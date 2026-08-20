<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Password reset, by emailed link.
 *
 * Before this, a locked-out account could only be recovered by an admin
 * editing it — and once sign-in gained a lockout, "wait for an admin" stopped
 * being an acceptable answer.
 *
 * Two properties this deliberately keeps:
 *
 *  • It never reveals whether an address has an account. Every request gets
 *    the same confirmation, whether a link was sent or not, so the form is not
 *    a way to enumerate staff email addresses.
 *  • It is rate limited on both the address and the caller's IP. Otherwise the
 *    form is a free way to send mail to any address, repeatedly.
 *
 * Mail goes through the application's DEFAULT mailer, not the azure mailer the
 * tenant-facing mail uses directly — so an environment with MAIL_MAILER=log
 * writes the link to the log instead of emailing a real person. Production
 * needs MAIL_MAILER set to a real transport for this to leave the building.
 */
class PasswordResetController extends Controller
{
    /** Reset requests allowed per address, and per IP, within the window. */
    public const MAX_PER_EMAIL = 3;

    public const MAX_PER_IP = 10;

    public const DECAY_SECONDS = 900;

    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = Str::lower($request->validated()['email']);

        if ($this->tooManyRequests($request, $email)) {
            return back()
                ->withErrors(['auth' => 'Too many reset requests. Try again later.'])
                ->onlyInput('email');
        }

        RateLimiter::hit($this->emailKey($email), self::DECAY_SECONDS);
        RateLimiter::hit($this->ipKey($request), self::DECAY_SECONDS);

        $status = Password::sendResetLink(['email' => $email]);

        // Recorded either way: a request for an address with no account is
        // itself worth seeing in the log.
        AuditLog::record(
            'password_reset_requested',
            AuditLog::AUTH,
            User::whereRaw('LOWER(email) = ?', [$email])->value('id'),
            $email,
            ['sent' => $status === Password::RESET_LINK_SENT]
        );

        // Same answer regardless, so the form cannot be used to test which
        // addresses exist.
        return back()->with('status', 'If that address has an account, a reset link is on its way.');
    }

    public function reset(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['email'] = Str::lower($data['email']);

        $status = Password::reset($data, function (User $user, string $password) {
            $user->forceFill([
                'password'       => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            AuditLog::record('password_reset', AuditLog::AUTH, $user->id, $user->name);

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            // An expired or already-used token, or an address that does not
            // match the one the link was issued for.
            return back()
                ->withErrors(['auth' => 'That reset link is no longer valid. Request a new one.'])
                ->onlyInput('email');
        }

        // The new password should work immediately, so clear any sign-in
        // lockout the failed attempts left behind.
        RateLimiter::clear('login:'.Str::transliterate($data['email']).'|'.$request->ip());

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been reset. Sign in with your new password.');
    }

    private function tooManyRequests(Request $request, string $email): bool
    {
        return RateLimiter::tooManyAttempts($this->emailKey($email), self::MAX_PER_EMAIL)
            || RateLimiter::tooManyAttempts($this->ipKey($request), self::MAX_PER_IP);
    }

    private function emailKey(string $email): string
    {
        return 'pw-reset:'.Str::transliterate($email);
    }

    private function ipKey(Request $request): string
    {
        return 'pw-reset-ip:'.$request->ip();
    }
}
