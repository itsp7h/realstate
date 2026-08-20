<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Sign in, rate-limited and audited.
     *
     * Both parts matter together: without a limit, the form accepts unlimited
     * password guesses, and without an audit trail there is no record that
     * anyone tried. The limiter is backed by the cache store (database here),
     * so the count survives across requests and php-fpm workers.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($seconds = $this->lockoutSeconds($request)) {
            AuditLog::record('locked_out', AuditLog::AUTH, null, $validated['login'], [
                'retry_after_seconds' => $seconds,
            ]);

            return back()
                ->withErrors(['auth' => $this->lockoutMessage($seconds)])
                ->onlyInput('login');
        }

        $identifierField = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        // Case-insensitive lookup — "developer" must match a user named
        // "Developer" the same way it would match on exact case, since
        // people don't reliably remember/retype their own capitalization.
        $user = User::whereRaw("LOWER({$identifierField}) = ?", [strtolower($validated['login'])])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($request->credentialKey(), LoginRequest::DECAY_SECONDS);
            RateLimiter::hit($request->ipKey(), LoginRequest::DECAY_SECONDS);

            // The submitted identifier, never the password. entity_id is set
            // only when the identifier matched a real account, which is what
            // separates "wrong password for a real user" from "guessing at
            // names that do not exist" when reading the log back.
            AuditLog::record('sign_in_failed', AuditLog::AUTH, $user?->id, $validated['login']);

            return back()
                ->withErrors(['auth' => 'These credentials do not match our records.'])
                ->onlyInput('login');
        }

        // Only the credential window is cleared. Clearing the per-IP window
        // would let an attacker reset it at will by signing into an account
        // they already hold.
        RateLimiter::clear($request->credentialKey());

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        AuditLog::record('signed_in', AuditLog::AUTH, $user->id, $user->name, [
            'remember' => $request->boolean('remember'),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        // Read the user before the session goes.
        if ($user = $request->user()) {
            AuditLog::record('signed_out', AuditLog::AUTH, $user->id, $user->name);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Seconds until the caller may try again, or null if they may try now.
     * Checks the identifier window first so the message reflects the limit the
     * user actually hit.
     */
    private function lockoutSeconds(LoginRequest $request): ?int
    {
        $windows = [
            [$request->credentialKey(), LoginRequest::MAX_PER_CREDENTIAL],
            [$request->ipKey(), LoginRequest::MAX_PER_IP],
        ];

        foreach ($windows as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                // availableIn can round to 0 on the final second; a lockout
                // message that says "try again in 0 seconds" reads as a bug.
                return max(RateLimiter::availableIn($key), 1);
            }
        }

        return null;
    }

    private function lockoutMessage(int $seconds): string
    {
        if ($seconds < 60) {
            return "Too many sign-in attempts. Try again in {$seconds} second".($seconds === 1 ? '' : 's').'.';
        }

        $minutes = (int) ceil($seconds / 60);

        return "Too many sign-in attempts. Try again in {$minutes} minute".($minutes === 1 ? '' : 's').'.';
    }
}
