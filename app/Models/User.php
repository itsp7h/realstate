<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /**
     * The photo's public URL, or null when there is none.
     *
     * Null is the normal state, and every avatar in the shell already knows how
     * to draw an initial — so this returning null is not a missing image, it is
     * the fallback path.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }

    /** The single letter drawn when there is no photo. */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name ?: '?', 0, 1));
    }

    protected $fillable = [
        'name',
        'email',
        'photo_path',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function isMaintenance(): bool
    {
        return $this->role === 'maintenance';
    }

    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    /**
     * Only Admin can delete records. Every other role can view, create and
     * edit at most — never remove.
     */
    public function canDelete(): bool
    {
        return $this->isAdmin();
    }

    /* ── What each role may reach ──────────────────────────────────────────
       These are named after the area rather than the role, because two roles
       are now confined to a slice of the app rather than one. A view that asks
       "is this user Maintenance?" has to be edited again the next time a
       confined role is added; one that asks "may this user reach the
       portfolio?" does not. RestrictScopedRoles enforces the same split on the
       routes, and RoleCatalog is what publishes it on the Roles page. ── */

    /**
     * Buildings, floors, units, tenants and leases. Maintenance is confined to
     * its own module, and Accountant works from the ledger, not the portfolio.
     */
    public function canAccessPortfolio(): bool
    {
        return in_array($this->role, ['admin', 'user'], true);
    }

    /**
     * Invoices, payments, EWA bills, expenses and revenue.
     */
    public function canAccessAccounting(): bool
    {
        return in_array($this->role, ['admin', 'user', 'accountant'], true);
    }

    /**
     * Maintenance Requests — the module the Maintenance role exists for, and
     * the one an Accountant has no part in.
     */
    public function canAccessMaintenance(): bool
    {
        return in_array($this->role, ['admin', 'user', 'maintenance'], true);
    }

    /**
     * The Reports section surfaces financial data (P&L, VAT, collections,
     * ageing). Admin, and Accountant, whose job it is.
     */
    public function canViewReports(): bool
    {
        return in_array($this->role, ['admin', 'accountant'], true);
    }

    /**
     * Forms & Templates and Import/Export. Both are portfolio-shaped, so they
     * follow portfolio access; *saving* a form config stays Admin-only and is
     * gated on the route itself.
     */
    public function canOpenConfiguration(): bool
    {
        return $this->canAccessPortfolio();
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin'       => 'Admin',
            'user'        => 'User',
            'maintenance' => 'Maintenance',
            'accountant'  => 'Accountant',
            default       => ucfirst($this->role),
        };
    }
}
