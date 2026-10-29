<?php

use App\Enums\UserRoleEnum;
use App\Filament\Resources\TimeTrackers\Pages\ViewTimeTracker;
use App\Models\Customer;
use App\Models\TimeTracker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

function pendingTracker(Customer $customer, User $worker, bool $approved = false): TimeTracker
{
    return TimeTracker::factory()->create([
        'customer_id' => $customer->id,
        'user_id' => $worker->id,
        'approved' => $approved,
        'approved_at' => $approved ? now() : null,
        'approved_by' => null,
    ]);
}

it('lets a platform admin approve a pending tracker and then hides the button', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $tracker = pendingTracker($customer, $worker);
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    livewire(ViewTimeTracker::class, ['record' => $tracker->id])
        ->assertActionVisible('approve')
        ->callAction('approve')
        ->assertActionHidden('approve');

    expect((bool) $tracker->fresh()->approved)->toBeTrue()
        ->and($tracker->fresh()->approved_by)->toBe($admin->id);
});

it('hides the approve button for platform admins on an already approved tracker', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $tracker = pendingTracker($customer, $worker, approved: true);

    actingAs(User::factory()->admin()->create());

    livewire(ViewTimeTracker::class, ['record' => $tracker->id])
        ->assertActionHidden('approve');
});

it('shows and hides the approve button for customer owners and administrators', function (UserRoleEnum $role): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $approver = User::factory()->create(['customer_id' => $customer->id, 'user_role_id' => $role->value]);

    actingAs($approver);

    livewire(ViewTimeTracker::class, ['record' => pendingTracker($customer, $worker)->id])
        ->assertActionVisible('approve');

    livewire(ViewTimeTracker::class, ['record' => pendingTracker($customer, $worker, approved: true)->id])
        ->assertActionHidden('approve');
})->with([
    'owner' => UserRoleEnum::Owner,
    'administrator' => UserRoleEnum::Admin,
]);

it('never shows the approve button to managers or employees', function (UserRoleEnum $role): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $user = User::factory()->create(['customer_id' => $customer->id, 'user_role_id' => $role->value]);

    actingAs($user);

    livewire(ViewTimeTracker::class, ['record' => pendingTracker($customer, $worker)->id])
        ->assertActionHidden('approve');
})->with([
    'manager' => UserRoleEnum::Manager,
    'employee' => UserRoleEnum::Employee,
]);
