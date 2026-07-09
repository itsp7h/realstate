<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Tenant extends Model
{
    use Auditable;
    protected $fillable = [
        'name',
        'tenant_type',
        'tenant_code',
        'id_cr_number',
        'phone',
        'email',
        'nationality_country',
        'address',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $tenant) {
            if (empty($tenant->tenant_code)) {
                $tenant->tenant_code = static::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $last = static::orderByDesc('id')->value('id') ?? 0;
        return 'Tenant-' . str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }

    public function auditName(): string
    {
        return $this->name;
    }
}
