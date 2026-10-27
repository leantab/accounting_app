<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Filament\Resources\TimeTrackers\Pages\CreateTimeTracker;
use App\Filament\Resources\TimeTrackers\Pages\ListTimeTrackers;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

it('fills item amounts and the tracker total from the auto complete button', function (): void {
    foreach (TimeTrackerItemTypeEnum::cases() as $type) {
        TimeTrackerItemType::ensureIdFor($type);
    }
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $hourId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXTRA_HOUR);
    $dayId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);

    DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => $hourId, 'rate' => 10]);
    DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => $dayId, 'rate' => 100]);

    $tracker = TimeTracker::factory()->create(['customer_id' => $customer->id, 'user_id' => $worker->id, 'amount' => null]);
    $hourItem = $tracker->items()->create(['date' => '2026-10-01', 'time_tracker_item_type_id' => $hourId, 'hours' => 3]);
    $dayItem = $tracker->items()->create(['date' => '2026-10-02', 'time_tracker_item_type_id' => $dayId, 'time_start' => '08:00', 'time_end' => '17:00']);

    actingAs(User::factory()->admin()->create());

    livewire(ListTimeTrackers::class)->callAction(TestAction::make('auto_complete')->table());

    expect((float) $hourItem->fresh()->amount)->toBe(30.0)
        ->and((float) $dayItem->fresh()->amount)->toBe(100.0)
        ->and((float) $tracker->fresh()->amount)->toBe(130.0);
});

it('works end to end for a customer owner after creating the tracker from the form', function (): void {
    foreach (TimeTrackerItemTypeEnum::cases() as $type) {
        TimeTrackerItemType::ensureIdFor($type);
    }
    $customer = Customer::factory()->create();
    $owner = User::factory()->create(['customer_id' => $customer->id, 'user_role_id' => 1]);
    $dayId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    $hourId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXTRA_HOUR);

    DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => $dayId, 'rate' => 100]);
    DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => $hourId, 'rate' => 10]);

    actingAs($owner);

    livewire(CreateTimeTracker::class)
        ->fillForm([
            'user_id' => $owner->id,
            'name' => 'Planilla',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-02',
            'items' => [
                ['date' => '2026-10-01', 'time_tracker_item_type_id' => $dayId, 'time_start' => '08:00', 'time_end' => '17:00'],
                ['date' => '2026-10-02', 'time_tracker_item_type_id' => $hourId, 'hours' => 2, 'time_start' => '18:00', 'time_end' => '20:00'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tracker = TimeTracker::query()->where('name', 'Planilla')->firstOrFail();

    livewire(ListTimeTrackers::class)->callAction(TestAction::make('auto_complete')->table());

    expect($tracker->fresh()->items->map(fn ($item) => (float) $item->amount)->sort()->values()->all())->toBe([20.0, 100.0])
        ->and((float) $tracker->fresh()->amount)->toBe(120.0);
});

it('calculates a tracker again after rates were added following an earlier run without rates', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $dayId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    $tracker = TimeTracker::factory()->create(['customer_id' => $customer->id, 'user_id' => $worker->id, 'amount' => null]);
    $item = $tracker->items()->create(['date' => '2026-10-01', 'time_tracker_item_type_id' => $dayId]);

    actingAs(User::factory()->admin()->create());

    livewire(ListTimeTrackers::class)
        ->callAction(TestAction::make('auto_complete')->table())
        ->assertNotified('Planillas calculadas, pero faltan tarifas');

    expect((float) $tracker->fresh()->amount)->toBe(0.0);

    DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => $dayId, 'rate' => 250]);

    livewire(ListTimeTrackers::class)
        ->callAction(TestAction::make('auto_complete')->table())
        ->assertNotified('Planillas calculadas');

    expect((float) $item->fresh()->amount)->toBe(250.0)
        ->and((float) $tracker->fresh()->amount)->toBe(250.0);
});

it('does not set hours on daily items when times are present', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $dayId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => $dayId, 'rate' => 100]);
    $tracker = TimeTracker::factory()->create(['customer_id' => $customer->id, 'user_id' => $worker->id, 'amount' => null]);
    $item = $tracker->items()->create(['date' => '2026-10-01', 'time_tracker_item_type_id' => $dayId, 'time_start' => '08:00', 'time_end' => '17:00']);

    actingAs(User::factory()->admin()->create());

    livewire(ListTimeTrackers::class)->callAction(TestAction::make('auto_complete')->table());

    expect($item->fresh()->hours)->toBeNull()
        ->and((float) $item->fresh()->amount)->toBe(100.0);
});

it('tells the user when there is nothing to calculate', function (): void {
    actingAs(User::factory()->admin()->create());

    livewire(ListTimeTrackers::class)
        ->callAction(TestAction::make('auto_complete')->table())
        ->assertNotified('No hay planillas pendientes de calcular');
});
