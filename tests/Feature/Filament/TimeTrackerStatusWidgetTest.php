<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\CheckIns\Pages\ListCheckIns;
use App\Filament\Resources\TimeTrackers\Pages\CreateTimeTracker;
use App\Filament\Resources\TimeTrackers\Pages\EditTimeTracker;
use App\Filament\Resources\TimeTrackers\Pages\ListTimeTrackers;
use App\Filament\Resources\TimeTrackers\Pages\ViewTimeTracker;
use App\Filament\Widgets\TimeTrackerStatusStats;
use App\Http\Resources\V1\TimeTrackerResource;
use App\Models\Customer;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

function statusUser(UserRoleEnum $role, Customer $customer): User
{
    return User::factory()->create(['customer_id' => $customer->id, 'user_role_id' => $role->value]);
}

function statusTracker(Customer $customer, array $state): TimeTracker
{
    return TimeTracker::factory()->create(array_merge([
        'customer_id' => $customer->id,
        'user_id' => User::factory()->create(['customer_id' => $customer->id])->id,
        'approved' => false,
        'billed' => false,
        'paid' => false,
        'invoice_id' => null,
        'payment_id' => null,
        'approved_by' => null,
    ], $state));
}

/**
 * @return array<string, array{value: mixed, description: mixed}>
 */
function widgetStats(): array
{
    $stats = (fn () => $this->getStats())->call(new TimeTrackerStatusStats);
    $result = [];

    foreach ($stats as $stat) {
        $result[(string) $stat->getLabel()] = ['value' => $stat->getValue(), 'description' => (string) $stat->getDescription()];
    }

    return $result;
}

it('shows the time sheet status widget to platform admins, owners, administrators and managers only', function (): void {
    $customer = Customer::factory()->create();

    foreach ([UserRoleEnum::Owner, UserRoleEnum::Admin, UserRoleEnum::Manager] as $role) {
        actingAs(statusUser($role, $customer));

        livewire(Dashboard::class)->assertSeeLivewire(TimeTrackerStatusStats::class);
    }

    actingAs(User::factory()->admin()->create());
    livewire(Dashboard::class)->assertSeeLivewire(TimeTrackerStatusStats::class);

    actingAs(statusUser(UserRoleEnum::Employee, $customer));
    livewire(Dashboard::class)->assertDontSeeLivewire(TimeTrackerStatusStats::class);
});

it('counts each time sheet in exactly one state of its company', function (): void {
    $customer = Customer::factory()->create();
    $other = Customer::factory()->create();

    statusTracker($customer, []);
    statusTracker($customer, []);
    statusTracker($customer, ['approved' => true]);
    statusTracker($customer, ['approved' => true, 'billed' => true]);
    statusTracker($customer, ['approved' => true, 'billed' => true]);
    statusTracker($customer, ['approved' => true, 'billed' => true]);
    statusTracker($customer, ['approved' => true, 'billed' => true, 'paid' => true]);
    statusTracker($other, []);
    statusTracker($other, ['approved' => true, 'billed' => true]);

    actingAs(statusUser(UserRoleEnum::Manager, $customer));

    $stats = widgetStats();

    expect($stats['Pendientes de aprobación']['value'])->toBe(2)
        ->and($stats['Pendientes de facturar']['value'])->toBe(1)
        ->and($stats['Pendientes de pago']['value'])->toBe(3)
        ->and($stats['Pendientes de pago']['description'])->toBe('Planillas facturadas sin pagar');
});

it('lets platform admins see the counts of every company', function (): void {
    statusTracker(Customer::factory()->create(), []);
    statusTracker(Customer::factory()->create(), []);

    actingAs(User::factory()->admin()->create());

    expect(widgetStats()['Pendientes de aprobación']['value'])->toBe(2);
});

it('warns about billed time sheets whose expected payment date has passed', function (): void {
    $customer = Customer::factory()->create();

    statusTracker($customer, ['approved' => true, 'billed' => true, 'expected_payment_date' => today()->subDay()]);
    statusTracker($customer, ['approved' => true, 'billed' => true, 'expected_payment_date' => today()->subDays(10)]);
    statusTracker($customer, ['approved' => true, 'billed' => true, 'expected_payment_date' => today()]);
    statusTracker($customer, ['approved' => true, 'billed' => true, 'expected_payment_date' => null]);
    statusTracker($customer, ['approved' => true, 'billed' => true, 'paid' => true, 'expected_payment_date' => today()->subMonth()]);

    actingAs(statusUser(UserRoleEnum::Owner, $customer));

    $stat = widgetStats()['Pendientes de pago'];

    expect($stat['value'])->toBe(4)
        ->and($stat['description'])->toBe('Planillas facturadas sin pagar (2 con pago vencido)');
});

it('shows the widget texts in English when the locale changes', function (): void {
    actingAs(statusUser(UserRoleEnum::Owner, Customer::factory()->create()));
    app()->setLocale('en');

    expect(array_keys(widgetStats()))->toBe(['Pending approval', 'Pending invoicing', 'Pending payment']);
});

it('has no expected payment date by default', function (): void {
    $tracker = statusTracker(Customer::factory()->create(), []);

    expect($tracker->fresh()->expected_payment_date)->toBeNull();
});

