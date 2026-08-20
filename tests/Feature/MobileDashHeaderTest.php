<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The three controls in the mobile dashboard header (theme, bell, avatar).
 *
 * Two of them were broken in opposite directions: the avatar ended the
 * session on a single tap with nothing asked, and the bell wore a live red
 * dot while doing nothing at all when tapped. These pin both fixes.
 */
class MobileDashHeaderTest extends TestCase
{
    use RefreshDatabase;

    private function header(?User $user = null): string
    {
        $html = $this->actingAs($user ?: User::factory()->admin()->create(['email' => 'boss@example.com']))
            ->get(route('dashboard'))
            ->getContent();

        // The mobile header only — the desktop shell top bar has its own
        // account menu, covered by ShellAccountMenuTest.
        return Str::between($html, 'id="pmDashHeader"', '</div>' . PHP_EOL . PHP_EOL . '    <div class="pm-scroll"');
    }

    public function test_the_avatar_asks_before_signing_out(): void
    {
        $header = $this->header();

        // The form stays a real POST form (the no-JS fallback); the attribute
        // is what hands it to #signOutDialog instead of ending the session.
        $this->assertStringContainsString('data-signout-form', $header);
        $this->assertStringContainsString(route('logout'), $header);
        $this->assertStringNotContainsString('confirm(', $header);
    }

    public function test_the_avatar_names_the_account_it_signs_out_of(): void
    {
        $this->assertStringContainsString(
            'aria-label="Sign out of boss@example.com"',
            $this->header()
        );
    }

    public function test_the_bell_is_a_real_control(): void
    {
        $header = $this->header();

        $this->assertStringContainsString('id="pmAlertsBtn"', $header);
        $this->assertStringNotContainsString('coming soon', $header);
        $this->assertStringContainsString('needs you today', $header);
    }

    public function test_the_bell_wears_no_dot_when_nothing_needs_attention(): void
    {
        $header = $this->header();

        $this->assertStringNotContainsString('pm-dot', $header);
        $this->assertStringContainsString('No alerts', $header);
    }

    /** The bell opens a sheet; the Today view stays reachable from inside it. */
    public function test_the_bell_opens_the_alerts_sheet(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->getContent();

        $this->assertStringContainsString('id="alertsSheet"', $html);
        $this->assertStringContainsString("getElementById('pmAlertsBtn')", $html);
        $this->assertStringContainsString("btn.addEventListener('click', open)", $html);
    }

    public function test_the_today_view_is_still_reachable_by_deep_link(): void
    {
        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->getContent();

        $this->assertStringContainsString('id="pmNeedsToday"', $html);
        $this->assertStringContainsString('window.pmRevealAlerts', $html);
        $this->assertStringContainsString("window.location.hash === '#today'", $html);
    }

    public function test_pushed_screens_link_their_bell_to_the_same_alert_list(): void
    {
        $building = Building::create(['property_name' => 'Tower A', 'property_code' => 'TA1']);

        $html = $this->actingAs(User::factory()->admin()->create())
            ->get(route('buildings.show', $building))
            ->getContent();

        $this->assertStringContainsString(route('dashboard') . '#today', $html);
        $this->assertStringNotContainsString('coming soon', $html);
    }
}
