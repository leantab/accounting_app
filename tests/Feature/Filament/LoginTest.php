<?php

use App\Enums\UserRoleEnum;
use App\Models\Customer;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

it('logs in and lands on a working dashboard for every role', function (?UserRoleEnum $role): void {
    $user = User::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'user_role_id' => $role?->value,
    ]);

    livewire(Login::class)
        ->fillForm(['email' => $user->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user);

    actingAs($user)->get('/app')->assertOk();
})->with([
    'owner' => UserRoleEnum::Owner,
    'administrator' => UserRoleEnum::Admin,
    'manager' => UserRoleEnum::Manager,
    'employee' => UserRoleEnum::Employee,
    'no role' => null,
]);

it('logs in a platform admin without a customer', function (): void {
    $admin = User::factory()->admin()->create();

    livewire(Login::class)
        ->fillForm(['email' => $admin->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    actingAs($admin)->get('/app')->assertOk();
});
