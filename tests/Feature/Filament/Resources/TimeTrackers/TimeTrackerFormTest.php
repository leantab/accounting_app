<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\TimeTrackers\Pages\CreateTimeTracker;
use App\Filament\Resources\TimeTrackers\Pages\EditTimeTracker;
use App\Models\Customer;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

function seedItemTypes(): void
{
    foreach (TimeTrackerItemTypeEnum::cases() as $type) {
        TimeTrackerItemType::ensureIdFor($type);
    }
}

it('stores start and end times for non hourly item types on create', function (): void {
    seedItemTypes();
    $admin = User::factory()->admin()->create();
    $dayTypeId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);

    actingAs($admin);

    livewire(CreateTimeTracker::class)
        ->fillForm([
            'customer_id' => Customer::factory()->create()->id,
            'user_id' => $admin->id,
            'name' => 'Planilla de prueba',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-02',
            'items' => [
                [
                    'date' => '2026-10-01',
                    'time_tracker_item_type_id' => $dayTypeId,
                    'time_start' => '08:00',
                    'time_end' => '17:00',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $item = TimeTracker::query()->where('name', 'Planilla de prueba')->firstOrFail()->items()->firstOrFail();

    expect($item->time_start->format('H:i'))->toBe('08:00')
        ->and($item->time_end->format('H:i'))->toBe('17:00');
});

it('updates start and end times for non hourly item types on edit', function (): void {
    seedItemTypes();
    $admin = User::factory()->admin()->create();
    $dayTypeId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    $tracker = TimeTracker::factory()->create(['user_id' => $admin->id]);
    $tracker->items()->create([
        'date' => '2026-10-01',
        'time_tracker_item_type_id' => $dayTypeId,
    ]);

    actingAs($admin);

    $component = livewire(EditTimeTracker::class, ['record' => $tracker->id]);
    $itemKey = array_key_first($component->get('data.items'));

    $component
        ->set("data.items.{$itemKey}.time_start", '09:00')
        ->set("data.items.{$itemKey}.time_end", '18:00')
        ->call('save')
        ->assertHasNoFormErrors();

    $item = $tracker->items()->firstOrFail();

    expect($item->time_start->format('H:i'))->toBe('09:00')
        ->and($item->time_end->format('H:i'))->toBe('18:00');
});

it('hides and clears the hours field for daily item types', function (): void {
    seedItemTypes();
    $admin = User::factory()->admin()->create();
    $extraHourId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXTRA_HOUR);

    actingAs($admin);

    $component = livewire(CreateTimeTracker::class)
        ->fillForm([
            'items' => [
                ['time_tracker_item_type_id' => $extraHourId, 'hours' => 5],
            ],
        ]);

    $itemKey = array_key_first($component->get('data.items'));

    foreach ([TimeTrackerItemTypeEnum::DAY, TimeTrackerItemTypeEnum::DAY_RIGGING, TimeTrackerItemTypeEnum::DAY_SETUP_TEARDOWN, TimeTrackerItemTypeEnum::DOUBLE_DAY] as $dailyType) {
        $component
            ->set("data.items.{$itemKey}.hours", 5)
            ->set("data.items.{$itemKey}.time_tracker_item_type_id", TimeTrackerItemType::ensureIdFor($dailyType))
            ->assertSet("data.items.{$itemKey}.hours", null);
    }
});

it('labels the item fields in Spanish', function (): void {
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    livewire(CreateTimeTracker::class)
        ->assertFormFieldExists('name', checkFieldUsing: fn ($field): bool => $field->getLabel() === 'Nombre')
        ->assertFormFieldExists('items', checkFieldUsing: fn ($field): bool => $field->getLabel() === 'Ítems');
});

it('loads saved times into the 30 minute select options', function (): void {
    seedItemTypes();
    $admin = User::factory()->admin()->create();
    $tracker = TimeTracker::factory()->create(['user_id' => $admin->id]);
    $tracker->items()->create([
        'date' => '2026-10-01',
        'time_tracker_item_type_id' => TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY),
        'time_start' => '08:30',
        'time_end' => '17:30',
    ]);

    actingAs($admin);

    $component = livewire(EditTimeTracker::class, ['record' => $tracker->id]);
    $itemKey = array_key_first($component->get('data.items'));

    $component
        ->assertSet("data.items.{$itemKey}.time_start", '08:30')
        ->assertSet("data.items.{$itemKey}.time_end", '17:30');
});

it('assigns the signed in employee as the tracker user when the user select is hidden', function (): void {
    seedItemTypes();
    $customer = Customer::factory()->create();
    $employee = User::factory()->create([
        'customer_id' => $customer->id,
        'user_role_id' => UserRoleEnum::Employee->value,
    ]);
    $other = User::factory()->create(['customer_id' => $customer->id]);
    $dayTypeId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);

    actingAs($employee);

    livewire(CreateTimeTracker::class)
        ->assertFormFieldIsHidden('user_id')
        ->fillForm([
            'name' => 'Mi planilla',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-02',
            'items' => [
                ['date' => '2026-10-01', 'time_tracker_item_type_id' => $dayTypeId],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tracker = TimeTracker::query()->where('name', 'Mi planilla')->firstOrFail();

    expect($tracker->user_id)->toBe($employee->id)
        ->and($tracker->customer_id)->toBe($customer->id);
});

it('does not let an employee assign the tracker to another user', function (): void {
    seedItemTypes();
    $customer = Customer::factory()->create();
    $employee = User::factory()->create([
        'customer_id' => $customer->id,
        'user_role_id' => UserRoleEnum::Employee->value,
    ]);
    $other = User::factory()->create(['customer_id' => $customer->id]);
    $dayTypeId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);

    actingAs($employee);

    livewire(CreateTimeTracker::class)
        ->fillForm([
            'user_id' => $other->id,
            'name' => 'Planilla ajena',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-02',
            'items' => [
                ['date' => '2026-10-01', 'time_tracker_item_type_id' => $dayTypeId],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TimeTracker::query()->where('name', 'Planilla ajena')->firstOrFail()->user_id)->toBe($employee->id);
});
