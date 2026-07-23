<?php

namespace App\Services;

use App\Models\Building;
use App\Models\EwaBill;
use App\Models\InvoiceNote;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates the per-month portfolio series (income/expenses/credits/debits/profit),
 * the per-building current-month performance snapshot shown on the main dashboard,
 * and the richer single-building dashboard shown on a building's own page.
 * Everything is built on top of ProfitLossService's revenue/expense recognition
 * rules so numbers always agree with the Profit & Loss report.
 */
class DashboardAnalyticsService
{
    public function __construct(private ProfitLossService $profitLoss)
    {
    }

    public function monthlySeries(int $year, ?int $buildingId = null): array
    {
        $today = Carbon::today();
        $labels = $income = $expenses = $credits = $debits = $profit = [];

        $buildingName = $buildingId ? Building::find($buildingId)?->property_name : null;

        for ($month = 1; $month <= 12; $month++) {
            $from = Carbon::create($year, $month, 1)->startOfMonth();
            $labels[] = $from->format('M');

            if ($from->gt($today)) {
                $income[] = $expenses[] = $credits[] = $debits[] = $profit[] = null;
                continue;
            }

            $to = $from->copy()->endOfMonth()->min($today);

            $pl = $this->profitLoss->build($from, $to, $buildingId);

            $income[]   = $pl['total_revenue'];
            $expenses[] = $pl['total_expense'];
            $profit[]   = $pl['net_profit'];

            $noteQuery = fn (string $type) => InvoiceNote::where('type', $type)
                ->whereDate('note_date', '>=', $from)->whereDate('note_date', '<=', $to)
                ->when($buildingName !== null, fn ($q) => $q->whereHas('invoice', fn ($iq) => $iq->where('property_name', $buildingName)));

            $credits[] = round((float) $noteQuery('credit')->sum('amount'), 3);
            $debits[]  = round((float) $noteQuery('debit')->sum('amount'), 3);
        }

        return compact('labels', 'income', 'expenses', 'credits', 'debits', 'profit');
    }

    public function buildingPerformance(Carbon $from, Carbon $to): Collection
    {
        return Building::with('images')->orderBy('property_name')->get()
            ->map(fn (Building $building) => array_merge(
                ['building' => $building],
                $this->buildingSnapshot($building, $from, $to)
            ));
    }

    /**
     * Full dashboard payload for a single building's own page: KPIs, a
     * monthly financial series, portfolio-composition breakdowns, upcoming
     * lease expirations, and recent maintenance activity.
     */
    public function buildingDashboard(Building $building, Collection $units, Collection $contracts, int $year): array
    {
        $today     = Carbon::today();
        $monthFrom = $today->copy()->startOfMonth();
        $monthTo   = $today->copy()->endOfMonth();

        $snapshot = $this->buildingSnapshot($building, $monthFrom, $monthTo);

        $totalUnits    = $units->count();
        $occupiedUnits = $units->filter(fn ($u) => $u->activeContract !== null)->count();

        $unitConditions = $units->groupBy(fn ($u) => $u->unit_condition ?: 'Unspecified')
            ->map->count()->sortDesc();

        $leaseStatusCounts = collect(['active', 'expiring', 'upcoming', 'expired'])
            ->mapWithKeys(fn ($status) => [$status => $contracts->where('status', $status)->count()]);

        $upcomingExpirations = $contracts
            ->filter(fn ($c) => in_array($c->status, ['active', 'expiring'], true) && $c->lease_end_date)
            ->filter(fn ($c) => $c->lease_end_date->greaterThanOrEqualTo($today) && $c->lease_end_date->lessThanOrEqualTo($today->copy()->addDays(60)))
            ->sortBy('lease_end_date')
            ->take(6)
            ->values();

        $recentMaintenance = MaintenanceRequest::where('building_id', $building->id)
            ->orderByDesc('date')
            ->limit(6)
            ->get();

        return [
            'kpis' => [
                'total_units'       => $totalUnits,
                'occupied_units'    => $occupiedUnits,
                'vacant_units'      => max(0, $totalUnits - $occupiedUnits),
                'occupancy_percent' => $snapshot['occupancy_percent'],
                'tenant_count'      => $snapshot['tenant_count'],
                'total_floors'      => $building->floors()->count(),
                'month_income'      => $snapshot['total_income'],
                'month_expense'     => round(array_sum($snapshot['expenses']), 3),
                'month_profit'      => $snapshot['net_income'],
            ],
            'monthly'              => $this->monthlySeries($year, $building->id),
            'unit_conditions'      => $unitConditions,
            'lease_status_counts'  => $leaseStatusCounts,
            'expenses'             => $snapshot['expenses'],
            'upcoming_expirations' => $upcomingExpirations,
            'recent_maintenance'   => $recentMaintenance,
        ];
    }

    private function buildingSnapshot(Building $building, Carbon $from, Carbon $to): array
    {
        $pl = $this->profitLoss->build($from, $to, $building->id);

        $today = Carbon::today();

        $totalUnits    = $building->units()->count();
        $occupiedUnits = $building->occupiedUnits()->count();

        $tenantCount = LeaseContract::whereIn('unit_id', $building->units()->pluck('id'))
            ->whereDate('lease_start_date', '<=', $today)
            ->whereDate('lease_end_date', '>=', $today)
            ->distinct('tenant_id')
            ->count('tenant_id');

        $electricity = (float) EwaBill::where('property_name', $building->property_name)
            ->whereDate('reading_date', '>=', $from)->whereDate('reading_date', '<=', $to)
            ->sum('elec_charges');

        $water = (float) EwaBill::where('property_name', $building->property_name)
            ->whereDate('reading_date', '>=', $from)->whereDate('reading_date', '<=', $to)
            ->sum('water_charges');

        $maintenance = (float) MaintenanceRequest::where('building_id', $building->id)
            ->whereNotNull('approved_dept_head')
            ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)
            ->get()
            ->sum(fn (MaintenanceRequest $r) => $r->selected_quotation ? (float) $r->{"quotation_{$r->selected_quotation}"} : 0.0);

        return [
            'total_income'      => $pl['total_revenue'],
            'net_income'        => $pl['net_profit'],
            'occupancy_percent' => $totalUnits > 0 ? round($occupiedUnits / $totalUnits * 100) : 0,
            'tenant_count'      => $tenantCount,
            'expenses'          => [
                'electricity' => round($electricity, 3),
                'water'       => round($water, 3),
                'maintenance' => round($maintenance, 3),
            ],
        ];
    }
}
