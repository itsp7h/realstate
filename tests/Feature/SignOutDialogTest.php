<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The sign-out confirmation dialog in the layout.
 *
 * It replaced window.confirm() on every sign-out path: the native dialog
 * docks to the top of the phone screen, prefixes the question with the bare
 * host, cannot show which account is leaving, and labels the outcomes
 * "OK" / "Cancel".
 */
class SignOutDialogTest extends TestCase
{
    use RefreshDatabase;

    private function page(?User $user = null): string
    {
        return $this->actingAs($user ?: User::factory()->admin()->create([
            'name' => 'Dana Reed',
            'email' => 'dana@example.com',
        ]))->get(route('dashboard'))->getContent();
    }

    private function dialog(): string
    {
        return Str::between($this->page(), 'id="signOutDialog"', '<div class="shell-frame">');
    }

    public function test_no_sign_out_path_uses_the_native_confirm(): void
    {
        $this->assertStringNotContainsString('confirm(', $this->page());
    }

    public function test_the_dialog_is_a_labelled_modal(): void
    {
        $dialog = $this->dialog();

        $this->assertStringContainsString('role="dialog"', $dialog);
        $this->assertStringContainsString('aria-modal="true"', $dialog);
        $this->assertStringContainsString('aria-labelledby="signOutTitle"', $dialog);
        $this->assertStringContainsString('Sign out?', $dialog);
    }

    public function test_the_dialog_shows_which_account_is_leaving(): void
    {
        $dialog = $this->dialog();

        $this->assertStringContainsString('Dana Reed', $dialog);
        $this->assertStringContainsString('dana@example.com', $dialog);
        $this->assertStringContainsString('signout-avatar', $dialog);
    }

    public function test_both_buttons_name_their_outcome(): void
    {
        $dialog = $this->dialog();

        $this->assertStringContainsString('Stay signed in', $dialog);
        $this->assertStringContainsString('btn btn-danger', $dialog);
        $this->assertStringNotContainsString('>OK<', $dialog);
    }

    public function test_the_dialog_posts_to_logout_itself(): void
    {
        $dialog = $this->dialog();

        $this->assertStringContainsString('action="' . route('logout') . '"', $dialog);
        $this->assertStringContainsString('type="submit"', $dialog);
    }

    /** Every trigger must stay a real POST form so sign-out survives JS off. */
    public function test_every_trigger_is_still_a_real_form(): void
    {
        $page = $this->page();

        // Sidebar footer, More sheet row, and the avatar in each of the two
        // mobile header variants — the compact one's used to be an inert
        // <div> with cursor:pointer, and is the same control as Home's now.
        // Matching the closing bracket keeps the JS selector out of the count.
        $this->assertSame(4, substr_count($page, 'data-signout-form>'));
        $this->assertStringContainsString('form[data-signout-form]', $page);
    }

    public function test_signing_out_from_the_dialog_ends_the_session(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('logout'))->assertRedirect();
        $this->assertGuest();
    }
}
