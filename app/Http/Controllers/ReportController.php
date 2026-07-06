<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\TenantLedgerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(private TenantLedgerService $ledger) {}

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

    // ── TENANT AGEING ──────────────────────────────────────────────

    public function tenantAgeing(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'tenant_code']);

        $tenant = null;
        $rows   = collect();
        if ($tenantId = $request->input('tenant_id')) {
            $tenant = Tenant::findOrFail($tenantId);
            $rows   = $this->ledger->buildLedger($tenant, $from, $to);
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
        $rows   = $this->ledger->buildLedger($tenant, $from, $to);

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
