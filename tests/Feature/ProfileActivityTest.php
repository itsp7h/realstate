<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The account-activity card on /profile.
 *
 * Every figure it shows was already being written — LoginController audits
 * signed_in / sign_in_failed / locked_out, ProfileController audits
 * password_changed and profile_updated — but the audit log is Admin-only, so
 * the account it happened to could not check its own. No new schema: for an
 * authentication event the subject IS the actor, so entity_id is the account.
 */
class ProfileActivityTest extends TestCase
{
    use RefreshDatabase;

    private function event(User $user, string $action, string $when, ?array $changes = null, ?string $ip = '10.0.0.5'): AuditLog
    {
        $log = AuditLog::create([
            'action'      => $action,
            'entity_type' => AuditLog::AUTH,
            'entity_id'   => $user->id,
            'entity_name' => $user->name,
            'changes'     => $changes,
            'ip_address'  => $ip,
        ]);

        // forceFill, not create(): AuditLog::$fillable holds no timestamps, so
        // passing created_at to create() is silently dropped and every event
        // lands at now() — which quietly broke ordering and the 30-day window.
        return $log->forceFill([
            'created_at' => Carbon::parse($when),
            'updated_at' => Carbon::parse($when),
        ])->saveQuietly() ? $log->refresh() : $log;
    }

    public function test_the_card_lists_this_accounts_events(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana']);
        $this->event($user, 'signed_in', '2026-08-19 09:00');
        $this->event($user, 'password_changed', '2026-08-18 14:30');

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('Account activity', $html);
        $this->assertStringContainsString('Signed in', $html);
        $this->assertStringContainsString('Password changed', $html);
        $this->assertStringContainsString('10.0.0.5', $html);
    }

    /** The whole point of scoping: nobody sees anyone else's sign-ins. */
    public function test_another_accounts_events_are_never_shown(): void
    {
        $me    = User::factory()->admin()->create(['name' => 'Dana']);
        $other = User::factory()->create(['name' => 'Someone Else', 'email' => 'other@example.com']);

        $this->event($other, 'signed_in', '2026-08-19 09:00', null, '203.0.113.9');

        $html = $this->actingAs($me)->get(route('profile.edit'))->getContent();

        $this->assertStringNotContainsString('203.0.113.9', $html);
        $this->assertStringContainsString('Nothing recorded yet', $html);
    }

    /**
     * entity_id alone would match a building with the same id, which is why the
     * query pins entity_type to Auth as well.
     */
    public function test_a_record_event_with_the_same_id_is_not_mistaken_for_a_sign_in(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana']);

        AuditLog::create([
            'action' => 'updated', 'entity_type' => 'Building',
            'entity_id' => $user->id, 'entity_name' => 'Tower A',
            'ip_address' => '198.51.100.7',
        ]);

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertStringNotContainsString('198.51.100.7', $html);
        $this->assertStringNotContainsString('Tower A', $html);
    }

    /** The latest sign-in is the session reading the page — show the one before. */
    public function test_it_shows_the_previous_sign_in_not_the_current_one(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana']);
        $this->event($user, 'signed_in', '2026-08-10 08:00', null, '10.0.0.1');   // previous
        $this->event($user, 'signed_in', '2026-08-20 08:00', null, '10.0.0.2');   // current

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('10 Aug 2026, 08:00', $html);
        $this->assertStringContainsString('from 10.0.0.1', $html);
    }

    public function test_a_first_ever_sign_in_says_so_rather_than_showing_a_blank(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana']);
        $this->event($user, 'signed_in', '2026-08-20 08:00');

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('first sign-in on record', $html);
    }

    public function test_failed_attempts_are_counted_over_thirty_days(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana']);
        $this->event($user, 'sign_in_failed', now()->subDays(2)->toDateTimeString());
        $this->event($user, 'sign_in_failed', now()->subDays(5)->toDateTimeString());
        // Outside the window, so it must not be counted.
        $this->event($user, 'sign_in_failed', now()->subDays(40)->toDateTimeString());

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('is-danger', $html);
        $this->assertStringContainsString('If none of these were you', $html);
        $this->assertMatchesRegularExpression('/Failed sign-ins.*?>2</s', $html);
    }

    public function test_no_failures_reads_as_reassurance_not_a_warning(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana']);
        $this->event($user, 'signed_in', '2026-08-20 08:00');

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('None in the last 30 days', $html);
    }

    public function test_a_password_never_changed_says_so(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana']);

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('Never changed since the account was created', $html);
    }

    public function test_a_profile_update_names_the_fields_that_changed(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana', 'email' => 'dana@example.com']);

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Dana Reed', 'email' => 'dana@example.com',
        ]);

        $html = $this->actingAs($user->refresh())->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('Profile updated', $html);
        $this->assertStringContainsString('Changed name', $html);
    }

    /** Eight rows: a profile page is not the audit log. */
    public function test_the_list_is_capped(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Dana']);

        for ($i = 1; $i <= 12; $i++) {
            $this->event($user, 'signed_in', now()->subDays($i)->toDateTimeString(), null, "10.0.0.{$i}");
        }

        $html = $this->actingAs($user)->get(route('profile.edit'))->getContent();

        $this->assertStringContainsString('eight most recent', $html);
        // The ninth-oldest must not be on the page.
        $this->assertStringNotContainsString('10.0.0.12', $html);
    }
}
