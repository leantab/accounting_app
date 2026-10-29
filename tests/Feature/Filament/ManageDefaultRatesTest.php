<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Pages\ManageDefaultRates;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array<int, int>
 */
function itemTypeIds(): array
{
    $ids = [];

    foreach (TimeTrackerItemTypeEnum::cases() as $type) {
        $ids[$type->value] = TimeTrackerItemType::ensureIdFor($type);
    }

    return $ids;
}

/**
 * @param  array<string, mixed>  $state
 */
function fillDefaultRateForm(array $state): Testable
{
    $component = livewire(ManageDefaultRates::class);

    foreach ($state as $field => $value) {
        $component->set('data.'.$field, $value);
    }

    return $component;
}

it('lets platform admins save a default for every item type that has one', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();
    $typeIds = itemTypeIds();

    actingAs($admin);

    fillDefaultRateForm([
        'customer_id' => $customer->id,
        'rates' => [
            $typeIds[TimeTrackerItemTypeEnum::EXTRA_HOUR->value] => 120.5,
            $typeIds[TimeTrackerItemTypeEnum::DAY->value] => 800,
            $typeIds[TimeTrackerItemTypeEnum::DAY_RIGGING->value] => 400,
            $typeIds[TimeTrackerItemTypeEnum::EXTRA_HOUR_RIGGING->value] => 90,
            $typeIds[TimeTrackerItemTypeEnum::DAY_SETUP_TEARDOWN->value] => 500,
            $typeIds[TimeTrackerItemTypeEnum::DOUBLE_DAY->value] => 1600,
            $typeIds[TimeTrackerItemTypeEnum::LOAD_UNLOAD->value] => 60,
            $typeIds[TimeTrackerItemTypeEnum::WAREHOUSE->value] => 45,
            $typeIds[TimeTrackerItemTypeEnum::GUARD->value] => 70,
            $typeIds[TimeTrackerItemTypeEnum::TRAVEL->value] => 30,
            $typeIds[TimeTrackerItemTypeEnum::EXPENSES->value] => 15,
        ],
    ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(DefaultRate::query()->where('customer_id', $customer->id)->count())->toBe(9)
        ->and((float) DefaultRate::query()->where('customer_id', $customer->id)->where('time_tracker_item_type_id', $typeIds[TimeTrackerItemTypeEnum::EXTRA_HOUR->value])->value('rate'))->toBe(120.5)
        ->and(DefaultRate::query()->whereIn('time_tracker_item_type_id', [
            $typeIds[TimeTrackerItemTypeEnum::TRAVEL->value],
            $typeIds[TimeTrackerItemTypeEnum::EXPENSES->value],
        ])->exists())->toBeFalse();
});

it('does not offer default rates for expenses and travel', function (): void {
    $admin = User::factory()->admin()->create();
    $typeIds = itemTypeIds();

    actingAs($admin);

    livewire(ManageDefaultRates::class)
        ->assertFormFieldExists('rates.'.$typeIds[TimeTrackerItemTypeEnum::DAY->value])
        ->assertFormFieldDoesNotExist('rates.'.$typeIds[TimeTrackerItemTypeEnum::TRAVEL->value])
        ->assertFormFieldDoesNotExist('rates.'.$typeIds[TimeTrackerItemTypeEnum::EXPENSES->value])
        ->assertDontSee('Gastos')
        ->assertDontSee('Viáticos');
});

it('updates existing defaults instead of creating duplicates', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();
    $typeIds = itemTypeIds();
    $hoursId = $typeIds[TimeTrackerItemTypeEnum::EXTRA_HOUR->value];

    actingAs($admin);

    fillDefaultRateForm([
        'customer_id' => $customer->id,
        'rates' => [
            $hoursId => 100,
        ],
    ])
        ->call('save')
        ->assertHasNoFormErrors();

    fillDefaultRateForm([
        'customer_id' => $customer->id,
        'rates' => [
            $hoursId => 110,
        ],
    ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(DefaultRate::query()->where('customer_id', $customer->id)->count())->toBe(1)
        ->and((float) DefaultRate::query()->where('customer_id', $customer->id)->where('time_tracker_item_type_id', $hoursId)->value('rate'))->toBe(110.0);
});

it('removes a default when its rate is cleared', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();
    $typeIds = itemTypeIds();
    $hoursId = $typeIds[TimeTrackerItemTypeEnum::EXTRA_HOUR->value];

    DefaultRate::query()->create([
        'customer_id' => $customer->id,
        'time_tracker_item_type_id' => $hoursId,
        'rate' => 100,
    ]);

    actingAs($admin);

    fillDefaultRateForm([
        'customer_id' => $customer->id,
        'rates' => [
            $hoursId => null,
        ],
    ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(DefaultRate::query()->where('customer_id', $customer->id)->exists())->toBeFalse();
});

it('rejects a negative rate', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();
    $typeIds = itemTypeIds();
    $hoursId = $typeIds[TimeTrackerItemTypeEnum::EXTRA_HOUR->value];

    actingAs($admin);

    fillDefaultRateForm([
        'customer_id' => $customer->id,
        'rates' => [
            $hoursId => -1,
        ],
    ])
        ->call('save')
        ->assertHasFormErrors([
            "rates.{$hoursId}",
        ])
        ->assertNotNotified();
});

it('lets a customer admin save defaults for their own customer', function (): void {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $user = User::factory()->create([
        'customer_id' => $customer->id,
        'is_admin' => false,
        'user_role_id' => UserRoleEnum::Admin->value,
    ]);

    $typeIds = itemTypeIds();
    $hoursId = $typeIds[TimeTrackerItemTypeEnum::EXTRA_HOUR->value];

    actingAs($user);

    fillDefaultRateForm([
        'customer_id' => $otherCustomer->id,
        'rates' => [
            $hoursId => 40,
            $typeIds[TimeTrackerItemTypeEnum::DAY->value] => 300,
        ],
    ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(DefaultRate::query()->where('customer_id', $customer->id)->count())->toBe(2)
        ->and(DefaultRate::query()->where('customer_id', $otherCustomer->id)->exists())->toBeFalse();
});

it('loads the saved defaults for the signed-in customer', function (): void {
    $customer = Customer::factory()->create();
    $user = User::factory()->create([
        'customer_id' => $customer->id,
        'is_admin' => false,
        'user_role_id' => UserRoleEnum::Owner->value,
    ]);

    $hourlyTypeId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXTRA_HOUR);
    $dailyTypeId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);

    DefaultRate::query()->create([
        'customer_id' => $customer->id,
        'time_tracker_item_type_id' => $hourlyTypeId,
        'rate' => 55,
    ]);
    DefaultRate::query()->create([
        'customer_id' => $customer->id,
        'time_tracker_item_type_id' => $dailyTypeId,
        'rate' => 440,
    ]);

    actingAs($user);

    $rates = livewire(ManageDefaultRates::class)
        ->assertOk()
        ->get('data.rates');

    expect($rates[$hourlyTypeId])->toBe('55,00')
        ->and($rates[$dailyTypeId])->toBe('440,00');
});

it('allows owners and customer admins to open the page', function (UserRoleEnum $role): void {
    $user = User::factory()->create([
        'customer_id' => Customer::factory(),
        'user_role_id' => $role->value,
    ]);

    actingAs($user);

    livewire(ManageDefaultRates::class)->assertOk();
})->with([
    'owner' => UserRoleEnum::Owner,
    'admin' => UserRoleEnum::Admin,
]);

it('blocks managers and employees', function (UserRoleEnum $role): void {
    $user = User::factory()->create([
        'customer_id' => Customer::factory(),
        'user_role_id' => $role->value,
    ]);

    actingAs($user);

    livewire(ManageDefaultRates::class)->assertForbidden();
})->with([
    'manager' => UserRoleEnum::Manager,
    'employee' => UserRoleEnum::Employee,
]);

it('shows the default hourly rate on the user until a custom rate exists', function (): void {
    $customer = Customer::factory()->create();
    $admin = User::factory()->admin()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);

    DefaultRate::query()->create([
        'customer_id' => $customer->id,
        'time_tracker_item_type_id' => TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXTRA_HOUR),
        'rate' => 50,
    ]);

    actingAs($admin);

    livewire(ViewUser::class, ['record' => $worker->id])
        ->assertOk()
        ->assertSee('Por defecto (50,00)')
        ->assertSee('Por defecto');
});

it('shows and saves default rates with the Argentine number format', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();
    $dayId = itemTypeIds()[TimeTrackerItemTypeEnum::DAY->value];

    DefaultRate::query()->create([
        'customer_id' => $customer->id,
        'time_tracker_item_type_id' => $dayId,
        'rate' => 12345.6,
    ]);

    actingAs($admin);

    $component = fillDefaultRateForm(['customer_id' => $customer->id])
        ->assertSet("data.rates.{$dayId}", '12.345,60');

    $component
        ->set("data.rates.{$dayId}", '1.500.000,25')
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) DefaultRate::query()->where('time_tracker_item_type_id', $dayId)->value('rate'))->toBe(1500000.25);
});
