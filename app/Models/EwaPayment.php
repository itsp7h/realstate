<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EwaPayment extends Model
{
    protected $fillable = [
        'payment_number', 'ewa_bill_id', 'amount', 'payment_date', 'method', 'reference', 'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:3',
        'payment_date' => 'date',
    ];

    public function ewaBill(): BelongsTo
    {
        return $this->belongsTo(EwaBill::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'cash'          => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque'        => 'Cheque',
            'online_card'   => 'Online / Card',
            default         => ucfirst($this->method),
        };
    }

    public static function generateNumber(): string
    {
        $prefix = 'EWAPAY-' . now()->format('Ymd') . '-';
        $last   = static::where('payment_number', 'like', $prefix . '%')
                        ->orderByDesc('payment_number')->value('payment_number');
        $seq    = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
