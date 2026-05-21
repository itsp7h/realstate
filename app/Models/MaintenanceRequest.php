<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'date', 'job_order', 'property', 'tenant', 'flat', 'contact_no',
        'available_datetime', 'apartment_status', 'request_date', 'status',
        'supervisor_name', 'supervisor_datetime',
        'job_assessment', 'quotation_1', 'quotation_2', 'quotation_3', 'maintenance_remarks',
        'approved_supervisor', 'approved_dept_head',
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

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open'        => 'blue',
            'in_progress' => 'gold',
            'completed'   => 'green',
            'cancelled'   => 'red',
            default       => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'in_progress' => 'In Progress',
            default       => ucfirst($this->status),
        };
    }
}
