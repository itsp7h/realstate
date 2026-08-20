<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\PasswordResetController;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        auth()->logout();
        RateLimiter::clear('pw-reset:owner@example.com');
        RateLimiter::clear('pw-reset-ip:127.0.0.1');
    }

    private function owner(): User
    {
        return User::factory()->admin()->create([
            'email'    => 'owner@example.com',
            'password' => bcrypt('old-password'),
        ]);
    }

    public function test_the_request_page_renders_for_a_guest(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Forgot your password?');
    }

    public function test_the_sign_in_page_links_to_it(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('href="'.route('password.request').'"', false);
    }

    public function test_a_reset_link_is_sent_to_a_real_account(): void
    {
        Notification::fake();
        $user = $this->owner();

        $this->post(route('password.email'), ['email' => 'owner@example.com'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_the_address_is_matched_case_insensitively(): void
    {
        Notification::fake();
        $user = $this->owner();

        $this->post(route('password.email'), ['email' => 'OWNER@Example.COM']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_an_unknown_address_gets_the_same_answer_and_no_mail(): void
    {
        Notification::fake();
        $this->owner();

        $known = $this->post(route('password.email'), ['email' => 'owner@example.com']);
        RateLimiter::clear('pw-reset:owner@example.com');
        $unknown = $this->post(route('password.email'), ['email' => 'ghost@example.com']);

        // Identical wording, so the form cannot be used to discover which
        // addresses have accounts.
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Notification::assertCount(1);
    }

    public function test_a_request_is_rate_limited_per_address(): void
    {
        Notification::fake();
        $this->owner();

        for ($i = 0; $i < PasswordResetController::MAX_PER_EMAIL; $i++) {
            $this->post(route('password.email'), ['email' => 'owner@example.com'])
                ->assertSessionHas('status');
        }

        // Past the limit the request is refused outright.
        $this->post(route('password.email'), ['email' => 'owner@example.com'])
            ->assertSessionHasErrors('auth');

        // Two layers, and this asserts the inner one: Laravel's broker will
        // not re-send within config('auth.passwords.users.throttle') seconds,
        // so the repeat requests under the limit produce no extra mail either.
        // One request, one email — which is what stops the form being used to
        // flood an inbox.
        Notification::assertCount(1);
    }

    public function test_a_request_is_rate_limited_per_ip_across_addresses(): void
    {
        Notification::fake();

        for ($i = 0; $i < PasswordResetController::MAX_PER_IP; $i++) {
            $this->post(route('password.email'), ['email' => "nobody{$i}@example.com"]);
        }

        $this->post(route('password.email'), ['email' => 'someone-else@example.com'])
            ->assertSessionHasErrors('auth');
    }

    public function test_a_request_is_recorded_in_the_audit_log(): void
    {
        Notification::fake();
        $user = $this->owner();

        $this->post(route('password.email'), ['email' => 'owner@example.com']);

        $log = AuditLog::where('action', 'password_reset_requested')->sole();
        $this->assertSame($user->id, (int) $log->entity_id);
        $this->assertTrue($log->changes['sent']);
    }

    public function test_a_request_for_an_unknown_address_is_recorded_without_an_id(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'ghost@example.com']);

        $log = AuditLog::where('action', 'password_reset_requested')->sole();
        $this->assertNull($log->entity_id);
        $this->assertFalse($log->changes['sent']);
    }

    /** @return string the token from the notification that was actually sent */
    private function captureToken(User $user): string
    {
        $token = null;
        Notification::fake();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        return $token;
    }

    public function test_the_reset_form_renders_with_the_token(): void
    {
        $user = $this->owner();
        $token = $this->captureToken($user);

        $this->get(route('password.reset', $token).'?email='.urlencode($user->email))
            ->assertOk()
            ->assertSee('value="'.$token.'"', false)
            ->assertSee($user->email);
    }

    public function test_a_valid_token_sets_the_new_password(): void
    {
        $user = $this->owner();
        $token = $this->captureToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    public function test_the_new_password_actually_signs_in(): void
    {
        $user = $this->owner();
        $token = $this->captureToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ]);

        $this->post(route('login.attempt'), [
            'login'    => $user->email,
            'password' => 'a-brand-new-password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_a_reset_is_recorded_in_the_audit_log(): void
    {
        $user = $this->owner();
        $token = $this->captureToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ]);

        $log = AuditLog::where('action', 'password_reset')->sole();
        $this->assertSame($user->id, (int) $log->entity_id);
    }

    public function test_a_token_cannot_be_used_twice(): void
    {
        $user = $this->owner();
        $token = $this->captureToken($user);

        $payload = [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ];

        $this->post(route('password.update'), $payload);
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('auth');
    }

    public function test_a_forged_token_is_refused(): void
    {
        $user = $this->owner();

        $this->post(route('password.update'), [
            'token'                 => 'not-a-real-token',
            'email'                 => $user->email,
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertSessionHasErrors('auth');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_the_two_passwords_must_match(): void
    {
        $user = $this->owner();
        $token = $this->captureToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'something-different',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_a_short_password_is_refused(): void
    {
        $user = $this->owner();
        $token = $this->captureToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }

    public function test_resetting_clears_a_sign_in_lockout(): void
    {
        $user = $this->owner();

        // Lock the account out by guessing at it.
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login.attempt'), ['login' => $user->email, 'password' => 'guess']);
        }

        $token = $this->captureToken($user);

        $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ]);

        // The whole point of the reset is to get back in, so the lockout the
        // failed guesses left behind must not still be standing.
        $this->post(route('login.attempt'), [
            'login'    => $user->email,
            'password' => 'a-brand-new-password',
        ]);

        $this->assertAuthenticated();
    }
}
