<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\PropertyUnit;

class DashboardController extends Controller
{
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

        return view('dashboard', compact('stats', 'recentBuildings', 'recentUnits'));
    }
}
