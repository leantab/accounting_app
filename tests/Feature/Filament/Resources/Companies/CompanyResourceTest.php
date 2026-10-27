<?php

use App\Filament\Resources\Companies\CompanyResource;
use App\Filament\Resources\Companies\Pages\ManageCompanies;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

it('creates a company from the companies page', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();

    actingAs($admin);

    livewire(ManageCompanies::class)
        ->callAction('create', [
            'customer_id' => $customer->id,
            'name' => 'Proveedor SA',
        ])
        ->assertHasNoActionErrors();

    expect(Company::query()->where('name', 'Proveedor SA')->exists())->toBeTrue();
});

it('creates a company for a user without a customer account', function (): void {
    $admin = User::factory()->admin()->create(['customer_id' => null]);

    actingAs($admin);

    expect($admin->currentCustomerId())->toBeNull();

    livewire(ManageCompanies::class)
        ->callAction('create', ['customer_id' => Customer::factory()->create()->id, 'name' => 'Sin cliente SA'])
        ->assertHasNoActionErrors();
});

it('shows the companies navigation item in Spanish', function (): void {
    expect(CompanyResource::getNavigationLabel())->toBe('Empresas')
        ->and(CompanyResource::getModelLabel())->toBe('Empresa');
});
