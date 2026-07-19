<?php

namespace App\Models;

use App\Support\NumberToWords;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EwaBill extends Model
{
    use Auditable;

    protected $fillable = [
        'bill_number', 'lease_contract_id', 'tenant_name', 'property_name', 'unit',
        'ewa_account_number', 'billing_period', 'reading_date', 'reading_type',
        'elec_prev_reading', 'elec_curr_reading', 'elec_consumption', 'elec_charges',
        'water_prev_reading', 'water_curr_reading', 'water_consumption', 'water_charges',
        'ewa_cap', 'tenant_portion', 'total_amount', 'due_date', 'status', 'notes', 'remarks',
    ];

    protected $casts = [
        'elec_prev_reading'  => 'decimal:0',
        'elec_curr_reading'  => 'decimal:0',
        'elec_consumption'   => 'decimal:0',
        'elec_charges'       => 'decimal:3',
        'water_prev_reading' => 'decimal:3',
        'water_curr_reading' => 'decimal:3',
        'water_consumption'  => 'decimal:3',
        'water_charges'      => 'decimal:3',
        'ewa_cap'            => 'decimal:3',
        'tenant_portion'     => 'decimal:3',
        'total_amount'       => 'decimal:3',
        'reading_date'       => 'date',
        'due_date'           => 'date',
    ];

    public function leaseContract(): BelongsTo
    {
        return $this->belongsTo(LeaseContract::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(EwaPayment::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getEffectiveTenantPortionAttribute(): float
    {
        if ($this->tenant_portion !== null) {
            return (float) $this->tenant_portion;
        }
        return (float) $this->total_amount;
    }

    public function getLandlordPortionAttribute(): float
    {
        return max(0.0, (float) $this->total_amount - $this->effective_tenant_portion);
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0.0, $this->effective_tenant_portion - $this->total_paid);
    }

    public function hasCap(): bool
    {
        return $this->ewa_cap !== null && (float) $this->ewa_cap > 0;
    }

    public function getAmountInWordsAttribute(): string
    {
        return NumberToWords::bahrainiDinars($this->effective_tenant_portion);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'partially_paid' => 'Partially Paid',
            default          => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function getReadingTypeLabelAttribute(): string
    {
        return $this->reading_type === 'estimated' ? 'Estimated' : 'Actual';
    }

    public function auditName(): string
    {
        return $this->bill_number;
    }

    public static function generateNumber(): string
    {
        $prefix = 'EWA-' . now()->format('Ymd') . '-';
        $last   = static::where('bill_number', 'like', $prefix . '%')
                        ->orderByDesc('bill_number')->value('bill_number');
        $seq    = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public static function computeTenantPortion(float $total, mixed $cap): float
    {
        if ($cap === null || (float) $cap <= 0) {
            return $total;
        }
        return max(0.0, $total - (float) $cap);
    }

    public static function computeTotal(array $data): float
    {
        return (float) ($data['elec_charges'] ?? 0)
             + (float) ($data['water_charges'] ?? 0);
    }

    public function syncStatus(): void
    {
        if ($this->status === 'cancelled') return;

        $paid  = $this->total_paid;
        $owing = $this->effective_tenant_portion;

        if ($paid <= 0) {
            $status = $this->due_date->isPast() ? 'overdue' : 'issued';
        } elseif ($paid >= $owing) {
            $status = 'paid';
        } else {
            $status = 'partially_paid';
        }

        $this->updateQuietly(['status' => $status]);
    }
}
