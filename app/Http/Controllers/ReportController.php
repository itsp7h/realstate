<?php

namespace App\Http\Controllers;

use App\Exports\VatReturnExport;
use App\Models\Building;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use App\Services\ProfitLossService;
use App\Services\RentScheduleService;
use App\Services\TenantLedgerService;
use App\Services\VatReturnService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(
        private TenantLedgerService $ledger,
        private ProfitLossService $profitLoss,
        private RentScheduleService $rentSchedule,
        private VatReturnService $vatReturn,
    ) {}

    public function index(): View
    {
        return view('reports.index');
    }

    // ── TENANT STATEMENT (Bill-wise) ──────────────────────────────

    public function tenantStatement(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'tenant_code']);

        $tenant = null;
        $rows   = collect();
        if ($tenantId = $request->input('tenant_id')) {
            $tenant = Tenant::findOrFail($tenantId);
            $rows   = $this->ledger->buildLedger($tenant, $from, $to);
        }

        return view('reports.tenant-statement', [
            'tenants' => $tenants,
            'tenant'  => $tenant,
            'rows'    => $rows,
            'from'    => $from,
            'to'      => $to,
            'total'   => $this->ledger->runningBalance($rows),
        ]);
    }

    public function tenantStatementPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenant = Tenant::findOrFail($request->input('tenant_id'));
        $rows   = $this->ledger->buildLedger($tenant, $from, $to);

        $pdf = Pdf::loadView('reports.tenant-statement-pdf', [
            'tenant' => $tenant,
            'rows'   => $rows,
            'from'   => $from,
            'to'     => $to,
            'total'  => $this->ledger->runningBalance($rows),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("statement-{$tenant->tenant_code}.pdf");
    }

    // ── TENANT LEDGER (full history, running balance) ───────────────

    public function tenantLedger(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'tenant_code']);

        $tenant = null;
        $rows   = collect();
        if ($tenantId = $request->input('tenant_id')) {
            $tenant = Tenant::findOrFail($tenantId);
            $rows   = $this->ledger->buildTransactionLedger($tenant, $from, $to);
        }

        return view('reports.tenant-ledger', [
            'tenants' => $tenants,
            'tenant'  => $tenant,
            'rows'    => $rows,
            'from'    => $from,
            'to'      => $to,
        ]);
    }

    public function tenantLedgerPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenant = Tenant::findOrFail($request->input('tenant_id'));
        $rows   = $this->ledger->buildTransactionLedger($tenant, $from, $to);

        $pdf = Pdf::loadView('reports.tenant-ledger-pdf', [
            'tenant' => $tenant,
            'rows'   => $rows,
            'from'   => $from,
            'to'     => $to,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("ledger-{$tenant->tenant_code}.pdf");
    }

    // ── TENANT AGEING ──────────────────────────────────────────────

    public function tenantAgeing(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'tenant_code']);

        $tenant = null;
        $rows   = collect();
        if ($tenantId = $request->input('tenant_id')) {
            $tenant = Tenant::findOrFail($tenantId);
            $rows   = $this->ledger->buildAgeingLedger($tenant, $from, $to);
        }

        return view('reports.tenant-ageing', [
            'tenants' => $tenants,
            'tenant'  => $tenant,
            'rows'    => $rows,
            'from'    => $from,
            'to'      => $to,
        ]);
    }

    public function tenantAgeingPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenant = Tenant::findOrFail($request->input('tenant_id'));
        $rows   = $this->ledger->buildAgeingLedger($tenant, $from, $to);

        $pdf = Pdf::loadView('reports.tenant-ageing-pdf', [
            'tenant' => $tenant,
            'rows'   => $rows,
            'from'   => $from,
            'to'     => $to,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("ageing-{$tenant->tenant_code}.pdf");
    }

    // ── GROUP OUTSTANDING AGEING ────────────────────────────────────

    public function groupAgeing(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $groups = $this->ledger->buildGroupOutstanding($from, $to);

        return view('reports.group-ageing', [
            'groups' => $groups,
            'from'   => $from,
            'to'     => $to,
        ]);
    }

    public function groupAgeingPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);
        $groups = $this->ledger->buildGroupOutstanding($from, $to);

        $pdf = Pdf::loadView('reports.group-ageing-pdf', [
            'groups' => $groups,
            'from'   => $from,
            'to'     => $to,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('group-outstanding-ageing.pdf');
    }

    // ── PROFIT & LOSS ────────────────────────────────────────────────

    public function profitLoss(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $buildingId = $request->input('building_id') ? (int) $request->input('building_id') : null;
        $tenantId   = $request->input('tenant_id') ? (int) $request->input('tenant_id') : null;
        $unitId     = $request->input('unit_id') ? (int) $request->input('unit_id') : null;

        $statement = $this->profitLoss->build($from, $to, $buildingId, $tenantId, $unitId);

        $breakdown = collect();
        if (! $buildingId && ! $tenantId && ! $unitId) {
            $breakdown = $this->profitLoss->byBuilding($from, $to);
        }

        $units = PropertyUnit::orderBy('unit_name')
            ->when($buildingId, fn ($q) => $q->where('building_id', $buildingId))
            ->get(['id', 'unit_name']);

        return view('reports.profit-loss', [
            'buildings'  => Building::orderBy('property_name')->get(['id', 'property_name']),
            'tenants'    => Tenant::orderBy('name')->get(['id', 'name']),
            'units'      => $units,
            'buildingId' => $buildingId,
            'tenantId'   => $tenantId,
            'unitId'     => $unitId,
            'statement'  => $statement,
            'breakdown'  => $breakdown,
            'from'       => $from,
            'to'         => $to,
        ]);
    }

    public function profitLossPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);
        $buildingId = $request->input('building_id') ? (int) $request->input('building_id') : null;
        $tenantId   = $request->input('tenant_id') ? (int) $request->input('tenant_id') : null;
        $unitId     = $request->input('unit_id') ? (int) $request->input('unit_id') : null;

        $statement = $this->profitLoss->build($from, $to, $buildingId, $tenantId, $unitId);
        $building  = $buildingId ? Building::find($buildingId) : null;
        $tenant    = $tenantId ? Tenant::find($tenantId) : null;
        $unit      = $unitId ? PropertyUnit::find($unitId) : null;

        $breakdown = collect();
        if (! $buildingId && ! $tenantId && ! $unitId) {
            $breakdown = $this->profitLoss->byBuilding($from, $to);
        }

        $pdf = Pdf::loadView('reports.profit-loss-pdf', [
            'building'  => $building,
            'tenant'    => $tenant,
            'unit'      => $unit,
            'statement' => $statement,
            'breakdown' => $breakdown,
            'from'      => $from,
            'to'        => $to,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('profit-and-loss.pdf');
    }

    // ── RENT PAYMENT SCHEDULE ────────────────────────────────────────

    public function rentSchedule(Request $request): View
    {
        [$from, $to] = $this->resolveOptionalDateRange($request);
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'tenant_code']);

        $tenant = null;
        $rows   = collect();
        if ($tenantId = $request->input('tenant_id')) {
            $tenant = Tenant::findOrFail($tenantId);
            $rows   = $this->rentSchedule->build($tenant, $from, $to);
        }

        return view('reports.rent-schedule', [
            'tenants' => $tenants,
            'tenant'  => $tenant,
            'rows'    => $rows,
            'from'    => $request->input('date_from'),
            'to'      => $request->input('date_to'),
        ]);
    }

    public function rentSchedulePdf(Request $request): Response
    {
        [$from, $to] = $this->resolveOptionalDateRange($request);
        $tenant = Tenant::findOrFail($request->input('tenant_id'));
        $rows   = $this->rentSchedule->build($tenant, $from, $to);

        $pdf = Pdf::loadView('reports.rent-schedule-pdf', [
            'tenant' => $tenant,
            'rows'   => $rows,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("rent-schedule-{$tenant->tenant_code}.pdf");
    }

    // ── VAT RETURN ───────────────────────────────────────────────────

    public function vatReturn(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $buildingId = $request->input('building_id') ? (int) $request->input('building_id') : null;

        $rows = $this->vatReturn->build($from, $to, $buildingId);

        return view('reports.vat-return', [
            'buildings'  => Building::orderBy('property_name')->get(['id', 'property_name']),
            'buildingId' => $buildingId,
            'building'   => $buildingId ? Building::find($buildingId) : null,
            'rows'       => $rows,
            'totals'     => $this->vatReturn->totals($rows),
            'from'       => $from,
            'to'         => $to,
        ]);
    }

    public function vatReturnExport(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $buildingId = $request->input('building_id') ? (int) $request->input('building_id') : null;
        $building   = $buildingId ? Building::find($buildingId) : null;

        $rows        = $this->vatReturn->build($from, $to, $buildingId);
        $groupedRows = $this->vatReturn->groupByBuilding($rows);

        $filename = 'vat-return-' . ($building ? Str::slug($building->property_name) : 'all') . '-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new VatReturnExport($groupedRows), $filename);
    }

    private function resolveOptionalDateRange(Request $request): array
    {
        $from = $request->input('date_from') ? Carbon::parse($request->input('date_from'))->startOfDay() : null;
        $to   = $request->input('date_to') ? Carbon::parse($request->input('date_to'))->startOfDay() : null;

        return [$from, $to];
    }

    private function resolveDateRange(Request $request): array
    {
        $from = $request->input('date_from')
            ? Carbon::parse($request->input('date_from'))
            : Carbon::now()->startOfYear();

        $to = $request->input('date_to')
            ? Carbon::parse($request->input('date_to'))
            : Carbon::today();

        return [$from->startOfDay(), $to->startOfDay()];
    }
}
