<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The mobile dashboard bell's alerts sheet.
 *
 * The bell first pointed at the Today segment: it switched the segmented
 * control and scrolled the alert list into view. That is a silent no-op once
 * Today is the remembered tab — which it becomes after the first tap — so from
 * the second tap on, the bell looked dead. It opens a sheet now, which answers
 * every time.
 */
class AlertsSheetTest extends TestCase
{
    use RefreshDatabase;

    private function dashboard(): string
    {
        return $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->getContent();
    }

    /** The alerts sheet only — the expense sheet that follows it starts the tail. */
    private function sheet(): string
    {
        return Str::between($this->dashboard(), 'id="alertsSheet"', 'id="expenseModal"');
    }

    private function makeOpenRequest(): MaintenanceRequest
    {
        return MaintenanceRequest::create([
            'date'               => '2026-05-21',
            'property'           => 'Tower A',
            'tenant'             => 'Ahmed Ali',
            'flat'               => '3B',
            'contact_no'         => '+973 3300 0000',
            'available_datetime' => '2026-05-22 10:00:00',
            'apartment_status'   => 'occupied',
            // A real status. This said 'pending', which is not one of the
            // statuses the app uses for a request (MaintenanceBoard lists the
            // live ones as open / waiting_supervisor / waiting_approval) — it
            // only counted because the dashboard's own alert list swept up
            // "anything not completed or cancelled". The shared feed asks a
            // sharper question, so the fixture has to be a real row.
            'status'             => 'waiting_supervisor',
        ]);
    }

    public function test_the_sheet_is_a_labelled_modal(): void
    {
        $sheet = $this->sheet();

        $this->assertStringContainsString('role="dialog"', $sheet);
        $this->assertStringContainsString('aria-modal="true"', $sheet);
        $this->assertStringContainsString('aria-labelledby="alertsSheetTitle"', $sheet);
    }

    /** A clean portfolio still has to say something, and say what it checked. */
    public function test_an_empty_bell_shows_a_clear_state(): void
    {
        $sheet = $this->sheet();

        $this->assertStringContainsString('alerts-clear', $sheet);
        $this->assertStringContainsString('all clear', $sheet);
        $this->assertStringContainsString('No overdue rent', $sheet);
        $this->assertStringContainsString('next 30 days', $sheet);
    }

    public function test_an_open_request_becomes_a_warning_toned_row(): void
    {
        $this->makeOpenRequest();
        $this->makeOpenRequest();

        $sheet = $this->sheet();

        // The feed's wording, not the dashboard's own: this sheet reads
        // App\Services\AttentionFeed like every other bell in the app.
        $this->assertStringContainsString('2 requests awaiting a decision', $sheet);
        $this->assertStringContainsString('--tone-warning-bg', $sheet);
        $this->assertStringContainsString(route('maintenance.index'), $sheet);
        $this->assertStringNotContainsString('all clear', $sheet);
    }

    public function test_the_header_counts_what_needs_you(): void
    {
        $this->makeOpenRequest();

        $this->assertStringContainsString('1 thing needs you today', $this->sheet());
    }

    public function test_the_sheet_offers_the_full_today_view(): void
    {
        $this->assertStringContainsString('id="alertsOpenToday"', $this->sheet());
    }

    /** Smart import is a shortcut, not something that needs you. */
    public function test_smart_import_is_not_counted_as_an_alert(): void
    {
        $html = $this->dashboard();

        $this->assertStringContainsString('Smart import', $html);          // still in Today
        $this->assertStringNotContainsString('Smart import', $this->sheet());
    }
}
