<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use Auditable;

    public const CATEGORIES = [
        'repairs_maintenance' => 'Repairs & Maintenance',
        'utilities'           => 'Utilities',
        'insurance'           => 'Insurance',
        'municipality_fees'   => 'Municipality Fees',
        'cleaning'            => 'Cleaning',
        'security'            => 'Security',
        'management_fees'     => 'Management Fees',
        'other'               => 'Other',
    ];

    protected $fillable = [
        'building_id', 'unit_id', 'category', 'description',
        'amount', 'expense_date', 'vendor_name', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:3',
        'expense_date' => 'date',
    ];

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    public function auditName(): string
    {
        return "{$this->category_label} — {$this->amount} BHD";
    }
}
