<?php

namespace App\Mcp\Tools;

use App\Models\LeaseContract;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('leasing-status')]
#[Description('Summarises the leasing department: unit occupancy, how many lease contracts are active, and which contracts are expiring soon or recently started, so you can see what the leasing team is working on.')]
class LeasingStatusTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $expiringWithinDays = (int) ($request->get('expiring_within_days') ?? 30);
        $today = Carbon::today();

        $totalUnits    = PropertyUnit::count();
        $occupiedUnits = PropertyUnit::has('activeContract')->count();

        $activeContracts = LeaseContract::where('lease_start_date', '<=', $today)
            ->where('lease_end_date', '>=', $today);

        $expiringSoon = (clone $activeContracts)
            ->whereBetween('lease_end_date', [$today, $today->copy()->addDays($expiringWithinDays)])
            ->orderBy('lease_end_date')
            ->get(['lease_agreement_no', 'tenant_name', 'property_name', 'unit', 'lease_end_date'])
            ->map(fn (LeaseContract $c) => [
                'lease_agreement_no' => $c->lease_agreement_no,
                'tenant_name'        => $c->tenant_name,
                'property_name'      => $c->property_name,
                'unit'               => $c->unit,
                'lease_end_date'     => $c->lease_end_date->format('Y-m-d'),
                'days_remaining'     => $today->diffInDays($c->lease_end_date),
            ]);

        $recentlyStarted = LeaseContract::whereBetween('lease_start_date', [$today->copy()->subDays(30), $today])
            ->orderByDesc('lease_start_date')
            ->get(['lease_agreement_no', 'tenant_name', 'property_name', 'unit', 'lease_start_date'])
            ->map(fn (LeaseContract $c) => [
                'lease_agreement_no' => $c->lease_agreement_no,
                'tenant_name'        => $c->tenant_name,
                'property_name'      => $c->property_name,
                'unit'               => $c->unit,
                'lease_start_date'   => $c->lease_start_date->format('Y-m-d'),
            ]);

        return Response::structured([
            'total_units'          => $totalUnits,
            'occupied_units'       => $occupiedUnits,
            'vacant_units'         => $totalUnits - $occupiedUnits,
            'occupancy_rate_percent' => $totalUnits > 0 ? round(($occupiedUnits / $totalUnits) * 100, 1) : 0,
            'active_lease_contracts' => $activeContracts->count(),
            'total_tenants'        => Tenant::count(),
            'expiring_within_days' => $expiringWithinDays,
            'expiring_soon'        => $expiringSoon,
            'recently_started'     => $recentlyStarted,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'expiring_within_days' => $schema->number()
                ->description('How many days ahead to look for contracts expiring soon. Defaults to 30.')
                ->default(30),
        ];
    }
}
