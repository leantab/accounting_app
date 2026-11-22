<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\Project;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItem;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(TestCase::class, RefreshDatabase::class);

function colleague(User $of, UserRoleEnum $role): User
{
    return User::factory()->create(['customer_id' => $of->customer_id, 'user_role_id' => $role->value]);
}

function trackerFor(User $user, array $attributes = []): TimeTracker
{
    return TimeTracker::factory()->create([
        'customer_id' => $user->customer_id,
        'user_id' => $user->id,
        'project_id' => null,
        'invoice_id' => null,
        'payment_id' => null,
        'approved_by' => null,
        ...$attributes,
    ]);
}

/**
 * @return array<string, mixed>
 */
function trackerPayload(array $overrides = []): array
{
    return [
        'name' => 'Festival marzo',
        'date_start' => '2026-03-01',
        'date_end' => '2026-03-03',
        'description' => 'Armado y show',
        'items' => [],
        ...$overrides,
    ];
}

test('employees only list their own trackers', function (): void {
    $employee = User::factory()->customerEmployee()->create();
    $other = colleague($employee, UserRoleEnum::Employee);

    $own = trackerFor($employee);
    trackerFor($other);

    Sanctum::actingAs($employee);

    $response = getJson('/api/v1/time-trackers')->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$own->id]);
});

test('owners list every tracker of their customer and none of another', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $employee = colleague($owner, UserRoleEnum::Employee);
    $outsider = User::factory()->customerEmployee()->create();

    $mine = trackerFor($employee);
    trackerFor($outsider);

    Sanctum::actingAs($owner);

    $response = getJson('/api/v1/time-trackers')->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$mine->id])
        ->and($response->json('data.0.user.name'))->toBe($employee->fullName);
});

test('the status filter matches the tabs of the panel', function (): void {
    $owner = User::factory()->customerOwner()->create();

    $pending = trackerFor($owner, ['approved' => false]);
    $unbilled = trackerFor($owner, ['approved' => true, 'billed' => false]);
    $unpaid = trackerFor($owner, ['approved' => true, 'billed' => true, 'paid' => false]);
    $paid = trackerFor($owner, ['approved' => true, 'billed' => true, 'paid' => true]);

    Sanctum::actingAs($owner);

    $ids = fn (string $status): array => collect(getJson("/api/v1/time-trackers?status={$status}")->json('data'))
        ->pluck('id')
        ->all();

    expect($ids('pending_approval'))->toBe([$pending->id])
        ->and($ids('approved_unbilled'))->toBe([$unbilled->id])
        ->and($ids('pending_payment'))->toBe([$unpaid->id])
        ->and($ids('paid'))->toBe([$paid->id]);
});

test('an employee cannot open a tracker of a colleague or of another customer', function (): void {
    $employee = User::factory()->customerEmployee()->create();
    $colleagueTracker = trackerFor(colleague($employee, UserRoleEnum::Employee));
    $foreignTracker = trackerFor(User::factory()->customerEmployee()->create());

    Sanctum::actingAs($employee);

    getJson("/api/v1/time-trackers/{$colleagueTracker->id}")->assertNotFound();
    getJson("/api/v1/time-trackers/{$foreignTracker->id}")->assertNotFound();
    putJson("/api/v1/time-trackers/{$colleagueTracker->id}", trackerPayload())->assertNotFound();
    deleteJson("/api/v1/time-trackers/{$colleagueTracker->id}")->assertNotFound();
});

test('an employee creates a tracker for themself and cannot set billing fields', function (): void {
    $employee = User::factory()->customerEmployee()->create();
    $other = colleague($employee, UserRoleEnum::Employee);
    $hourly = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXTRA_HOUR);
    $expenses = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXPENSES);
    $day = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);

    Sanctum::actingAs($employee);

    $response = postJson('/api/v1/time-trackers', trackerPayload([
        'user_id' => $other->id,
        'billed' => true,
        'paid' => true,
        'items' => [
            ['date' => '2026-03-01', 'time_tracker_item_type_id' => $hourly, 'hours' => 3, 'time_start' => '20:00', 'time_end' => '23:00'],
            ['date' => '2026-03-02', 'time_tracker_item_type_id' => $expenses, 'description' => 'Taxi', 'amount' => '1500.50'],
            ['date' => '2026-03-03', 'time_tracker_item_type_id' => $day, 'hours' => 9, 'amount' => '999'],
        ],
    ]))->assertCreated();

    $tracker = TimeTracker::query()->findOrFail($response->json('data.id'));

    expect($tracker->user_id)->toBe($employee->id)
        ->and($tracker->customer_id)->toBe($employee->customer_id)
        ->and((bool) $tracker->billed)->toBeFalse()
        ->and((bool) $tracker->paid)->toBeFalse()
        ->and($response->json('data.items'))->toHaveCount(3)
        ->and($response->json('data.items.0'))->toMatchArray(['hours' => 3, 'time_start' => '20:00', 'time_end' => '23:00', 'type_key' => 'EXTRA_HOUR'])
        ->and($response->json('data.items.1'))->toMatchArray(['description' => 'Taxi', 'amount' => '1500.50', 'hours' => null])
        ->and($response->json('data.items.2'))->toMatchArray(['hours' => null, 'amount' => null]);
});

