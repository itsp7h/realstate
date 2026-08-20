<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The mobile login sheet (=<768px).
 *
 * The sign-in card docks at the bottom of the photo hero showing only its
 * handle and heading, and opens on tap. The slide itself is CSS and the
 * gestures are JS, so what is pinned here is the markup contract they both
 * depend on — and the one state the server decides: whether the sheet is
 * already open on arrival.
 */
class LoginSheetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The base TestCase signs every test in as an admin, and /login is behind
     * the `guest` middleware — without this every request here 302s to the
     * dashboard and the assertions inspect a redirect page.
     */
    protected function setUp(): void
    {
        parent::setUp();
        auth()->logout();
        $this->assertGuest();
    }

    /** The mobile layer only; the desktop shell has its own card. */
    private function sheet(string $html): string
    {
        return Str::between($html, '<div class="mobile-shell">', '</body>');
    }

    public function test_the_sheet_is_docked_on_a_clean_visit(): void
    {
        $html = $this->get(route('login'))->getContent();

        $this->assertStringContainsString('aria-expanded="false"', $this->sheet($html));
        // Asserted on the element, not the document: the class name also
        // appears in the stylesheet, where it always matches.
        $this->assertStringContainsString('<div class="phone">', $html,
            'A clean visit must show the photo, not the form.');
    }

    public function test_the_handle_is_a_button_wired_to_the_sheet(): void
    {
        $sheet = $this->sheet($this->get(route('login'))->getContent());

        // A div with a click handler is unreachable by keyboard; the handle has
        // to be a real button that says what it controls.
        $this->assertStringContainsString('id="sheetHandle"', $sheet);
        $this->assertStringContainsString('aria-controls="loginSheet"', $sheet);
        $this->assertStringContainsString('aria-label="Expand sign-in form"', $sheet);
        $this->assertMatchesRegularExpression('/<button[^>]*class="sheet-handle"/', $sheet);
    }

    public function test_the_heading_sits_outside_the_collapsing_body(): void
    {
        $sheet = $this->sheet($this->get(route('login'))->getContent());

        // The peek is measured to .sheet-inner's top edge, so the heading must
        // be its sibling — inside it, the docked sheet would show nothing.
        $head = Str::between($sheet, '<div class="sheet-head">', '</div>');
        $this->assertStringContainsString('Welcome back.', $head);
        $this->assertStringContainsString('m-subcopy', $head);
    }

    public function test_a_rejected_sign_in_arrives_with_the_sheet_open(): void
    {
        // The error renders inside the sheet. Docked, the user would be told
        // nothing and shown a photo.
        $this->from(route('login'))->post(route('login'), [
            'login'    => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertRedirect(route('login'));

        $html = $this->get(route('login'))->getContent();

        $this->assertStringContainsString('<div class="phone is-sheet-open">', $html);
        $this->assertStringContainsString('aria-expanded="true"', $this->sheet($html));
        $this->assertStringContainsString('aria-label="Collapse sign-in form"', $this->sheet($html));
    }

    public function test_repopulated_input_keeps_the_sheet_open(): void
    {
        // onlyInput('login') means the field comes back filled; a docked sheet
        // would hide the text the user already typed.
        $this->from(route('login'))->post(route('login'), [
            'login'    => 'someone@example.com',
            'password' => 'wrong-password',
        ]);

        $html = $this->get(route('login'))->getContent();

        $this->assertStringContainsString('someone@example.com', $html);
        $this->assertStringContainsString('<div class="phone is-sheet-open">', $html);
    }

    public function test_the_desktop_shell_is_untouched(): void
    {
        $html = $this->get(route('login'))->getContent();

        // >900px must not inherit any of this: the desktop card keeps its own
        // markup, and none of the sheet's hooks belong to it.
        $desktop = Str::between($html, '<div class="login-shell">', '<div class="mobile-shell">');
        $this->assertStringContainsString('login-card card', $desktop);
        $this->assertStringNotContainsString('sheet-handle', $desktop);
        $this->assertStringNotContainsString('aria-expanded', $desktop);
    }

    public function test_the_docked_sheet_advertises_that_it_opens(): void
    {
        $sheet = $this->sheet($this->get(route('login'))->getContent());

        // A panel that slides is not discoverable on its own. The hint lives
        // outside .sheet-head on purpose: absolutely positioned in the card's
        // top-right, it must not shift the heading or move the fold.
        $this->assertStringContainsString('peek-hint', $sheet);
        $this->assertStringContainsString('Tap to sign in', $sheet);

        $head = Str::between($sheet, '<div class="sheet-head">', '</div>');
        $this->assertStringNotContainsString('peek-hint', $head);
    }

    public function test_both_hints_share_the_header_slot(): void
    {
        $sheet = $this->sheet($this->get(route('login'))->getContent());

        // Two labels, one slot, swapped by CSS on the sheet's state. The close
        // hint is a button because it acts; the peek hint is not, because the
        // whole docked sheet is already the target.
        $this->assertStringContainsString('class="peek-hint"', $sheet);
        $this->assertStringContainsString('Tap to sign in', $sheet);
        $this->assertMatchesRegularExpression('/<button[^>]*class="close-hint"[^>]*id="closeHint"/', $sheet);
        $this->assertStringContainsString('Tap to close', $sheet);
    }

    public function test_the_mobile_hero_carries_the_brand_headline(): void
    {
        $sheet = $this->sheet($this->get(route('login'))->getContent());

        // Same statement as the desktop brand panel, gold last line and all.
        $this->assertStringContainsString('class="m-headline"', $sheet);
        $this->assertStringContainsString('Every building,', $sheet);
        $this->assertStringContainsString('<em>one ledger.</em>', $sheet);
    }

    public function test_the_retired_social_sign_in_block_is_gone(): void
    {
        $html = $this->get(route('login'))->getContent();

        // Removed with its divider, its CSS and its now-unused tokens; a
        // leftover "or continue with" label with nothing under it is worse
        // than either state.
        foreach (['social-row', 'btn-social', 'or continue with', 'photo-behind'] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
    }
}
