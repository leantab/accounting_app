<?php

use App\Actions\TimeTracker\AutoCompleteTimeTrackerAction;
use App\Enums\TimeTrackerItemTypeEnum;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Project;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItem;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Models\UserRate;
use Tests\TestCase;

uses(TestCase::class);

function createTimeTrackerForAutoComplete(User $user, Customer $customer, ?float $amount = null): TimeTracker
{
    $company = Company::factory()->create(['customer_id' => $customer->id]);
    $project = Project::factory()->create([
        'customer_id' => $customer->id,
        'company_id' => $company->id,
    ]);

    return TimeTracker::factory()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'project_id' => $project->id,
        'invoice_id' => null,
        'payment_id' => null,
        'approved_by' => null,
        'amount' => $amount,
    ]);
}

it('ignores time trackers that already have an amount', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeHours = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::HOURS->label()]);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'rate' => 100,
    ]);

    $tracker = createTimeTrackerForAutoComplete($user, $customer, amount: 500);

    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'hours' => 10,
        'amount' => null,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $tracker->refresh();
    expect((float) $tracker->amount)->toBe(500.0);
});

it('fully completes amount for Horas items using hours times user rate', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeHours = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::HOURS->label()]);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'rate' => 50,
    ]);

    $tracker = createTimeTrackerForAutoComplete($user, $customer);

    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'hours' => 8,
        'amount' => null,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $tracker->refresh();
    expect((float) $tracker->amount)->toBe(400.0);
});

it('uses flat user rate for non Horas types when rate is positive', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeTravel = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::TRAVEL->label()]);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeTravel->id,
        'rate' => 75.5,
    ]);

    $tracker = createTimeTrackerForAutoComplete($user, $customer);

    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeTravel->id,
        'hours' => null,
        'time_start' => null,
        'time_end' => null,
        'amount' => 999,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $tracker->refresh();
    expect((float) $tracker->amount)->toBe(75.5);
});

it('uses item amount when user rate is zero for non Horas types', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeExpenses = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::EXPENSES->label()]);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeExpenses->id,
        'rate' => 0,
    ]);

    $tracker = createTimeTrackerForAutoComplete($user, $customer);

    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeExpenses->id,
        'hours' => null,
        'time_start' => null,
        'time_end' => null,
        'amount' => 42.25,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $tracker->refresh();
    expect((float) $tracker->amount)->toBe(42.25);
});

it('uses item amount when no user rate row exists for that item type', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeGeneral = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::DAY->label()]);

    $tracker = createTimeTrackerForAutoComplete($user, $customer);

    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeGeneral->id,
        'hours' => null,
        'time_start' => null,
        'time_end' => null,
        'amount' => 30,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $tracker->refresh();
    expect((float) $tracker->amount)->toBe(30.0);
});

it('derives hours from time_start and time_end when hours is empty', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeHours = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::HOURS->label()]);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'rate' => 25,
    ]);

    $tracker = createTimeTrackerForAutoComplete($user, $customer);

    $item = TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'hours' => null,
        'time_start' => '09:00:00',
        'time_end' => '17:00:00',
        'amount' => null,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $item->refresh();
    $tracker->refresh();

    expect((int) $item->hours)->toBe(8);
    expect((float) $tracker->amount)->toBe(200.0);
});

it('does not overwrite hours when hours is already set', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeHours = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::HOURS->label()]);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'rate' => 10,
    ]);

    $tracker = createTimeTrackerForAutoComplete($user, $customer);

    $item = TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'hours' => 3,
        'time_start' => '09:00:00',
        'time_end' => '17:00:00',
        'amount' => null,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $item->refresh();
    $tracker->refresh();

    expect((int) $item->hours)->toBe(3);
    expect((float) $tracker->amount)->toBe(30.0);
});

it('aggregates multiple heterogeneous items', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeHours = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::HOURS->label()]);
    $typeTravel = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::TRAVEL->label()]);
    $typeExpenses = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::EXPENSES->label()]);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'rate' => 40,
    ]);
    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeTravel->id,
        'rate' => 60,
    ]);
    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeExpenses->id,
        'rate' => 0,
    ]);

    $tracker = createTimeTrackerForAutoComplete($user, $customer);

    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'hours' => 2,
        'amount' => null,
    ]);
    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeTravel->id,
        'hours' => null,
        'time_start' => null,
        'time_end' => null,
        'amount' => 0,
    ]);
    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeExpenses->id,
        'hours' => null,
        'time_start' => null,
        'time_end' => null,
        'amount' => 12.5,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $tracker->refresh();
    expect((float) $tracker->amount)->toBe(152.5);
});

it('sets amount to zero when the time tracker has no line items', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $tracker = createTimeTrackerForAutoComplete($user, $customer);

    (new AutoCompleteTimeTrackerAction)->execute();

    $tracker->refresh();
    expect((float) $tracker->amount)->toBe(0.0);
});

it('processes every eligible time tracker in one run', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeHours = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::HOURS->label()]);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'rate' => 10,
    ]);

    $a = createTimeTrackerForAutoComplete($user, $customer);
    $b = createTimeTrackerForAutoComplete($user, $customer);

    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $a->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'hours' => 1,
        'amount' => null,
    ]);
    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $b->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'hours' => 2,
        'amount' => null,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $a->refresh();
    $b->refresh();

    expect((float) $a->amount)->toBe(10.0);
    expect((float) $b->amount)->toBe(20.0);
});
