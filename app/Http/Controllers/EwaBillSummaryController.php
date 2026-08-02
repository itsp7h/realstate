<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Services\EwaBillBatchImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class EwaBillSummaryController extends Controller
{
    public function __construct(private EwaBillBatchImportService $batchImport) {}

    public function create(): View
    {
        return view('ewa-bills.summary-upload');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'mimes:pdf', 'max:10240'],
        ], [
            'files.required' => 'Please select at least one EWA bill PDF to upload.',
            'files.*.mimes'  => 'Only PDF files are accepted.',
            'files.*.max'    => 'Each PDF must be under 10 MB.',
        ]);

        $batchId = $this->batchImport->importMany($request->file('files'));

        return redirect()->route('ewa-bills.summary.show', $batchId);
    }

    public function show(string $batch): View
    {
        $rows = $this->batchImport->getBatch($batch);

        abort_if($rows === null, 404, 'This batch has expired or does not exist.');

        $succeeded = collect($rows)->whereIn('status', ['created', 'updated'])->count();
        $failed    = collect($rows)->where('status', 'failed')->count();

        return view('ewa-bills.summary-results', [
            'batch'     => $batch,
            'rows'      => $rows,
            'succeeded' => $succeeded,
            'failed'    => $failed,
        ]);
    }

    public function export(string $batch)
    {
        $rows = $this->batchImport->getBatch($batch);

        abort_if($rows === null, 404, 'This batch has expired or does not exist.');

        $headings = [
            'File', 'Result', 'Property Name', 'Address', 'Flat No', 'Tenant Name', 'Account No',
            'Previous reading date', 'Previous Reading Type', 'Current reading date', 'Current Reading Type',
            'electricity', 'water', 'total ewa',
            'Monthly CAP', 'Payable by Tenant', 'Municipal Tax as per bill', 'Sanitary Fee', 'Arrears',
            'Total Payable (Excl. arrears)', 'Total Payable (Incl. arrears)', 'Remarks',
        ];

        $mapper = fn (array $row) => [
            $row['file'],
            match ($row['status']) {
                'created' => 'Created',
                'updated' => 'Updated',
                default   => 'Failed',
            },
            $row['property_name'],
            $row['address'] ?? null,
            $row['unit'],
            $row['tenant_name'],
            $row['ewa_account_number'],
            $row['previous_reading_date'],
            ($row['previous_reading_type'] ?? null) ? ucfirst($row['previous_reading_type']) : null,
            $row['current_reading_date'],
            ($row['current_reading_type'] ?? null) ? ucfirst($row['current_reading_type']) : null,
            $row['elec_charges'],
            $row['water_charges'],
            $row['total_ewa'],
            $row['ewa_cap'],
            $row['payable_by_tenant'],
            $row['municipality_fee'],
            $row['sanitary_fee'],
            $row['arrears'],
            $row['payable_excl_arrears'],
            $row['payable_incl_arrears'],
            $row['remarks'] ?? $row['error'],
        ];

        return Excel::download(
            new ReportExport(new Collection($rows), $headings, $mapper, 'EWA Summary'),
            'ewa-summary-' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
