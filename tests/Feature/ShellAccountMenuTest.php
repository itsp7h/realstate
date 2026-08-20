<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The account chip in the top bar.
 *
 * It used to be a submit button for the logout form while displaying a
 * chevron-down, so the affordance said "menu" and the click said "goodbye" —
 * one stray click ended the session with nothing asked. These pin the fix.
 */
class ShellAccountMenuTest extends TestCase
{
    use RefreshDatabase;

    private function topbar(): string
    {
        $html = $this->actingAs(User::factory()->admin()->create(['email' => 'boss@example.com']))
            ->get(route('dashboard'))
            ->getContent();

        // The desktop top bar only; the mobile drawer and the "more" sheet have
        // their own, deliberately explicit, sign-out rows.
        return Str::between($html, 'shell-topbar', '</header>');
    }

    /**
     * The trigger only — Str::between() runs to the LAST match, which would
     * swallow the menu's own submit button and make this assert nothing.
     */
    private function chip(): string
    {
        return Str::before(Str::after($this->topbar(), '<div class="shell-account"'), '</button>');
    }

    public function test_the_account_chip_does_not_submit_a_form(): void
    {
        $chip = $this->chip();

        // The whole regression in one assertion.
        $this->assertStringContainsString('type="button"', $chip);
        $this->assertStringNotContainsString('type="submit"', $chip);
        $this->assertStringNotContainsString('form="shellLogout"', $chip);
    }

    public function test_the_chip_announces_the_menu_it_opens(): void
    {
        $chip = $this->chip();

        $this->assertStringContainsString('aria-expanded="false"', $chip);
        $this->assertStringContainsString('aria-haspopup="true"', $chip);
        $this->assertStringContainsString('aria-controls="shell-account-menu"', $chip);
    }

    public function test_signing_out_lives_inside_the_menu(): void
    {
        $topbar = $this->topbar();
        $this->assertStringContainsString('id="shell-account-menu" hidden', $topbar,
            'The menu must start closed, or the panel covers the page on load.');
        $this->assertStringContainsString(route('logout'), $topbar);
        $this->assertStringContainsString('Sign out', $topbar);
    }

    public function test_the_menu_names_who_is_signed_in(): void
    {
        // Signing out of the wrong account is the mistake the identity block
        // is there to prevent.
        $this->assertStringContainsString('boss@example.com', $this->topbar());
    }

    public function test_the_logout_route_still_only_accepts_post(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        // A GET logout would let any link or prefetch end the session.
        $this->get('/logout')->assertStatus(405);
    }

    public function test_signing_out_from_the_menu_works(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_both_top_bar_dropdowns_use_the_shared_panel(): void
    {
        $topbar = $this->topbar();

        // One handler drives both, keyed off these hooks; renaming one without
        // the other silently leaves a panel that cannot be opened.
        $this->assertSame(2, substr_count($topbar, 'data-pop>'), 'Expected exactly two top-bar dropdowns.');
        $this->assertSame(2, substr_count($topbar, 'data-pop-toggle'));
        $this->assertSame(2, substr_count($topbar, 'class="shell-pop '));
    }
}
