<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
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
        ];

        $recentBuildings = Building::latest()->limit(5)->get();
        $recentUnits     = PropertyUnit::with('building', 'floor')->latest()->limit(5)->get();

        $chartYear  = Carbon::today()->year;
        $chartData  = $this->analytics->monthlySeries($chartYear);
        $buildingPerformance = $this->analytics->buildingPerformance(
            Carbon::today()->startOfMonth(),
            Carbon::today()->endOfMonth()
        );

        return view('dashboard', compact(
            'stats', 'recentBuildings', 'recentUnits', 'chartYear', 'chartData', 'buildingPerformance'
        ));
    }
}
