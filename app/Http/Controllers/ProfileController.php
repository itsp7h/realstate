<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Your own account.
 *
 * Before this there was no way to edit it: the users resource is Admin-only, so
 * a User or Maintenance account could not change its own name, email or
 * password — and once sign-in gained a lockout, "ask an admin" stopped being an
 * acceptable answer for a forgotten password too.
 *
 * Every route here acts on auth()->user() and never on an id from the request,
 * so there is no object to tamper with: this controller cannot be pointed at
 * somebody else's account.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user'               => $user,
            'events'             => $this->events($user)->limit(8)->get(),
            'currentSignIn'      => $this->events($user)->where('action', 'signed_in')->first(),
            'previousSignIn'     => $this->events($user)->where('action', 'signed_in')->skip(1)->first(),
            'lastPasswordChange' => $this->events($user)
                ->whereIn('action', ['password_changed', 'password_reset'])->first(),
            // A run of failures nobody can account for is the one thing on this
            // page worth interrupting someone about, so it gets its own figure
            // rather than being buried in the list.
            'failedAttempts'     => $this->events($user)
                ->where('action', 'sign_in_failed')
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ]);
    }

    /**
     * This account's security events, newest first.
     *
     * audit_logs carries no actor column — for an authentication event the
     * subject IS the actor, so entity_id is the account. Scoping on
     * entity_type as well is what stops a building that happens to share the
     * id being read as one of this user's sign-ins.
     *
     * It also means the card covers authentication only: a building edit is
     * recorded without who made it, so "you updated Tower A" is not something
     * the log can currently answer.
     */
    private function events(\App\Models\User $user): \Illuminate\Database\Eloquent\Builder
    {
        return AuditLog::query()
            ->where('entity_type', AuditLog::AUTH)
            ->where('entity_id', $user->id)
            ->latest();
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // The file and the checkbox are instructions, not column values.
        unset($data['photo'], $data['remove_photo']);

        $changed = array_keys(array_diff_assoc($data, $user->only(array_keys($data))));

        if ($request->hasFile('photo')) {
            // Delete the old one first: an account that changes its photo five
            // times should not leave five files on disk.
            $this->deletePhoto($user);
            $data['photo_path'] = $request->file('photo')->store('avatars', 'public');
            $changed[] = 'photo';
            // Distinguished, because "Changed photo" read the same for adding
            // one and taking one away — and on a security log those are not the
            // same event.
            $photoAction = $user->photo_path ? 'replaced' : 'added';
        } elseif ($request->boolean('remove_photo') && $user->photo_path) {
            $this->deletePhoto($user);
            $data['photo_path'] = null;
            $changed[] = 'photo';
            $photoAction = 'removed';
        }

        $user->update($data);

        if ($changed !== []) {
            AuditLog::record('profile_updated', AuditLog::AUTH, $user->id, $user->name, array_filter([
                'fields' => $changed,
                'photo'  => $photoAction ?? null,
            ]));
        }

        return redirect()->route('profile.edit')->with('success', 'Your profile has been updated.');
    }

    /**
     * Remove the stored file, if there is one.
     *
     * Guarded on the path rather than on the disk: a row whose file was already
     * deleted by hand must not raise, because the point of the call is to end
     * up with nothing there.
     */
    private function deletePhoto(\App\Models\User $user): void
    {
        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update(['password' => Hash::make($request->validated()['password'])]);

        // A new password means a new session identifier: anyone riding the old
        // one should not keep the account.
        $request->session()->regenerate();

        AuditLog::record('password_changed', AuditLog::AUTH, $user->id, $user->name);

        return redirect()->route('profile.edit')->with('success', 'Your password has been changed.');
    }
}
