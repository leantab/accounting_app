<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Models\UserRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

function individualRateTypeId(TimeTrackerItemTypeEnum $type): int
{
    return TimeTrackerItemType::ensureIdFor($type);
}

it('lists every item type except expenses and travel as individual rates', function (): void {
    $names = TimeTrackerItemType::withRates()->pluck('name')->all();

    expect($names)->toHaveCount(9)
        ->and($names)->not->toContain('Gastos', 'Viáticos')
        ->and($names)->toContain('Hora extra', 'Jornal', 'Guardia', 'Depósito');
});

it('shows every individual rate on the user view page, falling back to default', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);

    DefaultRate::query()->create([
        'customer_id' => $customer->id,
        'time_tracker_item_type_id' => individualRateTypeId(TimeTrackerItemTypeEnum::DAY),
        'rate' => 800,
    ]);
    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $worker->id,
        'time_tracker_item_type_id' => individualRateTypeId(TimeTrackerItemTypeEnum::EXTRA_HOUR),
        'rate' => 77.5,
    ]);

    actingAs(User::factory()->admin()->create());

    livewire(ViewUser::class, ['record' => $worker->id])
        ->assertOk()
        ->assertSee('Tarifas individuales')
        ->assertSee('77.50')
        ->assertSee('Por defecto (800.00)')
        ->assertSee('Doble Jornal')
        ->assertDontSee('Gastos');
});

it('shows the individual rates section even when the user has none', function (): void {
    $worker = User::factory()->create();

    actingAs(User::factory()->admin()->create());

    livewire(ViewUser::class, ['record' => $worker->id])
        ->assertSee('Tarifas individuales')
        ->assertSee('Por defecto');
});

it('shows an input for every item type except expenses and travel on the edit form', function (): void {
    $worker = User::factory()->create();
    $dayId = individualRateTypeId(TimeTrackerItemTypeEnum::DAY);
    $expensesId = individualRateTypeId(TimeTrackerItemTypeEnum::EXPENSES);
    $travelId = individualRateTypeId(TimeTrackerItemTypeEnum::TRAVEL);

    actingAs(User::factory()->admin()->create());

    livewire(EditUser::class, ['record' => $worker->id])
        ->assertFormFieldExists("individualRates.{$dayId}")
        ->assertFormFieldDoesNotExist("individualRates.{$expensesId}")
        ->assertFormFieldDoesNotExist("individualRates.{$travelId}");
});

it('loads, saves and clears individual rates from the edit form', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $hourId = individualRateTypeId(TimeTrackerItemTypeEnum::EXTRA_HOUR);
    $dayId = individualRateTypeId(TimeTrackerItemTypeEnum::DAY);

    UserRate::query()->create([
        'customer_id' => $customer->id,
        'user_id' => $worker->id,
        'time_tracker_item_type_id' => $hourId,
        'rate' => 50,
    ]);

    actingAs(User::factory()->admin()->create());

    livewire(EditUser::class, ['record' => $worker->id])
        ->assertFormSet(["individualRates.{$hourId}" => 50.0])
        ->fillForm([
            "individualRates.{$hourId}" => null,
            "individualRates.{$dayId}" => 900,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $rates = UserRate::query()->where('user_id', $worker->id)->pluck('rate', 'time_tracker_item_type_id');

    expect($rates)->toHaveCount(1)
        ->and((float) $rates[$dayId])->toBe(900.0)
        ->and($rates->has($hourId))->toBeFalse();
});

it('updates an existing individual rate without duplicating it', function (): void {
    $worker = User::factory()->create();
    $dayId = individualRateTypeId(TimeTrackerItemTypeEnum::DAY);

    actingAs(User::factory()->admin()->create());

    foreach ([700, 750] as $rate) {
        livewire(EditUser::class, ['record' => $worker->id])
            ->fillForm(["individualRates.{$dayId}" => $rate])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    expect(UserRate::query()->where('user_id', $worker->id)->count())->toBe(1)
        ->and((float) UserRate::query()->where('user_id', $worker->id)->value('rate'))->toBe(750.0);
});
