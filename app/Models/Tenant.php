<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Tenant extends Model
{
    use Auditable;
    protected $fillable = [
        'name',
        'tenant_type',
        'company_name',
        'contact_person',
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

    public function leaseContracts(): HasMany
    {
        return $this->hasMany(LeaseContract::class);
    }

    /**
     * The tenant's current lease — a lease only stops counting once it's
     * explicitly terminated, not merely once its end date has passed.
     */
    public function activeLease(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LeaseContract::class)
            ->whereNull('terminated_at')
            ->latest('lease_start_date');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function ewaBills(): HasManyThrough
    {
        return $this->hasManyThrough(EwaBill::class, LeaseContract::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Invoice::class);
    }

    public function invoiceNotes(): HasMany
    {
        return $this->hasMany(InvoiceNote::class);
    }
}
