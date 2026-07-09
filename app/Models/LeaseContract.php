<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class LeaseContract extends Model
{
    use Auditable;
    protected $fillable = [
        'date',
        'lease_agreement_no',
        'tenant_id',
        'tenant_name',
        'property_name',
        'property_code',
        'block_name',
        'block_code',
        'floor_name',
        'floor_code',
        'unit_id',
        'unit',
        'description',
        'lease_start_date',
        'lease_end_date',
        'lease_break_date',
        'notice_period',
        'rental_income_ledger',
        'currency',
        'security_deposit',
        'ewa_cap',
        'invoicing_frequency',
        'rent_start_date',
        'rent_end_date',
        'rent_per_month',
        'service_frequency',
        'service_start_date',
        'service_end_date',
        'service_amount_bd_excl_vat',
        'vat_enabled',
        'vat_rate',
    ];

    protected $casts = [
        'date'               => 'date',
        'lease_start_date'   => 'date',
        'lease_end_date'     => 'date',
        'lease_break_date'   => 'date',
        'rent_start_date'    => 'date',
        'rent_end_date'      => 'date',
        'service_start_date' => 'date',
        'service_end_date'   => 'date',
        'ewa_cap'            => 'decimal:3',
        'vat_enabled'        => 'boolean',
        'vat_rate'           => 'decimal:2',
    ];

    public function getEffectiveVatRateAttribute(): float
    {
        return $this->vat_enabled ? (float) $this->vat_rate : 0.0;
    }

    protected function status(): Attribute
    {
        return Attribute::make(get: function () {
            $today = Carbon::today();
            if ($this->lease_end_date < $today) return 'expired';
            if ($this->lease_start_date > $today) return 'upcoming';
            if ($this->lease_end_date <= $today->copy()->addDays(30)) return 'expiring';
            return 'active';
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'unit_id');
    }

    public static function generateNumber(): string
    {
        $prefix = 'LA-' . now()->year . '-';
        $last   = static::where('lease_agreement_no', 'like', $prefix . '%')
                        ->orderByDesc('lease_agreement_no')->value('lease_agreement_no');
        $seq    = $last ? (int) substr($last, -3) + 1 : 1;
        return $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
