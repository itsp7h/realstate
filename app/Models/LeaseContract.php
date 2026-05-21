<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseContract extends Model
{
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
        'invoicing_frequency',
        'rent_start_date',
        'rent_end_date',
        'rent_per_month',
        'service_frequency',
        'service_start_date',
        'service_end_date',
        'service_amount_bd_excl_vat',
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
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'unit_id');
    }
}
