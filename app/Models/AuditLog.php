<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'action', 'entity_type', 'entity_id', 'entity_name', 'changes', 'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public static function record(
        string $action,
        string $entityType,
        ?int $entityId,
        ?string $entityName,
        ?array $changes = null
    ): void {
        try {
            static::create([
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'entity_name' => $entityName,
                'changes'     => $changes,
                'ip_address'  => request()->ip(),
            ]);
        } catch (\Throwable) {
            // Never let audit logging break the main flow
        }
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created'  => 'green',
            'updated'  => 'blue',
            'deleted'  => 'red',
            'imported' => 'gold',
            default    => 'gray',
        };
    }
}
