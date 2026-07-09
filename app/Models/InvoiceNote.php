<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceNote extends Model
{
    use Auditable;

    protected $fillable = [
        'note_number', 'invoice_id', 'type', 'amount', 'note_date', 'reason',
    ];

    protected $casts = [
        'amount'    => 'decimal:3',
        'note_date' => 'date',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'credit' ? 'Credit Note' : 'Debit Note';
    }

    public function auditName(): string
    {
        return $this->note_number;
    }

    public static function generateNumber(string $type): string
    {
        $code   = $type === 'credit' ? 'CN' : 'DN';
        $prefix = "{$code}-" . now()->format('Ymd') . '-';
        $last   = static::where('note_number', 'like', $prefix . '%')
                        ->orderByDesc('note_number')->value('note_number');
        $seq    = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
