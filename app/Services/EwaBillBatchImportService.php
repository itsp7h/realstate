<?php

namespace App\Services;

use App\Models\EwaBill;
use App\Models\EwaBillImportBatch;
use App\Models\PropertyUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Imports a batch of government EWA bill PDFs, matching each one (by EWA
 * account number) against the most recent EwaBill already on file for that
 * account to inherit the property/unit/tenant/cap it belongs to — the bill
 * itself is addressed to the landlord entity, not the tenant, so it can't
 * supply that roster data on its own.
 */
class EwaBillBatchImportService
{
    public function __construct(private EwaBillParser $parser) {}

    public function importMany(array $files): string
    {
        $rows = array_map(fn (UploadedFile $file) => $this->importFile($file), $files);

        $batchId = (string) Str::uuid();
        EwaBillImportBatch::create(['batch_id' => $batchId, 'rows' => $rows]);

        return $batchId;
    }

    public function getBatch(string $batchId): ?array
    {
        return EwaBillImportBatch::where('batch_id', $batchId)->first()?->rows;
    }

    public function importFile(UploadedFile $file): array
    {
        $parsed = $this->parser->parse($file->getRealPath());

        $validator = Validator::make($parsed, [
            'ewa_account_number' => ['required', 'string', 'max:50'],
            'billing_period'     => ['required', 'string', 'max:30'],
            'due_date'           => ['required', 'date'],
            'reading_type'       => ['required', 'in:actual,estimated'],
            'elec_charges'       => ['nullable', 'numeric', 'min:0'],
            'water_charges'      => ['nullable', 'numeric', 'min:0'],
            'elec_prev_reading'  => ['nullable', 'numeric', 'min:0'],
            'elec_curr_reading'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $base = ['file' => $file->getClientOriginalName()];

        if ($validator->fails() || ($parsed['elec_charges'] === null && $parsed['water_charges'] === null)) {
            $error = $validator->fails()
                ? implode(' ', $validator->errors()->all())
                : 'Could not read electricity or water charges from this PDF.';

            return $base + $this->emptyRow() + ['status' => 'failed', 'error' => $error];
        }

        $roster = $this->resolveRoster($parsed['ewa_account_number']);

        $elecCharges = (float) ($parsed['elec_charges'] ?? 0);
        $waterCharges = (float) ($parsed['water_charges'] ?? 0);
        $totalEwa    = EwaBill::computeTotal(['elec_charges' => $elecCharges, 'water_charges' => $waterCharges]);
        $cap         = $roster['ewa_cap'];
        $payable     = EwaBill::computeTenantPortion($totalEwa, $cap);
        $municipalityFee = (float) ($parsed['municipality_fee'] ?? 0);
        $sanitaryFee = (float) ($parsed['sanitary_fee'] ?? 0);
        $arrears     = (float) ($parsed['arrears'] ?? 0);
        $payableExclArrears = $payable + $municipalityFee + $sanitaryFee;
        $payableInclArrears = $payableExclArrears + $arrears;

        $fields = [
            'lease_contract_id'  => $roster['lease_contract_id'],
            'tenant_name'        => $roster['tenant_name'] ?? $parsed['account_holder_name'] ?? 'Unknown Tenant',
            'property_name'      => $roster['property_name'],
            'address'            => $parsed['address_raw'],
            'unit'               => $roster['unit'] ?? $parsed['flat_building_raw'],
            'ewa_account_number' => $parsed['ewa_account_number'],
            'billing_period'     => $parsed['billing_period'],
            'reading_date'       => $parsed['current_reading_date'],
            'reading_type'       => $parsed['reading_type'],
            'elec_prev_reading'  => $parsed['elec_prev_reading'],
            'elec_curr_reading'  => $parsed['elec_curr_reading'],
            'elec_consumption'   => $parsed['elec_consumption'],
            'elec_charges'       => $elecCharges,
            'water_prev_reading' => null,
            'water_curr_reading' => null,
            'water_consumption'  => $parsed['water_consumption'],
            'water_charges'      => $waterCharges,
            'ewa_cap'            => $cap,
            'total_amount'       => $totalEwa,
            'tenant_portion'     => $payable,
            'due_date'           => $parsed['due_date'],
        ];

        $existing = EwaBill::where('ewa_account_number', $parsed['ewa_account_number'])
            ->where('billing_period', $parsed['billing_period'])
            ->first();

        if ($existing) {
            $existing->update($fields);
            $existing->syncStatus();
            $bill   = $existing;
            $status = 'updated';
        } else {
            $fields['bill_number'] = EwaBill::generateNumber();
            $fields['status']      = 'issued';
            $bill   = EwaBill::create($fields);
            $status = 'created';
        }

        return $base + [
            'status'                    => $status,
            'bill_id'                   => $bill->id,
            'bill_number'               => $bill->bill_number,
            'property_name'             => $bill->property_name,
            'address'                   => $bill->address,
            'unit'                      => $bill->unit,
            'tenant_name'               => $bill->tenant_name,
            'ewa_account_number'        => $bill->ewa_account_number,
            'previous_reading_date'     => $parsed['previous_reading_date'],
            'current_reading_date'      => $parsed['current_reading_date'],
            'previous_reading_type'     => $parsed['previous_reading_type'],
            'current_reading_type'      => $parsed['current_reading_type'],
            'elec_charges'              => $elecCharges,
            'water_charges'             => $waterCharges,
            'total_ewa'                 => $totalEwa,
            'ewa_cap'                   => $cap,
            'payable_by_tenant'         => $payable,
            'municipality_fee'          => $municipalityFee,
            'sanitary_fee'              => $sanitaryFee,
            'arrears'                   => $arrears,
            'payable_excl_arrears'      => $payableExclArrears,
            'payable_incl_arrears'      => $payableInclArrears,
            'remarks'                   => $roster['matched'] ? null : 'New account — tenant/property/cap not on file yet, please confirm manually.',
            'error'                     => null,
        ];
    }

    /**
     * Resolves the property/unit/tenant/cap for an EWA account number,
     * preferring the Unit roster (property_units.electricity_ac_no — the
     * account number recorded against the actual flat) since it gives the
     * real property name and unit rather than whatever text happened to be
     * printed on the bill's landlord-addressed letterhead. Falls back to the
     * most recent EwaBill already on file for the account when no unit has
     * that account number recorded yet.
     */
    private function resolveRoster(?string $accountNumber): array
    {
        if (! $accountNumber) {
            return $this->unmatchedRoster();
        }

        return $this->resolveFromUnit($accountNumber) ?? $this->resolveFromPreviousBill($accountNumber) ?? $this->unmatchedRoster();
    }

    private function resolveFromUnit(string $accountNumber): ?array
    {
        $unit = PropertyUnit::where('electricity_ac_no', $accountNumber)->first();

        if (! $unit) {
            return null;
        }

        $lease = $unit->activeContract;
        $cap   = $lease?->ewa_cap;

        return [
            'matched'           => true,
            'lease_contract_id' => $lease?->id,
            'tenant_name'       => $lease?->tenant_name,
            'property_name'     => $unit->property_name,
            'unit'              => $this->formatUnitLabel($unit),
            'ewa_cap'           => $cap !== null ? (float) $cap : null,
        ];
    }

    private function resolveFromPreviousBill(string $accountNumber): ?array
    {
        $previous = EwaBill::where('ewa_account_number', $accountNumber)->latest('due_date')->first();

        if (! $previous) {
            return null;
        }

        $cap = $previous->leaseContract?->ewa_cap ?? $previous->ewa_cap;

        return [
            'matched'           => true,
            'lease_contract_id' => $previous->lease_contract_id,
            'tenant_name'       => $previous->tenant_name,
            'property_name'     => $previous->property_name,
            'unit'              => $previous->unit,
            'ewa_cap'           => $cap !== null ? (float) $cap : null,
        ];
    }

    private function unmatchedRoster(): array
    {
        return [
            'matched'           => false,
            'lease_contract_id' => null,
            'tenant_name'       => null,
            'property_name'     => null,
            'unit'              => null,
            'ewa_cap'           => null,
        ];
    }

    // "MP1 - 11" -> "Flat 11"; non-numeric suffixes ("Common Area", "R/T")
    // are left as-is rather than forced into a "Flat ..." label that doesn't fit.
    private function formatUnitLabel(PropertyUnit $unit): string
    {
        $suffix = trim(str_replace($unit->property_code . ' - ', '', $unit->unit_name));

        return ctype_digit($suffix) ? "Flat {$suffix}" : $suffix;
    }

    private function emptyRow(): array
    {
        return [
            'bill_id'               => null,
            'bill_number'           => null,
            'property_name'         => null,
            'address'               => null,
            'unit'                  => null,
            'tenant_name'           => null,
            'ewa_account_number'    => null,
            'previous_reading_date' => null,
            'current_reading_date'  => null,
            'previous_reading_type' => null,
            'current_reading_type'  => null,
            'elec_charges'          => null,
            'water_charges'         => null,
            'total_ewa'             => null,
            'ewa_cap'               => null,
            'payable_by_tenant'     => null,
            'municipality_fee'      => null,
            'sanitary_fee'          => null,
            'arrears'               => null,
            'payable_excl_arrears'  => null,
            'payable_incl_arrears'  => null,
            'remarks'               => null,
        ];
    }
}
