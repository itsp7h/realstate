<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRequest extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'date', 'job_order', 'property', 'tenant', 'flat', 'building_id', 'unit_id', 'contact_no',
        'available_datetime', 'apartment_status', 'request_date', 'status',
        'supervisor_name', 'supervisor_datetime', 'supervisor_signature',
        'job_assessment', 'quotation_1', 'quotation_1_file', 'quotation_2', 'quotation_2_file', 'quotation_3', 'quotation_3_file', 'maintenance_remarks',
        'selected_quotation', 'approved_supervisor', 'approved_dept_head', 'dept_head_signature',
        'job_lines',
    ];

    protected $casts = [
        'date'                => 'date',
        'request_date'        => 'date',
        'available_datetime'  => 'datetime',
        'supervisor_datetime' => 'datetime',
        'quotation_1'         => 'decimal:3',
        'quotation_2'         => 'decimal:3',
        'quotation_3'         => 'decimal:3',
        'job_lines'           => 'array',
    ];

    public function auditName(): string
    {
        return $this->job_order ?? "#{$this->getKey()}";
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'unit_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'waiting_supervisor' => 'orange',
            'waiting_approval'   => 'purple',
            'approved'           => 'green',
            'in_progress'        => 'blue',
            'completed'          => 'teal',
            'cancelled'          => 'red',
            default              => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'waiting_supervisor' => 'Pending Assessment',
            'waiting_approval'   => 'Pending Approval',
            'in_progress'        => 'In Progress',
            default              => ucfirst($this->status),
        };
    }

    public function getSelectedQuotationAmountAttribute(): ?string
    {
        if (!$this->selected_quotation) return null;
        $amount = $this->{"quotation_{$this->selected_quotation}"};
        return $amount ? 'BHD ' . number_format($amount, 3) : null;
    }
}
