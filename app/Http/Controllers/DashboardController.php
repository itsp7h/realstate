<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\PropertyUnit;
use App\Services\DashboardAnalyticsService;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(private DashboardAnalyticsService $analytics)
    {
    }

    public function index()
    {
        $stats = [
            'buildings' => Building::count(),
            'floors'    => Floor::count(),
            'units'     => PropertyUnit::count(),
            'furnished' => PropertyUnit::where('unit_condition', 'Furnished')->count(),
            'fitted'    => PropertyUnit::where('unit_condition', 'Fitted')->count(),
            'occupied'  => PropertyUnit::whereHas('activeContract')->count(),
        ];

        $recentBuildings = Building::latest()->limit(5)->get();
        $recentUnits     = PropertyUnit::with('building', 'floor')->latest()->limit(5)->get();
        $recentActivity  = AuditLog::latest()->limit(3)->get();

        $chartYear  = Carbon::today()->year;
        $chartData  = $this->analytics->monthlySeries($chartYear);
        $buildingPerformance = $this->analytics->buildingPerformance(
            Carbon::today()->startOfMonth(),
            Carbon::today()->endOfMonth()
        );

        $today = Carbon::today();

        $billedThisMonth = (float) Invoice::whereMonth('invoice_date', $today->month)
            ->whereYear('invoice_date', $today->year)
            ->get()
            ->sum(fn (Invoice $i) => $i->total_incl_vat);

        $collectedThisMonth = (float) Payment::whereMonth('payment_date', $today->month)
            ->whereYear('payment_date', $today->year)
            ->sum('amount');

        $outstanding = (float) Invoice::whereIn('status', ['issued', 'partially_paid', 'overdue'])
            ->get()
            ->sum(fn (Invoice $i) => $i->balance_due);

        $overdueTenantCount = Invoice::where('status', 'overdue')->distinct('tenant_id')->count('tenant_id');
        $openMaintenanceCount = MaintenanceRequest::whereNotIn('status', ['completed', 'cancelled'])->count();
        $expiringLeaseCount = LeaseContract::all()->filter(fn (LeaseContract $c) => $c->status === 'expiring')->count();

        $portfolioMetrics = [
            'billed'          => $billedThisMonth,
            'collected'       => $collectedThisMonth,
            'collectedPct'    => $billedThisMonth > 0 ? min(100, round($collectedThisMonth / $billedThisMonth * 100)) : 0,
            'outstanding'     => $outstanding,
            'overdueCount'    => $overdueTenantCount,
            'openMaintenance' => $openMaintenanceCount,
            'expiringLeases'  => $expiringLeaseCount,
            'occupancyPct'    => $stats['units'] > 0 ? round($stats['occupied'] / $stats['units'] * 100) : 0,
        ];

        return view('dashboard', compact(
            'stats', 'recentBuildings', 'recentUnits', 'recentActivity', 'chartYear', 'chartData',
            'buildingPerformance', 'portfolioMetrics'
        ));
    }
}