test('hourly items need their hours', function (): void {
    $employee = User::factory()->customerEmployee()->create();
    $hourly = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXTRA_HOUR);

    Sanctum::actingAs($employee);

    postJson('/api/v1/time-trackers', trackerPayload([
        'items' => [['date' => '2026-03-01', 'time_tracker_item_type_id' => $hourly]],
    ]))->assertUnprocessable()->assertJsonValidationErrors('items.0.hours');
});

test('a manager must choose a user of their own customer', function (): void {
    $manager = User::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'user_role_id' => UserRoleEnum::Manager->value,
    ]);
    $employee = colleague($manager, UserRoleEnum::Employee);
    $outsider = User::factory()->customerEmployee()->create();
    $foreignProject = Project::factory()->create(['customer_id' => $outsider->customer_id]);

    Sanctum::actingAs($manager);

    postJson('/api/v1/time-trackers', trackerPayload())->assertUnprocessable()->assertJsonValidationErrors('user_id');
    postJson('/api/v1/time-trackers', trackerPayload(['user_id' => $outsider->id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');
    postJson('/api/v1/time-trackers', trackerPayload(['user_id' => $employee->id, 'project_id' => $foreignProject->id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('project_id');

    postJson('/api/v1/time-trackers', trackerPayload(['user_id' => $employee->id, 'billed' => true]))
        ->assertCreated()
        ->assertJsonPath('data.user_id', $employee->id)
        ->assertJsonPath('data.billed', true);
});

test('updating replaces the items and keeps amounts of rated items', function (): void {
    $employee = User::factory()->customerEmployee()->create();
    $day = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    $tracker = trackerFor($employee);

    $kept = TimeTrackerItem::factory()->create([
        'time_tracker_id' => $tracker->id,
        'time_tracker_item_type_id' => $day,
        'hours' => null,
        'amount' => 800,
    ]);
    $removed = TimeTrackerItem::factory()->create(['time_tracker_id' => $tracker->id, 'time_tracker_item_type_id' => $day]);

    Sanctum::actingAs($employee);

    $response = putJson("/api/v1/time-trackers/{$tracker->id}", trackerPayload([
        'name' => 'Renombrada',
        'items' => [
            ['id' => $kept->id, 'date' => '2026-03-02', 'time_tracker_item_type_id' => $day, 'amount' => '1'],
            ['date' => '2026-03-03', 'time_tracker_item_type_id' => $day],
        ],
    ]))->assertOk();

    expect($response->json('data.name'))->toBe('Renombrada')
        ->and($response->json('data.items'))->toHaveCount(2)
        ->and($kept->refresh()->amount)->toBe('800.00')
        ->and($kept->date->format('Y-m-d'))->toBe('2026-03-02')
        ->and(TimeTrackerItem::query()->whereKey($removed->id)->exists())->toBeFalse();
});

test('a tracker can be deleted with its items', function (): void {
    $employee = User::factory()->customerEmployee()->create();
    $tracker = trackerFor($employee);
    TimeTrackerItem::factory()->create(['time_tracker_id' => $tracker->id]);

    Sanctum::actingAs($employee);

    deleteJson("/api/v1/time-trackers/{$tracker->id}")->assertOk();

    expect(TimeTracker::query()->whereKey($tracker->id)->exists())->toBeFalse()
        ->and(TimeTrackerItem::query()->where('time_tracker_id', $tracker->id)->exists())->toBeFalse();
});

test('owners approve a tracker and its owner is notified', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $employee = colleague($owner, UserRoleEnum::Employee);
    $tracker = trackerFor($employee, ['approved' => false]);

    Sanctum::actingAs($owner);

    postJson("/api/v1/time-trackers/{$tracker->id}/approve")
        ->assertOk()
        ->assertJsonPath('data.approved', true)
        ->assertJsonPath('data.approved_by.id', $owner->id);

    expect($employee->notifications()->count())->toBe(1)
        ->and($employee->notifications()->first()->data['title'])->toBe('Planilla aprobada');

    postJson("/api/v1/time-trackers/{$tracker->id}/approve")->assertUnprocessable();
});

test('managers and employees cannot approve or recalculate', function (UserRoleEnum $role): void {
    $owner = User::factory()->customerOwner()->create();
    $user = colleague($owner, $role);
    $tracker = trackerFor($user, ['approved' => false]);

    Sanctum::actingAs($user);

    postJson("/api/v1/time-trackers/{$tracker->id}/approve")->assertForbidden();
    postJson("/api/v1/time-trackers/{$tracker->id}/recalculate")->assertForbidden();

    expect((bool) $tracker->refresh()->approved)->toBeFalse();
})->with([UserRoleEnum::Manager, UserRoleEnum::Employee]);

test('recalculate applies the current rates and reports missing ones', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $employee = colleague($owner, UserRoleEnum::Employee);
    $day = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    $guard = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::GUARD);
    $tracker = trackerFor($employee, ['approved' => true, 'amount' => 0]);

    DefaultRate::query()->create(['customer_id' => $owner->customer_id, 'time_tracker_item_type_id' => $day, 'rate' => 5000]);
    TimeTrackerItem::factory()->create(['time_tracker_id' => $tracker->id, 'time_tracker_item_type_id' => $day, 'hours' => null, 'amount' => null]);
    TimeTrackerItem::factory()->create(['time_tracker_id' => $tracker->id, 'time_tracker_item_type_id' => $guard, 'hours' => null, 'amount' => null]);

    Sanctum::actingAs($owner);

    postJson("/api/v1/time-trackers/{$tracker->id}/recalculate")
        ->assertOk()
        ->assertJsonPath('data.amount', '5000.00')
        ->assertJsonPath('missing_rates', ['Guardia']);
});

test('auto complete calculates approved trackers without amount', function (): void {
    Queue::fake();

    $owner = User::factory()->customerOwner()->create();
    $employee = colleague($owner, UserRoleEnum::Employee);
    $day = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    $tracker = trackerFor($employee, ['approved' => true, 'amount' => null]);

    DefaultRate::query()->create(['customer_id' => $owner->customer_id, 'time_tracker_item_type_id' => $day, 'rate' => 7000]);
    TimeTrackerItem::factory()->create(['time_tracker_id' => $tracker->id, 'time_tracker_item_type_id' => $day, 'hours' => null, 'amount' => null]);

    Sanctum::actingAs($owner);

    postJson('/api/v1/time-trackers/auto-complete')
        ->assertOk()
        ->assertJsonPath('trackers', 1)
        ->assertJsonPath('missing_rates', []);

    expect($tracker->refresh()->amount)->toBe('7000.00');

    postJson('/api/v1/time-trackers/auto-complete')
        ->assertOk()
        ->assertJsonPath('trackers', 0)
        ->assertJsonPath('message', 'No hay planillas pendientes de calcular.');
});

test('auto complete is refused to managers and employees', function (): void {
    $owner = User::factory()->customerOwner()->create();

    foreach ([UserRoleEnum::Manager, UserRoleEnum::Employee] as $role) {
        Sanctum::actingAs(colleague($owner, $role));

        postJson('/api/v1/time-trackers/auto-complete')
            ->assertForbidden()
            ->assertJsonPath('message', 'No tenés permiso para calcular montos automáticamente.');

        expect(MobileAccess::abilities(colleague($owner, $role))['time_trackers']['auto_complete'])->toBeFalse();
    }

    expect(MobileAccess::abilities($owner)['time_trackers']['auto_complete'])->toBeTrue();
});

test('every item type accepts an optional description', function (): void {
    $employee = User::factory()->customerEmployee()->create();
    Sanctum::actingAs($employee);

    $day = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    $hour = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXTRA_HOUR);

    $response = postJson('/api/v1/time-trackers', [
        'name' => 'Con notas',
        'date_start' => '2026-03-01',
        'date_end' => '2026-03-02',
        'items' => [
            ['date' => '2026-03-01', 'time_tracker_item_type_id' => $day, 'description' => 'Armado de escenario'],
            ['date' => '2026-03-02', 'time_tracker_item_type_id' => $hour, 'hours' => 2],
        ],
    ])->assertCreated();

    expect($response->json('data.items.0.description'))->toBe('Armado de escenario')
        ->and($response->json('data.items.1.description'))->toBeNull();
});

