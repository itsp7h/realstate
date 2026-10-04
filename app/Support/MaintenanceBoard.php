<?php

namespace App\Support;

use App\Models\MaintenanceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * The maintenance board's three columns.
 *
 * The design handoff shows a three-column kanban; the app has seven statuses.
 * This is the mapping, in one place, because a board whose columns disagree
 * with the status filter beside it is worse than a table.
 *
 * The seven statuses collapse by *who is holding the work*:
 *
 *   Needs attention  open · waiting_supervisor · waiting_approval
 *                    → waiting on somebody here to look or decide
 *   In progress      approved · in_progress
 *                    → decided, work happening
 *   Closed           completed · cancelled
 *                    → nothing left to do, either way
 *
 * Every status appears in exactly one column, asserted by MaintenanceBoardTest
 * so a status added to the domain cannot quietly vanish from the board.
 *
 * ── ON PRIORITY ──────────────────────────────────────────────────────────────
 * The handoff's cards carry Low/Medium/High/Urgent pills. There is no priority
 * column on maintenance_requests, and inventing one in the view would mean
 * showing a figure the data does not support. The cards use age instead, which
 * is derived from the request date and is the honest urgency signal available:
 * a request sitting for three weeks is the one to look at. Adding a real
 * priority field is a schema change and a decision about who sets it.
 */
final class MaintenanceBoard
{
    /** A request open longer than this is called out on its card. */
    public const STALE_DAYS = 14;

    /**
     * Cards rendered per column before the rest are summarised. A board is for
     * seeing the shape of the work; past a couple of dozen cards a column is a
     * list, and the list view does that better.
     */
    public const PER_COLUMN = 25;

    /** @var array<string, array{label:string, tone:string, statuses:list<string>}> */
    public const COLUMNS = [
        'attention' => [
            'label'    => 'Needs attention',
            'tone'     => 'danger',
            'statuses' => ['open', 'waiting_supervisor', 'waiting_approval'],
        ],
        'progress' => [
            'label'    => 'In progress',
            'tone'     => 'accent',
            'statuses' => ['approved', 'in_progress'],
        ],
        'closed' => [
            'label'    => 'Closed',
            'tone'     => 'success',
            'statuses' => ['completed', 'cancelled'],
        ],
    ];

    /** Every status the board accounts for. */
    public static function mappedStatuses(): array
    {
        return array_merge(...array_column(self::COLUMNS, 'statuses'));
    }

    /**
     * Build the columns from an already-filtered query, so the board narrows
     * with the same filter bar the list uses — the filtering stays in SQL and
     * only the cards for the visible columns cross the wire.
     *
     * @return list<array{key:string,label:string,tone:string,total:int,hidden:int,url:string,items:\Illuminate\Support\Collection}>
     */
    public static function columns(Builder $filtered): array
    {
        $today = Carbon::today();
        $out = [];

        foreach (self::COLUMNS as $key => $column) {
            $scoped = (clone $filtered)->whereIn('status', $column['statuses']);

            $total = (clone $scoped)->count();
            $items = (clone $scoped)
                ->orderByRaw('COALESCE(request_date, date) asc')   // oldest first: the ones going stale surface
                ->limit(self::PER_COLUMN)
                ->get()
                ->map(function (MaintenanceRequest $r) use ($today) {
                    $raised = $r->request_date ?: $r->date;
                    $age = $raised ? (int) $raised->diffInDays($today) : null;

                    return [
                        'model'   => $r,
                        'age'     => $age,
                        'isStale' => $age !== null && $age > self::STALE_DAYS && ! in_array($r->status, self::COLUMNS['closed']['statuses'], true),
                    ];
                });

            $out[] = [
                'key'    => $key,
                'label'  => $column['label'],
                'tone'   => $column['tone'],
                'total'  => $total,
                'hidden' => max(0, $total - $items->count()),
                // Where "+N more" goes: the list view, narrowed to this column.
                'url'    => route('maintenance.index', array_merge(
                    request()->except(['view', 'status', 'page']),
                    ['view' => 'list', 'stage' => $key]
                )),
                'items'  => $items,
            ];
        }

        return $out;
    }
}
