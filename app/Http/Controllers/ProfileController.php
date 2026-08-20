<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $changed = array_keys(array_diff_assoc($data, $user->only(array_keys($data))));

        $user->update($data);

        if ($changed !== []) {
            AuditLog::record('profile_updated', AuditLog::AUTH, $user->id, $user->name, ['fields' => $changed]);
        }

        return redirect()->route('profile.edit')->with('success', 'Your profile has been updated.');
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
