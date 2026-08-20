<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    /**
     * Attempts allowed against ONE identifier from one IP before lockout.
     * Guards a specific account being guessed at.
     */
    public const MAX_PER_CREDENTIAL = 5;

    /**
     * Attempts allowed from one IP across ALL identifiers. Without this, an
     * attacker rotating usernames never trips the per-credential limit — five
     * guesses each against a hundred accounts is a hundred free attempts.
     *
     * Set well above what a shared office IP does in a minute, so a team
     * behind one NAT address is not locked out by ordinary sign-ins.
     */
    public const MAX_PER_IP = 20;

    /** Both windows, in seconds. */
    public const DECAY_SECONDS = 60;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Rate-limiter key for this identifier from this IP. Lower-cased and
     * transliterated so 'Developer', 'developer' and 'devéloper' cannot each
     * carry their own five attempts against the same account.
     */
    public function credentialKey(): string
    {
        return 'login:'.Str::transliterate(Str::lower((string) $this->input('login'))).'|'.$this->ip();
    }

    /** Rate-limiter key for this IP, whatever identifier was submitted. */
    public function ipKey(): string
    {
        return 'login-ip:'.$this->ip();
    }
}
