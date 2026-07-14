<?php

namespace App\Services;

use App\Models\Building;
use App\Models\EwaBill;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the invoice-level VAT return schedule for a building and date
 * range — every rent invoice and EWA bill, in the same shape the
 * quarterly NBR VAT return workbook expects (invoice date, customer,
 * description, taxable amount, VAT amount, total, tax code).
 *
 * EWA bills carry no VAT anywhere in this system, so they're always
 * treated as exempt (EXM-S) here, matching how they're already handled
 * everywhere else in the app (ProfitLossService, TenantLedgerService).
 */
class VatReturnService
{
    public function build(Carbon $from, Carbon $to, ?int $buildingId = null): Collection
    {
        $buildingName = $buildingId ? Building::find($buildingId)?->property_name : null;

        $invoiceRows = Invoice::whereBetween('invoice_date', [$from, $to])
            ->when($buildingName !== null, fn ($q) => $q->where('property_name', $buildingName))
            ->orderBy('invoice_date')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'building_name'   => $invoice->property_name,
                'invoice_date'    => $invoice->invoice_date,
                'date_of_supply'  => $invoice->invoice_date,
                'reference'       => $invoice->invoice_number,
                'customer_name'   => $invoice->tenant_name,
                'description'     => $this->invoiceDescription($invoice),
                'taxable_amount'  => (float) $invoice->amount,
                'vat_amount'      => (float) $invoice->vat_amount,
                'total_incl_vat'  => (float) $invoice->total_incl_vat,
                'tax_code'        => (float) $invoice->vat_rate > 0 ? 'S' : 'EXM-S',
                'place_of_supply' => 'Bahrain',
            ]);

        $ewaRows = EwaBill::whereBetween('reading_date', [$from, $to])
            ->when($buildingName !== null, fn ($q) => $q->where('property_name', $buildingName))
            ->orderBy('reading_date')
            ->get()
            ->map(fn (EwaBill $bill) => [
                'building_name'   => $bill->property_name,
                'invoice_date'    => $bill->reading_date,
                'date_of_supply'  => $bill->reading_date,
                'reference'       => $bill->bill_number,
                'customer_name'   => $bill->tenant_name,
                'description'     => 'EWA — ' . ($bill->billing_period ?: $bill->reading_date?->format('M Y')),
                'taxable_amount'  => (float) $bill->effective_tenant_portion,
                'vat_amount'      => 0.0,
                'total_incl_vat'  => (float) $bill->effective_tenant_portion,
                'tax_code'        => 'EXM-S',
                'place_of_supply' => 'Bahrain',
            ]);

        return $invoiceRows->concat($ewaRows)
            ->sortBy('invoice_date')
            ->values();
    }

    /**
     * Groups rows by building name for the multi-tab XLSX export — one
     * group per property present in the result set, sorted alphabetically,
     * with rows lacking a recognisable property name bucketed under
     * "Unassigned" rather than dropped.
     */
    public function groupByBuilding(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn ($row) => $row['building_name'] ?: 'Unassigned')
            ->sortKeys();
    }

    public function totals(Collection $rows): array
    {
        return [
            'taxable_amount' => round($rows->sum('taxable_amount'), 3),
            'vat_amount'     => round($rows->sum('vat_amount'), 3),
            'total_incl_vat' => round($rows->sum('total_incl_vat'), 3),
        ];
    }

    private function invoiceDescription(Invoice $invoice): string
    {
        return $invoice->type_label . ' for ' . $invoice->invoice_date->format('M y');
    }
}
