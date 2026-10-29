<?php

use App\Enums\UserRoleEnum;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TimeTrackerStats;
use App\Models\Customer;
use App\Models\TimeTracker;
use App\Models\User;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

function userWithRole(UserRoleEnum $role, ?Customer $customer = null): User
{
    return User::factory()->create([
        'customer_id' => ($customer ?? Customer::factory()->create())->id,
        'user_role_id' => $role->value,
    ]);
}

it('shows the company overview to platform admins, owners and administrators', function (User $user): void {
    actingAs($user);

    livewire(Dashboard::class)
        ->assertSeeLivewire(StatsOverview::class)
        ->assertDontSeeLivewire(TimeTrackerStats::class);
})->with([
    'platform admin' => fn () => User::factory()->admin()->create(),
    'owner' => fn () => userWithRole(UserRoleEnum::Owner),
    'administrator' => fn () => userWithRole(UserRoleEnum::Admin),
]);

it('shows only the time tracker stats to managers and employees', function (User $user): void {
    actingAs($user);

    livewire(Dashboard::class)
        ->assertSeeLivewire(TimeTrackerStats::class)
        ->assertDontSeeLivewire(StatsOverview::class);
})->with([
    'manager' => fn () => userWithRole(UserRoleEnum::Manager),
    'employee' => fn () => userWithRole(UserRoleEnum::Employee),
]);

it('counts only the signed in user trackers pending approval and approved but unpaid', function (): void {
    $customer = Customer::factory()->create();
    $employee = userWithRole(UserRoleEnum::Employee, $customer);
    $colleague = userWithRole(UserRoleEnum::Employee, $customer);

    $make = fn (User $user, bool $approved, bool $paid) => TimeTracker::factory()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'approved' => $approved,
        'paid' => $paid,
    ]);

    $make($employee, false, false);
    $make($employee, false, false);
    $make($employee, true, false);
    $make($employee, true, true);
    $make($colleague, false, false);
    $make($colleague, true, false);
    $make($colleague, true, false);

    actingAs($employee);

    livewire(TimeTrackerStats::class)
        ->assertSee('Pendientes de aprobación')
        ->assertSee('Aprobadas sin pagar')
        ->assertSeeInOrder(['Pendientes de aprobación', '2', 'Aprobadas sin pagar', '1']);
});

it('does not show the time tracker stats to platform admins', function (): void {
    actingAs(User::factory()->admin()->create());

    expect(TimeTrackerStats::canView())->toBeFalse();
});
