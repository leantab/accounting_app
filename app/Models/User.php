<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'lastname',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $name = Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');

        $lastname = Str::of($this->lastname)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');

        return $name . $lastname;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->customer_id != null;
    }

    public function company(): MorphOne
    {
        return $this->morphOne(Company::class, 'companyable');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'user_role_id');
    }

    public function userRates(): HasMany
    {
        return $this->hasMany(UserRate::class);
    }

    public function timeTrackers(): HasMany
    {
        return $this->hasMany(TimeTracker::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => "{$this->name} {$this->lastname}",
        );
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isOwner(): bool
    {
        return $this->role->name === 'Owner';
    }

    public function isAccountant(): bool
    {
        return $this->role->name === 'Administrador';
    }

    public function isManager(): bool
    {
        return $this->role->name === 'Manager';
    }

    public function isEmployee(): bool
    {
        return $this->role->name === 'Empleado';
    }

    public function canAccessCustomer(Customer $customer): bool
    {
        return $this->customer_id === $customer->id || $this->isAdmin();
    }

    public function currentCustomerId(): int
    {
        return $this->customer_id;
    }
}
