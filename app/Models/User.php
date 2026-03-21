<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
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
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user', 'user_id', 'company_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(CompanyRole::class, 'company_role', 'user_id', 'role_id');
    }

    public function userRoles(): BelongsToMany
    {
        return $this->belongsToMany(UserRole::class, 'customer_user_role', 'user_id', 'user_role_id')
            ->withPivot('customer_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isOwner(): bool
    {
        return $this->hasRole('owner');
    }

    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    public function isEmployee(): bool
    {
        return $this->hasRole('employee');
    }

    public function currentCompany(): ?Company
    {
        return $this->companies()->where('is_active', true)->first();
    }

    public function setCurrentCompany(Company $company): void
    {
        $this->companies()->updateExistingPivot($company->id, ['is_active' => true]);
    }

    public function canAccessCompany(Company $company): bool
    {
        return $this->companies()->where('company_id', $company->id)->exists();
    }

    public function canEditCompany(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager());
    }

    public function canDeleteCompany(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner());
    }

    public function canCreateInvoice(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager() || $this->isEmployee());
    }

    public function canEditInvoice(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager() || $this->isEmployee());
    }

    public function canDeleteInvoice(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager());
    }

    public function canCreatePayment(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager() || $this->isEmployee());
    }

    public function canEditPayment(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager() || $this->isEmployee());
    }

    public function canDeletePayment(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager());
    }

    public function canCreateCustomer(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager() || $this->isEmployee());
    }

    public function canEditCustomer(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager() || $this->isEmployee());
    }

    public function canDeleteCustomer(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager());
    }

    public function canCreateUser(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager());
    }

    public function canEditUser(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner() || $this->isManager());
    }

    public function canDeleteUser(Company $company): bool
    {
        return $this->canAccessCompany($company) && ($this->isAdmin() || $this->isOwner());
    }

    public function currentCustomerId(): ?int
    {
        $customerId = $this->getAttribute('customer_id');

        return $customerId !== null ? (int) $customerId : null;
    }
}
