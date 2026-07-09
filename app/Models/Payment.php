<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use Auditable;

    protected $fillable = [
        'payment_number', 'invoice_id', 'amount', 'payment_date', 'method', 'reference', 'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:3',
        'payment_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'cash'         => 'Cash',
            'bank_transfer'=> 'Bank Transfer',
            'cheque'       => 'Cheque',
            'online_card'  => 'Online / Card',
            default        => ucfirst($this->method),
        };
    }

    public function auditName(): string
    {
        return $this->payment_number;
    }

    public static function generateNumber(): string
    {
        $prefix = 'PAY-' . now()->format('Ymd') . '-';
        $last   = static::where('payment_number', 'like', $prefix . '%')
                        ->orderByDesc('payment_number')->value('payment_number');
        $seq    = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
