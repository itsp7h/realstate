<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Exports\VatReturnExport;
use App\Models\Building;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use App\Services\CollectionReportService;
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
        private CollectionReportService $collectionReport,
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

    public function tenantStatementExport(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenant = Tenant::findOrFail($request->input('tenant_id'));
        $rows   = $this->ledger->buildLedger($tenant, $from, $to);

        return Excel::download(
            new ReportExport($rows, $this->ledgerRowHeadings(), $this->ledgerRowMapper(), 'Tenant Statement'),
            "statement-{$tenant->tenant_code}.xlsx"
        );
    }

    // ── BILL-WISE STATEMENT (one row per bill, netted) ──────────────

    public function billWiseStatement(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenants = Tenant::orderBy('name')->get(['id', 'name', 'tenant_code']);

        $tenant = null;
        $rows   = collect();
        if ($tenantId = $request->input('tenant_id')) {
            $tenant = Tenant::findOrFail($tenantId);
            $rows   = $this->ledger->buildAgeingLedger($tenant, $from, $to);
        }

        return view('reports.bill-wise-statement', [
            'tenants' => $tenants,
            'tenant'  => $tenant,
            'rows'    => $rows,
            'from'    => $from,
            'to'      => $to,
            'total'   => $this->ledger->runningBalance($rows),
        ]);
    }

    public function billWiseStatementPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenant = Tenant::findOrFail($request->input('tenant_id'));
        $rows   = $this->ledger->buildAgeingLedger($tenant, $from, $to);

        $pdf = Pdf::loadView('reports.bill-wise-statement-pdf', [
            'tenant' => $tenant,
            'rows'   => $rows,
            'from'   => $from,
            'to'     => $to,
            'total'  => $this->ledger->runningBalance($rows),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("bill-wise-statement-{$tenant->tenant_code}.pdf");
    }

    public function billWiseStatementExport(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenant = Tenant::findOrFail($request->input('tenant_id'));
        $rows   = $this->ledger->buildAgeingLedger($tenant, $from, $to);

        return Excel::download(
            new ReportExport($rows, $this->ledgerRowHeadings(), $this->ledgerRowMapper(), 'Bill-wise Statement'),
            "bill-wise-statement-{$tenant->tenant_code}.xlsx"
        );
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

    public function tenantLedgerExport(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenant = Tenant::findOrFail($request->input('tenant_id'));
        $rows   = $this->ledger->buildTransactionLedger($tenant, $from, $to);

        $headings = ['Date', 'Bill Ref', 'Description', 'Debit (BHD)', 'Credit (BHD)', 'Balance (BHD)'];
        $mapper = fn ($row) => [
            Carbon::parse($row['date'])->format('Y-m-d'),
            $row['bill_ref'],
            $row['description'],
            $row['debit'],
            $row['credit'],
            $row['balance'],
        ];

        return Excel::download(
            new ReportExport($rows, $headings, $mapper, 'Tenant Ledger'),
            "ledger-{$tenant->tenant_code}.xlsx"
        );
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

    public function tenantAgeingExport(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $tenant = Tenant::findOrFail($request->input('tenant_id'));
        $rows   = $this->ledger->buildAgeingLedger($tenant, $from, $to);

        return Excel::download(
            new ReportExport($rows, $this->ledgerRowHeadings(), $this->ledgerRowMapper(), 'Tenant Ageing'),
            "ageing-{$tenant->tenant_code}.xlsx"
        );
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

    public function groupAgeingExport(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $groups = $this->ledger->buildGroupOutstanding($from, $to);

        $headings = ['Tenant', 'Pending Bills (BHD)', '< 60 Days', '60-120 Days', '> 120 Days', 'On Account'];
        $mapper = fn ($g) => [
            $g['tenant']->name,
            $g['pending'],
            $g['lt60'],
            $g['b60_120'],
            $g['gt120'],
            -$g['on_account'],
        ];

        return Excel::download(
            new ReportExport($groups, $headings, $mapper, 'Group Ageing'),
            'group-outstanding-ageing-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // ── FINANCIAL SUMMARY (all tenants, date range) ─────────────────

    public function financialSummary(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $rows = $this->ledger->buildFinancialSummaryReport($from, $to);

        return view('reports.financial-summary', [
            'rows' => $rows,
            'from' => $from,
            'to'   => $to,
        ]);
    }

    public function financialSummaryPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);
        $rows = $this->ledger->buildFinancialSummaryReport($from, $to);

        $pdf = Pdf::loadView('reports.financial-summary-pdf', [
            'rows' => $rows,
            'from' => $from,
            'to'   => $to,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('tenant-financial-summary.pdf');
    }

    public function financialSummaryExport(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $rows = $this->ledger->buildFinancialSummaryReport($from, $to);

        $headings = ['Tenant', 'Opening Balance (BHD)', 'Amount (BHD)', 'Received Amount (BHD)', 'Net Balance (BHD)'];
        $mapper = fn ($r) => [
            $r['tenant']->name,
            $r['opening_balance'],
            $r['period_amount'],
            -$r['period_received'],
            $r['net_balance'],
        ];

        return Excel::download(
            new ReportExport($rows, $headings, $mapper, 'Financial Summary'),
            'tenant-financial-summary-' . now()->format('Y-m-d') . '.xlsx'
        );
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

    public function profitLossExport(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $buildingId = $request->input('building_id') ? (int) $request->input('building_id') : null;
        $tenantId   = $request->input('tenant_id') ? (int) $request->input('tenant_id') : null;
        $unitId     = $request->input('unit_id') ? (int) $request->input('unit_id') : null;

        if (! $buildingId && ! $tenantId && ! $unitId) {
            $rows = $this->profitLoss->byBuilding($from, $to)
                ->map(fn ($row) => array_merge(['label' => $row['building']->property_name], $row));
        } else {
            $label = $buildingId
                ? Building::find($buildingId)?->property_name
                : ($tenantId ? Tenant::find($tenantId)?->name : PropertyUnit::find($unitId)?->unit_name);

            $rows = collect([array_merge(
                ['label' => $label ?? 'All'],
                $this->profitLoss->build($from, $to, $buildingId, $tenantId, $unitId)
            )]);
        }

        $headings = [
            'Building / Scope', 'Rent Collected (BHD)', 'Utilities Collected (BHD)', 'Other Collected (BHD)',
            'EWA Collected (BHD)', 'Total Revenue (BHD)', 'EWA Landlord Expense (BHD)', 'Maintenance Expense (BHD)',
            'Total Expense (BHD)', 'Net Profit (BHD)',
        ];
        $mapper = fn ($row) => [
            $row['label'],
            $row['revenue']['rent_collected'],
            $row['revenue']['utilities_collected'],
            $row['revenue']['other_collected'],
            $row['revenue']['ewa_collected'],
            $row['total_revenue'],
            $row['expenses']['ewa_landlord_expense'],
            $row['expenses']['maintenance_expense'],
            $row['total_expense'],
            $row['net_profit'],
        ];

        return Excel::download(
            new ReportExport($rows, $headings, $mapper, 'Profit & Loss'),
            'profit-and-loss-' . now()->format('Y-m-d') . '.xlsx'
        );
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

    public function rentScheduleExport(Request $request)
    {
        [$from, $to] = $this->resolveOptionalDateRange($request);
        $tenant = Tenant::findOrFail($request->input('tenant_id'));
        $rows   = $this->rentSchedule->build($tenant, $from, $to);

        $headings = ['Month', 'Invoiced (BHD)', 'Received (BHD)', 'Remaining (BHD)', 'Status'];
        $mapper = fn ($row) => [
            $row['month']->format('F Y'),
            $row['invoiced'],
            $row['paid'],
            $row['remaining'],
            match ($row['status']) {
                'paid'         => 'Received',
                'partial'      => 'Partially Received',
                'unpaid'       => 'Unpaid',
                'not_invoiced' => 'Not Invoiced',
            },
        ];

        return Excel::download(
            new ReportExport($rows, $headings, $mapper, 'Rent Schedule'),
            "rent-schedule-{$tenant->tenant_code}.xlsx"
        );
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

    public function vatReturnPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);
        $buildingId = $request->input('building_id') ? (int) $request->input('building_id') : null;
        $building   = $buildingId ? Building::find($buildingId) : null;

        $rows = $this->vatReturn->build($from, $to, $buildingId);

        $pdf = Pdf::loadView('reports.vat-return-pdf', [
            'building' => $building,
            'rows'     => $rows,
            'totals'   => $this->vatReturn->totals($rows),
            'from'     => $from,
            'to'       => $to,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('vat-return.pdf');
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

    // ── COLLECTION REPORT (rent + EWA payments received) ─────────────

    public function collection(Request $request): View
    {
        [$from, $to] = $this->resolveDateRange($request);
        $rows = $this->collectionReport->build($from, $to);

        return view('reports.collection', [
            'rows'  => $rows,
            'from'  => $from,
            'to'    => $to,
            'total' => $this->collectionReport->total($rows),
        ]);
    }

    public function collectionPdf(Request $request): Response
    {
        [$from, $to] = $this->resolveDateRange($request);
        $rows = $this->collectionReport->build($from, $to);

        $pdf = Pdf::loadView('reports.collection-pdf', [
            'rows'  => $rows,
            'from'  => $from,
            'to'    => $to,
            'total' => $this->collectionReport->total($rows),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('collection-report.pdf');
    }

    public function collectionExport(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $rows = $this->collectionReport->build($from, $to);

        $headings = ['Receipt No', 'Date', 'Cheque No', 'Cheque Date', 'Tenant / Ledger Name', 'Particulars', 'Amount (BHD)'];
        $mapper = fn ($row) => [
            $row['receipt_no'],
            $row['date']->format('Y-m-d'),
            $row['cheque_number'],
            $row['cheque_date']?->format('Y-m-d'),
            $row['tenant_name'],
            $row['particulars'],
            $row['amount'],
        ];

        return Excel::download(
            new ReportExport($rows, $headings, $mapper, 'Collection Report'),
            'collection-report-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /**
     * Shared column set for the outstanding-bill row shape produced by
     * TenantLedgerService::buildLedger() / buildAgeingLedger() — used by
     * the Tenant Statement, Bill-wise Statement, and Tenant Ageing exports.
     */
    private function ledgerRowHeadings(): array
    {
        return ['Date', 'Bill Ref', 'Description', 'Opening Amount (BHD)', 'Pending Amount (BHD)', 'Due Date', 'Days Overdue', 'Bucket'];
    }

    private function ledgerRowMapper(): \Closure
    {
        return fn ($row) => [
            Carbon::parse($row['date'])->format('Y-m-d'),
            $row['bill_ref'],
            $row['description'],
            $row['opening_amount'],
            $row['pending_amount'],
            Carbon::parse($row['due_on'])->format('Y-m-d'),
            $row['overdue_days'],
            $row['bucket'],
        ];
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
