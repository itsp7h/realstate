<?php

namespace Tests\Feature;

use App\Mail\ExpiringLeasesDigest;
use App\Models\AuditLog;
use App\Models\LeaseContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Nothing watched lease expiry before: a lease could run out with no warning
 * to anyone, and the only scheduled job in the app chased overdue invoices.
 */
class ReportExpiringLeasesTest extends TestCase
{
    use RefreshDatabase;

    private function makeLease(string $endDate, string $ref = null): LeaseContract
    {
        return LeaseContract::create([
            'date'               => '2026-01-01',
            'lease_agreement_no' => $ref ?: 'LA-'.uniqid(),
            'tenant_name'        => 'Yousif Kanoo',
            'property_name'      => 'Marina Bay Tower',
            'unit'               => 'Flat 1',
            'lease_start_date'   => '2026-01-01',
            'lease_end_date'     => $endDate,
        ]);
    }

    public function test_it_says_so_when_nothing_is_ending(): void
    {
        $this->makeLease(Carbon::today()->addYear()->toDateString());

        $this->artisan('leases:report-expiring')
            ->expectsOutputToContain('No leases end in the next')
            ->assertExitCode(0);
    }

    public function test_it_lists_a_lease_ending_inside_the_window(): void
    {
        $this->makeLease(Carbon::today()->addDays(10)->toDateString(), 'LA-ENDING-1');

        $this->artisan('leases:report-expiring', ['--no-mail' => true])
            ->expectsOutputToContain('LA-ENDING-1')
            ->assertExitCode(0);
    }

    public function test_it_ignores_leases_outside_the_window(): void
    {
        $this->makeLease(Carbon::today()->addDays(200)->toDateString(), 'LA-FAR-OFF');
        $this->makeLease(Carbon::today()->subDays(5)->toDateString(), 'LA-ALREADY-GONE');

        $this->artisan('leases:report-expiring', ['--no-mail' => true])
            ->doesntExpectOutputToContain('LA-FAR-OFF')
            ->doesntExpectOutputToContain('LA-ALREADY-GONE')
            ->assertExitCode(0);
    }

    public function test_the_window_is_configurable(): void
    {
        $this->makeLease(Carbon::today()->addDays(100)->toDateString(), 'LA-IN-100-DAYS');

        $this->artisan('leases:report-expiring', ['--days' => 120, '--no-mail' => true])
            ->expectsOutputToContain('LA-IN-100-DAYS')
            ->assertExitCode(0);
    }

    public function test_a_nonsense_window_is_refused(): void
    {
        $this->artisan('leases:report-expiring', ['--days' => 0])->assertExitCode(1);
    }

    public function test_it_emails_the_admins(): void
    {
        Mail::fake();
        User::factory()->admin()->create(['email' => 'boss@example.com']);
        User::factory()->user()->create(['email' => 'staff@example.com']);
        $this->makeLease(Carbon::today()->addDays(10)->toDateString());

        $this->artisan('leases:report-expiring')->assertExitCode(0);

        // Admins only — the digest is a management decision list, and a
        // non-admin cannot open the leases page it points at.
        Mail::assertSent(ExpiringLeasesDigest::class, function (ExpiringLeasesDigest $mail) {
            return $mail->hasTo('boss@example.com') && ! $mail->hasTo('staff@example.com');
        });
    }

    public function test_no_mail_flag_sends_nothing(): void
    {
        Mail::fake();
        User::factory()->admin()->create(['email' => 'boss@example.com']);
        $this->makeLease(Carbon::today()->addDays(10)->toDateString());

        $this->artisan('leases:report-expiring', ['--no-mail' => true])->assertExitCode(0);

        Mail::assertNothingSent();
    }

    public function test_the_run_is_recorded_in_the_audit_log(): void
    {
        Mail::fake();
        $this->makeLease(Carbon::today()->addDays(10)->toDateString(), 'LA-AUDITED');

        $this->artisan('leases:report-expiring', ['--no-mail' => true]);

        $log = AuditLog::where('action', 'leases_expiring')->sole();
        $this->assertSame(1, $log->changes['count']);
        $this->assertContains('LA-AUDITED', $log->changes['agreements']);
    }

    public function test_it_still_records_the_run_when_mail_fails(): void
    {
        User::factory()->admin()->create(['email' => 'boss@example.com']);
        $this->makeLease(Carbon::today()->addDays(10)->toDateString());

        // No Mail::fake() and a transport that cannot deliver — the watch must
        // survive a broken mailer rather than lose the run.
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);

        $this->artisan('leases:report-expiring')->assertExitCode(0);

        $this->assertSame(1, AuditLog::where('action', 'leases_expiring')->count());
    }

    public function test_it_is_scheduled(): void
    {
        // Asserted through schedule:list rather than the Schedule singleton:
        // the singleton is resolved once per application, so a test that ran
        // artisan earlier in the class leaves it in a state where events() is
        // not a reliable read. This is also the command an operator would run
        // to check the same thing.
        $this->artisan('schedule:list')
            ->expectsOutputToContain('leases:report-expiring')
            ->assertExitCode(0);
    }
}