test('platform admins choose the customer of a tracker', function (): void {
    $admin = User::factory()->admin()->create();
    $employee = User::factory()->customerEmployee()->create();

    Sanctum::actingAs($admin);

    postJson('/api/v1/time-trackers', trackerPayload(['user_id' => $employee->id]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('customer_id');

    postJson('/api/v1/time-trackers', trackerPayload(['customer_id' => $employee->customer_id, 'user_id' => $employee->id]))
        ->assertCreated()
        ->assertJsonPath('data.customer_id', $employee->customer_id);
});

test('options are limited by role and customer', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $employee = colleague($owner, UserRoleEnum::Employee);
    $outsider = User::factory()->customerEmployee()->create();

    Project::factory()->create(['customer_id' => $owner->customer_id, 'name' => 'Propio']);
    Project::factory()->create(['customer_id' => $outsider->customer_id, 'name' => 'Ajeno']);

    Sanctum::actingAs($employee);

    expect(collect(getJson('/api/v1/options/projects')->assertOk()->json('data'))->pluck('name')->all())->toBe(['Propio']);
    getJson('/api/v1/options/users')->assertForbidden();
    getJson('/api/v1/options/invoices')->assertForbidden();
    getJson('/api/v1/options/customers')->assertForbidden();
    getJson('/api/v1/options/nada')->assertNotFound();

    Sanctum::actingAs($owner);

    $users = collect(getJson('/api/v1/options/users')->assertOk()->json('data'))->pluck('id');

    expect($users)->toContain($owner->id, $employee->id)->not->toContain($outsider->id);
});
