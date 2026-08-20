<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Support\MaintenanceBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MaintenanceBoardTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $status, array $overrides = []): MaintenanceRequest
    {
        return MaintenanceRequest::create(array_merge([
            'date'               => Carbon::today()->subDays(2)->toDateString(),
            'property'           => 'Marina Bay Tower',
            'tenant'             => 'Ahmed Ali',
            'flat'               => '3B',
            'contact_no'         => '+973 3300 0000',
            'available_datetime' => '2026-05-22 10:00:00',
            'apartment_status'   => 'occupied',
            'status'             => $status,
        ], $overrides));
    }

    /**
     * The board's whole claim is that it accounts for the work. A status that
     * belongs to no column would be invisible on it.
     */
    public function test_every_domain_status_lands_in_exactly_one_column(): void
    {
        $domain = ['open', 'waiting_supervisor', 'waiting_approval', 'approved', 'in_progress', 'completed', 'cancelled'];
        $mapped = MaintenanceBoard::mappedStatuses();

        $this->assertSame([], array_diff($domain, $mapped), 'A status the domain produces is on no column.');
        $this->assertSame([], array_diff($mapped, $domain), 'A column names a status the domain never produces.');
        $this->assertSame(count($mapped), count(array_unique($mapped)), 'A status appears in two columns.');
    }

    public function test_the_board_is_the_default_view(): void
    {
        $this->make('open');

        $response = $this->get(route('maintenance.index'));

        $response->assertOk();
        $response->assertSee('class="board', false);
        foreach (MaintenanceBoard::COLUMNS as $column) {
            $response->assertSee($column['label']);
        }
    }

    public function test_an_explicit_status_filter_opens_the_list_instead(): void
    {
        $this->make('waiting_approval');

        // Two of three columns would be empty by construction, so a filtered
        // arrival (the notification bell links this way) gets the table.
        $response = $this->get(route('maintenance.index', ['status' => 'waiting_approval']));

        $response->assertOk();
        $response->assertDontSee('class="board', false);
        $response->assertSee('table-card', false);
    }

    public function test_the_view_can_be_switched_explicitly(): void
    {
        $this->make('open');

        $this->get(route('maintenance.index', ['view' => 'list']))
            ->assertOk()->assertDontSee('class="board', false);

        $this->get(route('maintenance.index', ['view' => 'board', 'status' => 'open']))
            ->assertOk()->assertSee('class="board', false);
    }

    public function test_a_request_appears_under_its_stage(): void
    {
        $this->make('open', ['job_order' => 'JO-NEEDS-LOOK']);
        $this->make('in_progress', ['job_order' => 'JO-UNDERWAY']);
        $this->make('completed', ['job_order' => 'JO-FINISHED']);

        $columns = collect(MaintenanceBoard::columns(MaintenanceRequest::query()))->keyBy('key');

        $this->assertSame(1, $columns['attention']['total']);
        $this->assertSame(1, $columns['progress']['total']);
        $this->assertSame(1, $columns['closed']['total']);

        $this->assertSame('JO-NEEDS-LOOK', $columns['attention']['items'][0]['model']->job_order);
        $this->assertSame('JO-UNDERWAY', $columns['progress']['items'][0]['model']->job_order);
    }

    public function test_the_board_narrows_with_the_filter_bar(): void
    {
        $this->make('open', ['job_order' => 'JO-KEEP', 'property' => 'Marina Bay Tower']);
        $this->make('open', ['job_order' => 'JO-DROP', 'property' => 'Some Other Block']);

        $response = $this->get(route('maintenance.index', ['view' => 'board', 'search' => 'Marina']));

        // Filtering is the query's job, not the board's — the dropped card must
        // never reach the page.
        $response->assertOk();
        $response->assertSee('JO-KEEP');
        $response->assertDontSee('JO-DROP');
    }

    public function test_the_oldest_request_surfaces_first(): void
    {
        $this->make('open', ['job_order' => 'JO-NEW', 'date' => Carbon::today()->subDay()->toDateString()]);
        $this->make('open', ['job_order' => 'JO-OLD', 'date' => Carbon::today()->subDays(40)->toDateString()]);

        $items = collect(MaintenanceBoard::columns(MaintenanceRequest::query()))
            ->firstWhere('key', 'attention')['items'];

        $this->assertSame('JO-OLD', $items[0]['model']->job_order);
    }

    public function test_an_old_open_request_is_flagged_stale(): void
    {
        $this->make('open', ['date' => Carbon::today()->subDays(MaintenanceBoard::STALE_DAYS + 5)->toDateString()]);

        $items = collect(MaintenanceBoard::columns(MaintenanceRequest::query()))
            ->firstWhere('key', 'attention')['items'];

        $this->assertTrue($items[0]['isStale']);
    }

    public function test_a_closed_request_is_never_stale(): void
    {
        $this->make('completed', ['date' => Carbon::today()->subDays(400)->toDateString()]);

        $items = collect(MaintenanceBoard::columns(MaintenanceRequest::query()))
            ->firstWhere('key', 'closed')['items'];

        // Nothing is owed on it, so age carries no warning.
        $this->assertFalse($items[0]['isStale']);
    }

    public function test_a_column_caps_what_it_draws_and_says_so(): void
    {
        for ($i = 0; $i < MaintenanceBoard::PER_COLUMN + 4; $i++) {
            $this->make('open', ['job_order' => 'JO-BULK-'.$i]);
        }

        $column = collect(MaintenanceBoard::columns(MaintenanceRequest::query()))
            ->firstWhere('key', 'attention');

        $this->assertCount(MaintenanceBoard::PER_COLUMN, $column['items']);
        $this->assertSame(4, $column['hidden']);
        // A silent cap reads as "that is all of them".
        $this->get(route('maintenance.index'))->assertSee('+4 more in the list');
    }

    public function test_the_stage_link_narrows_the_list_to_that_column(): void
    {
        $this->make('open', ['job_order' => 'JO-ATTENTION']);
        $this->make('in_progress', ['job_order' => 'JO-PROGRESS']);

        $response = $this->get(route('maintenance.index', ['view' => 'list', 'stage' => 'attention']));

        $response->assertOk();
        $response->assertSee('JO-ATTENTION');
        $response->assertDontSee('JO-PROGRESS');
    }

    public function test_an_unknown_stage_is_ignored_rather_than_emptying_the_list(): void
    {
        $this->make('open', ['job_order' => 'JO-STILL-HERE']);

        $this->get(route('maintenance.index', ['view' => 'list', 'stage' => 'not-a-stage']))
            ->assertOk()
            ->assertSee('JO-STILL-HERE');
    }

    public function test_an_empty_column_says_so(): void
    {
        $this->make('open');

        $this->get(route('maintenance.index'))->assertSee('Nothing here.');
    }
}
