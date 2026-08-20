<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    /**
     * Every action the log can hold, in the order the filter offers them:
     * record changes first, then the authentication events.
     *
     * Kept here rather than in the view because two places need the list (the
     * filter dropdown and the controller's counts), and a fifth action added
     * to one but not the other is a filter that silently cannot find rows.
     *
     * @var list<string>
     */
    public const ACTIONS = [
        'created', 'updated', 'deleted', 'imported',
        'signed_in', 'signed_out', 'sign_in_failed', 'locked_out',
        'password_reset_requested', 'password_reset', 'leases_expiring',
    ];

    /** Authentication events are not about a record, so they share one type. */
    public const AUTH = 'Auth';

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
            'created', 'signed_in',
            'password_reset'                   => 'green',
            'updated', 'password_reset_requested' => 'blue',
            'leases_expiring'                  => 'amber',
            'deleted', 'sign_in_failed',
            'locked_out'                       => 'red',
            'imported'                         => 'gold',
            default                            => 'gray',
        };
    }

    /**
     * Human label. The auth actions are snake_case because they are also the
     * filter's query value, so they need spelling out rather than ucfirst()
     * turning 'sign_in_failed' into 'Sign_in_failed'.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'signed_in'                => 'Signed in',
            'password_reset_requested' => 'Reset requested',
            'password_reset'           => 'Password reset',
            'leases_expiring'          => 'Leases ending',
            'signed_out'     => 'Signed out',
            'sign_in_failed' => 'Sign-in failed',
            'locked_out'     => 'Locked out',
            default          => ucfirst($this->action),
        };
    }

    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'created'        => 'fa-plus',
            'updated'        => 'fa-pen',
            'deleted'        => 'fa-trash',
            'imported'       => 'fa-file-import',
            'signed_in'                => 'fa-right-to-bracket',
            'password_reset_requested' => 'fa-paper-plane',
            'password_reset'           => 'fa-key',
            'leases_expiring'          => 'fa-calendar-xmark',
            'signed_out'     => 'fa-right-from-bracket',
            'sign_in_failed' => 'fa-triangle-exclamation',
            'locked_out'     => 'fa-lock',
            default          => 'fa-circle',
        };
    }
}
