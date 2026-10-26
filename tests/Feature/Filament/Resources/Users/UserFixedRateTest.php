<?php

use App\Enums\UserRoleEnum;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

it('creates users without a fixed rate by default', function (): void {
    $user = User::factory()->create();

    expect($user->fresh()->fixed_daily_rate)->toBeNull()
        ->and($user->fresh()->fixed_monthly_rate)->toBeNull()
        ->and($user->hasFixedRate())->toBeFalse();
});

it('reports a fixed rate when either the daily or monthly one is set', function (?string $daily, ?string $monthly): void {
    $user = User::factory()->create([
        'fixed_daily_rate' => $daily,
        'fixed_monthly_rate' => $monthly,
    ]);

    expect($user->fresh()->hasFixedRate())->toBeTrue();
})->with([
    'daily only' => ['150.50', null],
    'monthly only' => [null, '3000'],
    'both' => ['150.50', '3000'],
]);

it('treats an explicit zero as a fixed rate', function (): void {
    $user = User::factory()->create(['fixed_daily_rate' => 0]);

    expect($user->fresh()->hasFixedRate())->toBeTrue();
});

it('casts fixed rates to two decimals', function (): void {
    $user = User::factory()->create([
        'fixed_daily_rate' => 150.5,
        'fixed_monthly_rate' => 3000,
    ]);

    expect($user->fresh()->fixed_daily_rate)->toBe('150.50')
        ->and($user->fresh()->fixed_monthly_rate)->toBe('3000.00');
});

it('sets only a daily fixed rate from the edit page', function (): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->create();

    actingAs($admin);

    livewire(EditUser::class, ['record' => $worker->id])
        ->fillForm(['fixed_daily_rate' => 200])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($worker->fresh()->fixed_daily_rate)->toBe('200.00')
        ->and($worker->fresh()->fixed_monthly_rate)->toBeNull();
});

it('sets both fixed rates from the edit page', function (): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->create();

    actingAs($admin);

    livewire(EditUser::class, ['record' => $worker->id])
        ->fillForm([
            'fixed_daily_rate' => 200,
            'fixed_monthly_rate' => 4500.75,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($worker->fresh()->fixed_daily_rate)->toBe('200.00')
        ->and($worker->fresh()->fixed_monthly_rate)->toBe('4500.75');
});

it('rejects negative or non numeric fixed rates', function (): void {
    $customer = Customer::factory()->create();
    $admin = User::factory()->create([
        'customer_id' => $customer->id,
        'user_role_id' => UserRoleEnum::Owner->value,
    ]);

    actingAs($admin);

    livewire(ManageUsers::class)
        ->callAction('create', [
            'name' => 'Ana',
            'lastname' => 'Perez',
            'email' => 'ana@example.com',
            'is_active' => true,
            'fixed_daily_rate' => -5,
            'fixed_monthly_rate' => 'abc',
        ])
        ->assertHasActionErrors(['fixed_daily_rate', 'fixed_monthly_rate']);

    expect(User::query()->where('email', 'ana@example.com')->exists())->toBeFalse();
});

it('edits and clears fixed rates', function (): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->create([
        'fixed_daily_rate' => 100,
        'fixed_monthly_rate' => 2000,
    ]);

    actingAs($admin);

    livewire(EditUser::class, ['record' => $worker->id])
        ->assertFormSet([
            'fixed_daily_rate' => '100.00',
            'fixed_monthly_rate' => '2000.00',
        ])
        ->fillForm([
            'fixed_daily_rate' => null,
            'fixed_monthly_rate' => 2500,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($worker->fresh()->fixed_daily_rate)->toBeNull()
        ->and($worker->fresh()->fixed_monthly_rate)->toBe('2500.00');
});

it('shows fixed rates on the user view page', function (): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->create([
        'fixed_daily_rate' => 123.45,
        'fixed_monthly_rate' => null,
    ]);

    actingAs($admin);

    livewire(ViewUser::class, ['record' => $worker->id])
        ->assertOk()
        ->assertSee('Tarifa fija por jornada')
        ->assertSee('123.45')
        ->assertSee('Tarifa fija mensual');
});

it('lists the fixed rate columns in the users table', function (): void {
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->create(['fixed_monthly_rate' => 3210]);

    actingAs($admin);

    livewire(ManageUsers::class)
        ->assertCanSeeTableRecords([$worker])
        ->assertTableColumnExists('fixed_daily_rate')
        ->assertTableColumnExists('fixed_monthly_rate')
        ->assertTableColumnStateSet('fixed_monthly_rate', '3210.00', $worker);
});
