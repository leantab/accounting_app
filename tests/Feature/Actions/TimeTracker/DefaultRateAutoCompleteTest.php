<?php

use App\Actions\TimeTracker\AutoCompleteTimeTrackerAction;
use App\Enums\TimeTrackerItemTypeEnum;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\Project;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItem;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Models\UserRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function createTimeTrackerForDefaultRate(User $user, Customer $customer): TimeTracker
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
        'amount' => null,
    ]);
}

it('uses the customer default hourly rate when the user has no custom rate', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeHours = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::HOURS->label()]);

    DefaultRate::query()->create([
        'customer_id' => $customer->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'rate' => 50,
    ]);

    $tracker = createTimeTrackerForDefaultRate($user, $customer);

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

it('uses the customer default daily rate as a flat amount', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeDay = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::DAY->label()]);

    DefaultRate::query()->create([
        'customer_id' => $customer->id,
        'time_tracker_item_type_id' => $typeDay->id,
        'rate' => 80,
    ]);

    $tracker = createTimeTrackerForDefaultRate($user, $customer);

    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeDay->id,
        'hours' => null,
        'time_start' => null,
        'time_end' => null,
        'amount' => 30,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $tracker->refresh();

    expect((float) $tracker->amount)->toBe(80.0);
});

it('keeps a custom user rate ahead of the default', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeHours = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::HOURS->label()]);

    DefaultRate::query()->create([
        'customer_id' => $customer->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'rate' => 50,
    ]);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'rate' => 10,
    ]);

    $tracker = createTimeTrackerForDefaultRate($user, $customer);

    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'hours' => 2,
        'amount' => null,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $tracker->refresh();

    expect((float) $tracker->amount)->toBe(20.0)
        ->and($user->effectiveRateFor($typeHours->id))->toBe(10.0);
});

it('does not apply another customer default rate', function (): void {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeHours = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::HOURS->label()]);

    DefaultRate::query()->create([
        'customer_id' => $otherCustomer->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'rate' => 50,
    ]);

    $tracker = createTimeTrackerForDefaultRate($user, $customer);

    TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $typeHours->id,
        'hours' => 8,
        'amount' => null,
    ]);

    (new AutoCompleteTimeTrackerAction)->execute();

    $tracker->refresh();

    expect((float) $tracker->amount)->toBe(0.0);
});

it('keeps an explicit zero user rate instead of the default', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['customer_id' => $customer->id]);
    $typeExpenses = TimeTrackerItemType::query()->create(['name' => TimeTrackerItemTypeEnum::EXPENSES->label()]);

    DefaultRate::query()->create([
        'customer_id' => $customer->id,
        'time_tracker_item_type_id' => $typeExpenses->id,
        'rate' => 100,
    ]);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $user->id,
        'time_tracker_item_type_id' => $typeExpenses->id,
        'rate' => 0,
    ]);

    $tracker = createTimeTrackerForDefaultRate($user, $customer);

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
