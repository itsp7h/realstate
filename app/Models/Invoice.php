<?php

namespace App\Models;

use App\Support\NumberToWords;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use Auditable;

    protected $fillable = [
        'invoice_number', 'tenant_id', 'tenant_name', 'tenant_code', 'tenant_address',
        'property_name', 'unit', 'type', 'description', 'lines', 'amount',
        'vat_rate', 'vat_amount', 'invoice_date', 'status', 'notes', 'remarks',
    ];

    protected $casts = [
        'lines'        => 'array',
        'amount'       => 'decimal:3',
        'vat_rate'     => 'decimal:2',
        'vat_amount'   => 'decimal:3',
        'invoice_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoiceNotes(): HasMany
    {
        return $this->hasMany(InvoiceNote::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getTotalCreditNotesAttribute(): float
    {
        return (float) $this->invoiceNotes()->where('type', 'credit')->sum('amount');
    }

    public function getTotalDebitNotesAttribute(): float
    {
        return (float) $this->invoiceNotes()->where('type', 'debit')->sum('amount');
    }

    public function getTotalInclVatAttribute(): float
    {
        return (float) $this->amount + (float) $this->vat_amount;
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->total_incl_vat - $this->total_paid - $this->total_credit_notes + $this->total_debit_notes;
    }

    public function getAmountInWordsAttribute(): string
    {
        return NumberToWords::bahrainiDinars($this->total_incl_vat);
    }

    public function getLineCountAttribute(): int
    {
        return count($this->lines ?? []);
    }

    /**
     * The address to print on the invoice: the tenant's own address if one is
     * on file, otherwise the address of the property on the first line
     * (looked up via that line's lease contract, or by property name).
     * Resolved live so older invoices saved before an address was on file
     * still display correctly.
     */
    public function getDisplayAddressAttribute(): ?string
    {
        if (filled($this->tenant_address)) {
            return $this->tenant_address;
        }

        return static::resolveAddressFromLines($this->lines ?? []);
    }

    public static function resolveTenantAddress(Tenant $tenant, array $lines): ?string
    {
        if (filled($tenant->address)) {
            return $tenant->address;
        }

        return static::resolveAddressFromLines($lines);
    }

    private static function resolveAddressFromLines(array $lines): ?string
    {
        $first = $lines[0] ?? null;
        if (! $first) {
            return null;
        }

        $building = null;

        if (! empty($first['lease_contract_id'])) {
            $contract = LeaseContract::find($first['lease_contract_id']);
            if ($contract && $contract->property_code) {
                $building = Building::where('property_code', $contract->property_code)->first();
            }
        }

        if (! $building && ! empty($first['property_name'])) {
            $building = Building::where('property_name', $first['property_name'])->first();
        }

        return $building?->full_address;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'rent'      => 'Rent',
            'utilities' => 'Utilities',
            'other'     => 'Other',
            default     => ucfirst($this->type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'partially_paid' => 'Partially Paid',
            default          => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function auditName(): string
    {
        return $this->invoice_number;
    }

    /**
     * Recompute the amount / vat_amount fields from the current `lines` array.
     * Call this before saving whenever lines or vat_rate change.
     */
    public function recomputeTotals(): void
    {
        $subtotal = collect($this->lines ?? [])->sum(fn ($line) => (float) ($line['amount'] ?? 0));

        $this->amount     = round($subtotal, 3);
        $this->vat_amount = round($subtotal * ((float) $this->vat_rate / 100), 3);
    }

    public static function generateNumber(string $type = 'rent'): string
    {
        $code = match ($type) {
            'rent'      => 'R',
            'utilities' => 'U',
            'other'     => 'O',
            default     => 'X',
        };

        $prefix = "INV-{$code}-" . now()->format('my') . '-';
        $last   = static::where('invoice_number', 'like', $prefix . '%')
                        ->orderByDesc('invoice_number')->value('invoice_number');
        $seq    = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function syncStatus(): void
    {
        if (in_array($this->status, ['cancelled', 'overdue'], true)) return;

        $paid  = $this->total_paid;
        $owing = $this->total_incl_vat - $this->total_credit_notes + $this->total_debit_notes;

        if ($owing <= 0) {
            $status = 'paid';
        } elseif ($paid <= 0) {
            $status = 'issued';
        } elseif ($paid >= $owing) {
            $status = 'paid';
        } else {
            $status = 'partially_paid';
        }

        $this->updateQuietly(['status' => $status]);
    }
}
