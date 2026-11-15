<?php

use App\Actions\TimeTracker\AutoCompleteTimeTrackerAction;
use App\Enums\TimeTrackerItemTypeEnum;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Models\UserRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function typeId(TimeTrackerItemTypeEnum $type): int
{
    TimeTrackerItemType::query()->insertOrIgnore(['id' => $type->value, 'name' => $type->label()]);

    return $type->value;
}

it('prices each MAIN type and Consola with its own rate, never the base type rate', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);

    $rates = [
        [TimeTrackerItemTypeEnum::DAY, 100],
        [TimeTrackerItemTypeEnum::DAY_MAIN, 150],
        [TimeTrackerItemTypeEnum::DOUBLE_DAY_MAIN, 400],
        [TimeTrackerItemTypeEnum::LOAD_UNLOAD_MAIN, 90],
        [TimeTrackerItemTypeEnum::CONSOLE, 500],
    ];

    foreach ($rates as [$type, $rate]) {
        DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => typeId($type), 'rate' => $rate]);
    }

    $tracker = TimeTracker::factory()->approved()->create(['customer_id' => $customer->id, 'user_id' => $worker->id, 'amount' => null]);

    foreach ([TimeTrackerItemTypeEnum::DAY_MAIN, TimeTrackerItemTypeEnum::DOUBLE_DAY_MAIN, TimeTrackerItemTypeEnum::LOAD_UNLOAD_MAIN, TimeTrackerItemTypeEnum::CONSOLE] as $type) {
        $tracker->items()->create(['date' => '2026-10-01', 'time_tracker_item_type_id' => typeId($type)]);
    }

    $result = AutoCompleteTimeTrackerAction::execute();

    expect($result['missing_rates'])->toBe([])
        ->and((float) $tracker->fresh()->amount)->toBe(1140.0);
});

it('charges hourly MAIN types per hour and lets the user rate win over the default', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);

    foreach ([TimeTrackerItemTypeEnum::EXTRA_HOUR_MAIN, TimeTrackerItemTypeEnum::EXTRA_HOUR_RIGGING_MAIN] as $type) {
        DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => typeId($type), 'rate' => 10]);
    }

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $worker->id,
        'time_tracker_item_type_id' => typeId(TimeTrackerItemTypeEnum::EXTRA_HOUR_RIGGING_MAIN),
        'rate' => 25,
    ]);

    $tracker = TimeTracker::factory()->approved()->create(['customer_id' => $customer->id, 'user_id' => $worker->id, 'amount' => null]);
    $tracker->items()->create(['date' => '2026-10-01', 'time_tracker_item_type_id' => typeId(TimeTrackerItemTypeEnum::EXTRA_HOUR_MAIN), 'hours' => 3]);
    $tracker->items()->create(['date' => '2026-10-02', 'time_tracker_item_type_id' => typeId(TimeTrackerItemTypeEnum::EXTRA_HOUR_RIGGING_MAIN), 'hours' => 2]);

    AutoCompleteTimeTrackerAction::execute();

    expect($tracker->fresh()->items->map(fn ($item) => (float) $item->amount)->sort()->values()->all())->toBe([30.0, 50.0])
        ->and((float) $tracker->fresh()->amount)->toBe(80.0);
});

it('reports a missing rate for a MAIN type that only has the base type rate', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);

    DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => typeId(TimeTrackerItemTypeEnum::DAY), 'rate' => 100]);

    $tracker = TimeTracker::factory()->approved()->create(['customer_id' => $customer->id, 'user_id' => $worker->id, 'amount' => null]);
    $tracker->items()->create(['date' => '2026-10-01', 'time_tracker_item_type_id' => typeId(TimeTrackerItemTypeEnum::DAY_MAIN)]);

    $result = AutoCompleteTimeTrackerAction::execute();

    expect($result['missing_rates'])->toBe(['Jornal MAIN']);
});
