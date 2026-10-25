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

it('lets platform admins save hourly and daily defaults for a customer', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();

    actingAs($admin);

    fillDefaultRateForm([
        'customer_id' => $customer->id,
        'hourly_rate' => 120.5,
        'daily_rate' => 800,
    ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $hourlyTypeId = TimeTrackerItemType::findIdFor(TimeTrackerItemTypeEnum::HOURS);
    $dailyTypeId = TimeTrackerItemType::findIdFor(TimeTrackerItemTypeEnum::DAY);

    expect($hourlyTypeId)->not->toBeNull()
        ->and($dailyTypeId)->not->toBeNull()
        ->and(DefaultRate::query()->where('customer_id', $customer->id)->count())->toBe(2)
        ->and((float) DefaultRate::query()->where('customer_id', $customer->id)->where('time_tracker_item_type_id', $hourlyTypeId)->value('rate'))->toBe(120.5)
        ->and((float) DefaultRate::query()->where('customer_id', $customer->id)->where('time_tracker_item_type_id', $dailyTypeId)->value('rate'))->toBe(800.0);
});

it('updates existing defaults instead of creating duplicates', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();

    actingAs($admin);

    fillDefaultRateForm([
        'customer_id' => $customer->id,
        'hourly_rate' => 100,
        'daily_rate' => 700,
    ])
        ->call('save')
        ->assertHasNoFormErrors();

    fillDefaultRateForm([
        'customer_id' => $customer->id,
        'hourly_rate' => 110,
        'daily_rate' => 750,
    ])
        ->call('save')
        ->assertHasNoFormErrors();

    $hourlyTypeId = TimeTrackerItemType::findIdFor(TimeTrackerItemTypeEnum::HOURS);

    expect(DefaultRate::query()->where('customer_id', $customer->id)->count())->toBe(2)
        ->and((float) DefaultRate::query()->where('customer_id', $customer->id)->where('time_tracker_item_type_id', $hourlyTypeId)->value('rate'))->toBe(110.0);
});

it('requires both rates', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();

    actingAs($admin);

    fillDefaultRateForm([
        'customer_id' => $customer->id,
        'hourly_rate' => null,
        'daily_rate' => null,
    ])
        ->call('save')
        ->assertHasFormErrors([
            'hourly_rate' => 'required',
            'daily_rate' => 'required',
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

    actingAs($user);

    fillDefaultRateForm([
        'customer_id' => $otherCustomer->id,
        'hourly_rate' => 40,
        'daily_rate' => 300,
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

    $hourlyTypeId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::HOURS);
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

    livewire(ManageDefaultRates::class)
        ->assertOk()
        ->assertFormSet([
            'hourly_rate' => '55.00',
            'daily_rate' => '440.00',
        ]);
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
        'time_tracker_item_type_id' => TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::HOURS),
        'rate' => 50,
    ]);

    actingAs($admin);

    livewire(ViewUser::class, ['record' => $worker->id])
        ->assertOk()
        ->assertSee('50.00 (por defecto)')
        ->assertSee('Sin definir');
});
