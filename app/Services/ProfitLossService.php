<?php

namespace App\Services;

use App\Models\Building;
use App\Models\EwaBill;
use App\Models\EwaPayment;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds a cash-basis profit & loss statement per building and/or tenant.
 * Revenue is recognised when cash is received (Payment / EwaPayment). The
 * expense side has no "paid to EWA authority" / "paid to contractor" event
 * anywhere in the schema, so those legs are recognised on accrual instead —
 * EWA landlord portion on the bill's reading_date, maintenance cost on the
 * request's date once department-head approved.
 */
class ProfitLossService
{
    public function build(Carbon $from, Carbon $to, ?int $buildingId = null, ?int $tenantId = null): array
    {
        $buildingName = $buildingId ? Building::find($buildingId)?->property_name : null;

        $rentCollected      = $this->paymentsByType('rent', $from, $to, $buildingName, $tenantId);
        $utilitiesCollected = $this->paymentsByType('utilities', $from, $to, $buildingName, $tenantId);
        $otherCollected     = $this->paymentsByType('other', $from, $to, $buildingName, $tenantId);
        $ewaCollected       = $this->ewaPayments($from, $to, $buildingName, $tenantId);

        $ewaLandlordExpense  = $this->ewaLandlordExpense($from, $to, $buildingName, $tenantId);
        $maintenanceExpense  = $this->maintenanceExpense($from, $to, $buildingId);

        $revenue = [
            'rent_collected'      => round($rentCollected, 3),
            'utilities_collected' => round($utilitiesCollected, 3),
            'other_collected'     => round($otherCollected, 3),
            'ewa_collected'       => round($ewaCollected, 3),
        ];

        $expenses = [
            'ewa_landlord_expense' => round($ewaLandlordExpense, 3),
            'maintenance_expense'  => round($maintenanceExpense, 3),
        ];

        $totalRevenue = round(array_sum($revenue), 3);
        $totalExpense = round(array_sum($expenses), 3);

        return [
            'revenue'        => $revenue,
            'expenses'       => $expenses,
            'total_revenue'  => $totalRevenue,
            'total_expense'  => $totalExpense,
            'net_profit'     => round($totalRevenue - $totalExpense, 3),
        ];
    }

    public function byBuilding(Carbon $from, Carbon $to, ?int $tenantId = null): Collection
    {
        return Building::orderBy('property_name')->get()
            ->map(fn (Building $building) => array_merge(
                ['building' => $building],
                $this->build($from, $to, $building->id, $tenantId)
            ));
    }

    public function byTenant(Carbon $from, Carbon $to, ?int $buildingId = null): Collection
    {
        return Tenant::orderBy('name')->get()
            ->map(fn (Tenant $tenant) => array_merge(
                ['tenant' => $tenant],
                $this->build($from, $to, $buildingId, $tenant->id)
            ));
    }

    private function paymentsByType(string $type, Carbon $from, Carbon $to, ?string $buildingName, ?int $tenantId): float
    {
        return (float) Payment::whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)
            ->whereHas('invoice', function ($q) use ($type, $buildingName, $tenantId) {
                $q->where('type', $type);
                if ($buildingName !== null) {
                    $q->where('property_name', $buildingName);
                }
                if ($tenantId !== null) {
                    $q->where('tenant_id', $tenantId);
                }
            })
            ->sum('amount');
    }

    private function ewaPayments(Carbon $from, Carbon $to, ?string $buildingName, ?int $tenantId): float
    {
        return (float) EwaPayment::whereDate('payment_date', '>=', $from)->whereDate('payment_date', '<=', $to)
            ->whereHas('ewaBill', function ($q) use ($buildingName, $tenantId) {
                if ($buildingName !== null) {
                    $q->where('property_name', $buildingName);
                }
                if ($tenantId !== null) {
                    $q->whereHas('leaseContract', fn ($lq) => $lq->where('tenant_id', $tenantId));
                }
            })
            ->sum('amount');
    }

    private function ewaLandlordExpense(Carbon $from, Carbon $to, ?string $buildingName, ?int $tenantId): float
    {
        $query = EwaBill::whereDate('reading_date', '>=', $from)->whereDate('reading_date', '<=', $to);

        if ($buildingName !== null) {
            $query->where('property_name', $buildingName);
        }
        if ($tenantId !== null) {
            $query->whereHas('leaseContract', fn ($lq) => $lq->where('tenant_id', $tenantId));
        }

        return (float) $query->get()->sum('landlord_portion');
    }

    private function maintenanceExpense(Carbon $from, Carbon $to, ?int $buildingId): float
    {
        $query = MaintenanceRequest::whereNotNull('approved_dept_head')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        if ($buildingId !== null) {
            $query->where('building_id', $buildingId);
        }

        return $query->get()->sum(fn (MaintenanceRequest $r) => $r->selected_quotation
            ? (float) $r->{"quotation_{$r->selected_quotation}"}
            : 0.0);
    }
}
