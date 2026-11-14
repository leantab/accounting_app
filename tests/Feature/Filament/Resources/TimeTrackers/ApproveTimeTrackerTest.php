<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\TimeTrackers\Pages\ViewTimeTracker;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

function trackerWithOutdatedAmounts(Customer $customer, User $worker): TimeTracker
{
    $dayId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    $travelId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::TRAVEL);

    DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => $dayId, 'rate' => 300]);

    $tracker = pendingTracker($customer, $worker, approved: true);
    $tracker->update(['amount' => 600]);
    $tracker->items()->create(['date' => '2026-10-01', 'time_tracker_item_type_id' => $dayId, 'amount' => 200]);
    $tracker->items()->create(['date' => '2026-10-02', 'time_tracker_item_type_id' => $travelId, 'amount' => 400]);

    return $tracker;
}

it('recalculates the item amounts and the total with the current rates', function (): void {
    Queue::fake();
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $tracker = trackerWithOutdatedAmounts($customer, $worker);

    actingAs(User::factory()->admin()->create());

    livewire(ViewTimeTracker::class, ['record' => $tracker->id])
        ->assertActionVisible('recalculate')
        ->callAction('recalculate')
        ->assertNotified('Monto recalculado');

    expect($tracker->fresh()->items->map(fn ($item) => (float) $item->amount)->sort()->values()->all())->toBe([300.0, 400.0])
        ->and((float) $tracker->fresh()->amount)->toBe(700.0);

    Queue::assertNothingPushed();
});

it('warns about item types without a rate when recalculating', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $tracker = pendingTracker($customer, $worker, approved: true);
    $tracker->items()->create(['date' => '2026-10-01', 'time_tracker_item_type_id' => TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY)]);

    actingAs(User::factory()->admin()->create());

    livewire(ViewTimeTracker::class, ['record' => $tracker->id])
        ->callAction('recalculate')
        ->assertNotified('Monto recalculado, pero faltan tarifas');
});

it('shows the recalculate button only to owners, administrators and platform admins', function (UserRoleEnum $role, bool $visible): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $user = User::factory()->create(['customer_id' => $customer->id, 'user_role_id' => $role->value]);

    actingAs($user);

    $component = livewire(ViewTimeTracker::class, ['record' => pendingTracker($customer, $worker)->id]);

    $visible ? $component->assertActionVisible('recalculate') : $component->assertActionHidden('recalculate');
})->with([
    'owner' => [UserRoleEnum::Owner, true],
    'administrator' => [UserRoleEnum::Admin, true],
    'manager' => [UserRoleEnum::Manager, false],
    'employee' => [UserRoleEnum::Employee, false],
]);

it('shows the recalculate button to platform admins', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);

    actingAs(User::factory()->admin()->create());

    livewire(ViewTimeTracker::class, ['record' => pendingTracker($customer, $worker)->id])
        ->assertActionVisible('recalculate');
});
