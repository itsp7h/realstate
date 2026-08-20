<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The "?" button in the top bar.
 *
 * It shipped as a bare <button> with no handler, no [data-pop] wrapper and no
 * panel — clicking it did nothing at all. These pin the fix: the trigger is
 * wired to the shared dropdown machinery, the panel starts closed, and every
 * link inside it points somewhere the signed-in user is actually allowed to go.
 */
class ShellHelpPanelTest extends TestCase
{
    use RefreshDatabase;

    private function shell(User $user): string
    {
        $html = $this->actingAs($user)->get(route('dashboard'))->getContent();

        // The desktop shell bar only, so the ≤768px copy of the panel cannot
        // satisfy an assertion about the desktop one.
        return Str::before(Str::after($html, 'shell-topbar'), '<header class="topbar"');
    }

    /** The trigger markup alone, stopping before the panel it controls. */
    private function trigger(User $user): string
    {
        return Str::before(Str::after($this->shell($user), 'class="shell-helpbtn"'), '</button>');
    }

    public function test_the_help_button_opens_a_panel(): void
    {
        $trigger = $this->trigger(User::factory()->admin()->create());

        // The whole regression: it had none of these.
        $this->assertStringContainsString('data-pop-toggle', $trigger);
        $this->assertStringContainsString('aria-controls="shell-help"', $trigger);
        $this->assertStringContainsString('aria-expanded="false"', $trigger);
        $this->assertStringContainsString('aria-haspopup="true"', $trigger);
    }

    public function test_the_panel_starts_closed(): void
    {
        // Without hidden the panel covers the page on every load.
        $this->assertStringContainsString(
            'id="shell-help" hidden',
            $this->shell(User::factory()->admin()->create())
        );
    }

    public function test_the_panel_lists_the_shortcuts_the_layout_implements(): void
    {
        $shell = $this->shell(User::factory()->admin()->create());

        $this->assertStringContainsString('Search anything', $shell);
        $this->assertStringContainsString('⌘K', $shell);
        $this->assertStringContainsString('Esc', $shell);
    }

    public function test_an_admin_sees_the_role_reference(): void
    {
        $shell = $this->shell(User::factory()->admin()->create());

        $this->assertStringContainsString(route('roles.index'), $shell);
        $this->assertStringContainsString(route('reports.index'), $shell);
        $this->assertStringContainsString(route('dashboard'), $shell);
    }

    public function test_the_panel_never_offers_a_link_the_user_would_be_denied(): void
    {
        // roles.index is behind role:admin. Offering it to everyone would turn
        // the help panel into a 403 dispenser.
        $user = User::factory()->maintenance()->create();

        $this->assertStringNotContainsString(route('roles.index'), $this->shell($user));
        $this->actingAs($user)->get(route('roles.index'))->assertForbidden();
    }

    public function test_the_mobile_bar_gets_its_own_panel_without_the_keyboard_block(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->getContent();

        $mobile = Str::after($html, '<header class="topbar"');
        $mobile = Str::before($mobile, '</header>');

        // A separate id, because the shell bar is display:none below 769px and
        // two elements may not share one.
        $this->assertStringContainsString('id="topbar-help" hidden', $mobile);
        $this->assertStringContainsString('aria-controls="topbar-help"', $mobile);

        // No command palette and no keyboard down there, so no ⌘K.
        $this->assertStringNotContainsString('⌘K', $mobile);
        $this->assertStringContainsString('Tap any row', $mobile);
    }
}
