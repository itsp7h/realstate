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
    protected $fillable = [
        'name',
        'email',
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

    /**
     * Only Admin can delete records — User can view/create/edit but never
     * delete, and Maintenance is confined to the Maintenance module entirely.
     */
    public function canDelete(): bool
    {
        return $this->isAdmin();
    }

    /**
     * The Reports section surfaces financial data (P&L, VAT, collections)
     * that's kept to Admin only.
     */
    public function canViewReports(): bool
    {
        return $this->isAdmin();
    }

    /**
     * The Maintenance role is confined to the Maintenance Requests module —
     * everything else in the app is off-limits to it.
     */
    public function isRestrictedToMaintenance(): bool
    {
        return $this->isMaintenance();
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            'admin'       => 'Admin',
            'user'        => 'User',
            'maintenance' => 'Maintenance',
            default       => ucfirst($this->role),
        };
    }
}
