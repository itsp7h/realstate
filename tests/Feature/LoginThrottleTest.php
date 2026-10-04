<?php

namespace Tests\Feature;

use App\Http\Requests\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The sign-in form used to accept unlimited password guesses and leave no
 * trace that anyone had tried. These pin both halves of the fix.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The base TestCase signs a user in; every case here is a guest.
        auth()->logout();
        RateLimiter::clear('login:victim@example.com|127.0.0.1');
        RateLimiter::clear('login-ip:127.0.0.1');
    }

    private function attempt(string $login, string $password = 'wrong-password')
    {
        return $this->post(route('login.attempt'), [
            'login'    => $login,
            'password' => $password,
        ]);
    }

    public function test_a_wrong_password_is_refused(): void
    {
        User::factory()->admin()->create(['email' => 'victim@example.com']);

        $this->attempt('victim@example.com')->assertSessionHasErrors('auth');
        $this->assertGuest();
    }

    public function test_the_identifier_is_locked_out_after_the_configured_attempts(): void
    {
        User::factory()->admin()->create(['email' => 'victim@example.com', 'password' => bcrypt('correct-horse')]);

        for ($i = 0; $i < LoginRequest::MAX_PER_CREDENTIAL; $i++) {
            $this->attempt('victim@example.com')->assertSessionHasErrors('auth');
        }

        $response = $this->attempt('victim@example.com');

        $response->assertSessionHasErrors('auth');
        $this->assertStringContainsString(
            'Too many sign-in attempts',
            session('errors')->first('auth')
        );
    }

    public function test_lockout_refuses_even_the_correct_password(): void
    {
        User::factory()->admin()->create(['email' => 'victim@example.com', 'password' => bcrypt('correct-horse')]);

        for ($i = 0; $i < LoginRequest::MAX_PER_CREDENTIAL; $i++) {
            $this->attempt('victim@example.com');
        }

        // This is the whole point: the guesser must not get a free pass the
        // moment they happen to land on the right password.
        $this->attempt('victim@example.com', 'correct-horse');

        $this->assertGuest();
    }

    public function test_a_successful_sign_in_clears_the_identifier_window(): void
    {
        User::factory()->admin()->create(['email' => 'victim@example.com', 'password' => bcrypt('correct-horse')]);

        // Stay one below the limit, then succeed.
        for ($i = 0; $i < LoginRequest::MAX_PER_CREDENTIAL - 1; $i++) {
            $this->attempt('victim@example.com');
        }

        $this->attempt('victim@example.com', 'correct-horse');
        $this->assertAuthenticated();

        $this->assertSame(0, RateLimiter::attempts('login:victim@example.com|127.0.0.1'));
    }

    public function test_the_identifier_window_is_case_insensitive(): void
    {
        User::factory()->admin()->create(['email' => 'victim@example.com', 'password' => bcrypt('correct-horse')]);

        // Varying the case must not hand out a fresh allowance per spelling.
        $spellings = ['victim@example.com', 'Victim@Example.com', 'VICTIM@EXAMPLE.COM'];
        for ($i = 0; $i < LoginRequest::MAX_PER_CREDENTIAL; $i++) {
            $this->attempt($spellings[$i % count($spellings)]);
        }

        $this->attempt('victim@example.com', 'correct-horse');

        $this->assertGuest();
    }

    public function test_rotating_identifiers_still_trips_the_per_ip_window(): void
    {
        User::factory()->admin()->create(['email' => 'victim@example.com', 'password' => bcrypt('correct-horse')]);

        // Never more than a few guesses at any one name, so the per-credential
        // window never fires — this is the attack the IP window exists for.
        for ($i = 0; $i < LoginRequest::MAX_PER_IP; $i++) {
            $this->attempt("nobody{$i}@example.com");
        }

        $this->attempt('victim@example.com', 'correct-horse');

        $this->assertGuest();
        $this->assertStringContainsString(
            'Too many sign-in attempts',
            session('errors')->first('auth')
        );
    }

    public function test_the_per_ip_window_is_not_cleared_by_signing_in(): void
    {
        User::factory()->admin()->create(['email' => 'victim@example.com', 'password' => bcrypt('correct-horse')]);

        $this->attempt('nobody@example.com');
        $before = RateLimiter::attempts('login-ip:127.0.0.1');

        $this->attempt('victim@example.com', 'correct-horse');
        $this->assertAuthenticated();

        // Otherwise an attacker resets their own IP budget at will using an
        // account they already hold.
        $this->assertSame($before, RateLimiter::attempts('login-ip:127.0.0.1'));
    }

    // ── The audit trail ──────────────────────────────────────────────────────

    public function test_a_successful_sign_in_is_recorded(): void
    {
        $user = User::factory()->admin()->create(['email' => 'victim@example.com', 'password' => bcrypt('correct-horse')]);

        $this->attempt('victim@example.com', 'correct-horse');

        $log = AuditLog::where('action', 'signed_in')->sole();
        $this->assertSame(AuditLog::AUTH, $log->entity_type);
        $this->assertSame($user->id, (int) $log->entity_id);
        $this->assertSame($user->name, $log->entity_name);
    }

    public function test_a_failed_attempt_is_recorded_against_a_real_account(): void
    {
        $user = User::factory()->admin()->create(['email' => 'victim@example.com']);

        $this->attempt('victim@example.com');

        $log = AuditLog::where('action', 'sign_in_failed')->sole();
        $this->assertSame($user->id, (int) $log->entity_id);
        $this->assertSame('victim@example.com', $log->entity_name);
    }

    public function test_a_failed_attempt_at_an_unknown_name_is_recorded_without_an_id(): void
    {
        $this->attempt('ghost@example.com');

        $log = AuditLog::where('action', 'sign_in_failed')->sole();
        $this->assertNull($log->entity_id);
        $this->assertSame('ghost@example.com', $log->entity_name);
    }

    public function test_the_password_is_never_written_to_the_audit_log(): void
    {
        User::factory()->admin()->create(['email' => 'victim@example.com']);

        $this->attempt('victim@example.com', 'sup3r-s3cret');

        foreach (AuditLog::all() as $log) {
            $this->assertStringNotContainsString('sup3r-s3cret', json_encode($log->toArray()));
        }
    }

    public function test_a_lockout_is_recorded(): void
    {
        User::factory()->admin()->create(['email' => 'victim@example.com']);

        for ($i = 0; $i < LoginRequest::MAX_PER_CREDENTIAL + 1; $i++) {
            $this->attempt('victim@example.com');
        }

        $log = AuditLog::where('action', 'locked_out')->first();
        $this->assertNotNull($log, 'A lockout leaves no trace in the audit log.');
        $this->assertSame('victim@example.com', $log->entity_name);
        $this->assertArrayHasKey('retry_after_seconds', $log->changes);
    }

    public function test_signing_out_is_recorded(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        $this->post(route('logout'));

        $log = AuditLog::where('action', 'signed_out')->sole();
        $this->assertSame($user->id, (int) $log->entity_id);
        $this->assertSame($user->name, $log->entity_name);
    }

    public function test_the_audit_log_page_lists_and_filters_the_auth_events(): void
    {
        $user = User::factory()->admin()->create(['email' => 'victim@example.com', 'password' => bcrypt('correct-horse')]);
        $this->attempt('victim@example.com');                       // failed
        $this->attempt('victim@example.com', 'correct-horse');       // succeeded

        $response = $this->actingAs($user)->get(route('admin.audit-log'));

        $response->assertOk();
        $response->assertSee('Signed in');
        $response->assertSee('Sign-in failed');
        $response->assertSee('Failed sign-ins');

        // The filter offers every action the log can hold.
        foreach (AuditLog::ACTIONS as $action) {
            $response->assertSee('value="'.$action.'"', false);
        }

        // Assert on the row badges, not the page text — every action name
        // also appears in the filter's own dropdown.
        $filtered = $this->actingAs($user)->get(route('admin.audit-log', ['action' => 'sign_in_failed']));
        $filtered->assertOk();
        $filtered->assertSee('status-badge sign_in_failed', false);
        $filtered->assertDontSee('status-badge signed_in', false);
    }
}
