<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enums\UserRoleEnum;
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

/**
 * Model User
 *
 * @property int $id
 * @property string $name
 * @property string $lastname
 * @property string $email
 * @property string $password
 * @property string|null $tax_id
 * @property int|null $customer_id
 * @property int|null $user_role_id
 * @property bool $is_admin
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Customer|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeTracker> $timeTrackers
 * @property-read int|null $time_trackers_count
 * @property-read \App\Models\UserRole|null $role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserRate> $userRates
 * @property-read int|null $user_rates_count
 */
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
        'email_verified_at',
        'customer_id',
        'user_role_id',
        'tax_id',
        'is_admin',
        'is_active',
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
            'is_active' => 'boolean',
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
        return $this->role_id === UserRoleEnum::Owner->value;
    }

    public function isAccountant(): bool
    {
        return $this->role_id === UserRoleEnum::Admin->value;
    }

    public function isManager(): bool
    {
        return $this->role_id === UserRoleEnum::Manager->value;
    }

    public function isEmployee(): bool
    {
        return $this->role_id === UserRoleEnum::Employee->value;
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