it('saves the expected payment date from the create and edit forms', function (): void {
    foreach (TimeTrackerItemTypeEnum::cases() as $type) {
        TimeTrackerItemType::query()->insertOrIgnore(['id' => $type->value, 'name' => $type->label()]);
    }

    $customer = Customer::factory()->create();
    $admin = User::factory()->admin()->create();

    actingAs($admin);

    livewire(CreateTimeTracker::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'name' => 'Con fecha prevista',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-02',
            'expected_payment_date' => '2026-12-15',
            'items' => [['date' => '2026-10-01', 'time_tracker_item_type_id' => TimeTrackerItemTypeEnum::DAY->value]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tracker = TimeTracker::query()->where('name', 'Con fecha prevista')->firstOrFail();

    expect($tracker->expected_payment_date->format('Y-m-d'))->toBe('2026-12-15');

    livewire(EditTimeTracker::class, ['record' => $tracker->id])
        ->fillForm(['expected_payment_date' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($tracker->fresh()->expected_payment_date)->toBeNull();
});

it('lets the expected payment date stay empty when creating a time sheet', function (): void {
    foreach (TimeTrackerItemTypeEnum::cases() as $type) {
        TimeTrackerItemType::query()->insertOrIgnore(['id' => $type->value, 'name' => $type->label()]);
    }

    $admin = User::factory()->admin()->create();

    actingAs($admin);

    livewire(CreateTimeTracker::class)
        ->fillForm([
            'customer_id' => Customer::factory()->create()->id,
            'user_id' => $admin->id,
            'name' => 'Sin fecha prevista',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-02',
            'items' => [['date' => '2026-10-01', 'time_tracker_item_type_id' => TimeTrackerItemTypeEnum::DAY->value]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(TimeTracker::query()->where('name', 'Sin fecha prevista')->firstOrFail()->expected_payment_date)->toBeNull();
});

it('hides the expected payment date from employees', function (): void {
    actingAs(statusUser(UserRoleEnum::Employee, Customer::factory()->create()));

    livewire(CreateTimeTracker::class)->assertFormFieldIsHidden('expected_payment_date');
});

it('shows the expected payment date in the table and the detail page', function (): void {
    $customer = Customer::factory()->create();
    $tracker = statusTracker($customer, ['expected_payment_date' => '2026-12-15']);

    actingAs(statusUser(UserRoleEnum::Owner, $customer));

    livewire(ListTimeTrackers::class)->assertSee('15/12/2026');
    livewire(ViewTimeTracker::class, ['record' => $tracker->id])->assertSee('15/12/2026');
});

it('includes the expected payment date in the mobile API resource', function (): void {
    $tracker = statusTracker(Customer::factory()->create(), ['expected_payment_date' => '2026-12-15']);

    $data = (new TimeTrackerResource($tracker))->toArray(Request::create('/'));

    expect($data['expected_payment_date'])->toBe('2026-12-15');
});

it('shows first and last name in every user select', function (): void {
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id, 'name' => 'Lucía', 'lastname' => 'Fernández']);
    $tracker = statusTracker($customer, ['user_id' => $worker->id]);

    actingAs(statusUser(UserRoleEnum::Owner, $customer));

    livewire(CreateTimeTracker::class)
        ->assertFormFieldExists('user_id', fn ($field): bool => in_array('Lucía Fernández', $field->getOptions(), true));

    livewire(ListTimeTrackers::class)
        ->assertTableFilterExists('user_id', function ($filter) use ($worker): bool {
            return (fn () => ($this->getOptionLabelFromRecordUsing)($worker))->call($filter) === 'Lucía Fernández';
        });

    livewire(ListCheckIns::class)
        ->assertTableFilterExists('user_id', function ($filter) use ($worker): bool {
            return (fn () => ($this->getOptionLabelFromRecordUsing)($worker))->call($filter) === 'Lucía Fernández';
        });
});

it('hides the expected payment date from employees in the table and the detail page', function (): void {
    $customer = Customer::factory()->create();
    $employee = statusUser(UserRoleEnum::Employee, $customer);
    $tracker = statusTracker($customer, ['user_id' => $employee->id, 'expected_payment_date' => '2026-12-15']);

    actingAs($employee);

    livewire(ListTimeTrackers::class)
        ->assertCanSeeTableRecords([$tracker])
        ->assertTableColumnHidden('expected_payment_date')
        ->assertDontSee('15/12/2026');

    livewire(ViewTimeTracker::class, ['record' => $tracker->id])
        ->assertDontSee('15/12/2026')
        ->assertDontSee('Fecha de pago prevista');
});

it('shows the expected payment date to managers, administrators and owners', function (): void {
    $customer = Customer::factory()->create();
    $tracker = statusTracker($customer, ['expected_payment_date' => '2026-12-15']);

    foreach ([UserRoleEnum::Manager, UserRoleEnum::Admin, UserRoleEnum::Owner] as $role) {
        actingAs(statusUser($role, $customer));

        livewire(ListTimeTrackers::class)->assertTableColumnVisible('expected_payment_date');
        livewire(ViewTimeTracker::class, ['record' => $tracker->id])->assertSee('15/12/2026');
    }
});

it('leaves the expected payment date out of the mobile API for employees', function (): void {
    $customer = Customer::factory()->create();
    $employee = statusUser(UserRoleEnum::Employee, $customer);
    $manager = statusUser(UserRoleEnum::Manager, $customer);
    $tracker = statusTracker($customer, ['expected_payment_date' => '2026-12-15']);

    $asEmployee = Request::create('/');
    $asEmployee->setUserResolver(fn () => $employee);
    $asManager = Request::create('/');
    $asManager->setUserResolver(fn () => $manager);

    expect((new TimeTrackerResource($tracker))->resolve($asEmployee))->not->toHaveKey('expected_payment_date')
        ->and((new TimeTrackerResource($tracker))->resolve($asManager)['expected_payment_date'])->toBe('2026-12-15');
});
