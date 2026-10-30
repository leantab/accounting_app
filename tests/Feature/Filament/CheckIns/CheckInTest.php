<?php

use App\Enums\CheckInTypeEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Pages\RegisterCheckIn;
use App\Filament\Resources\CheckIns\CheckInResource;
use App\Filament\Resources\CheckIns\Pages\ListCheckIns;
use App\Models\CheckIn;
use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

function checkInUserWithRole(UserRoleEnum $role, ?Customer $customer = null): User
{
    return User::factory()->create([
        'customer_id' => ($customer ?? Customer::factory()->create())->id,
        'user_role_id' => $role->value,
    ]);
}

it('records an entry with the location and the server time', function (): void {
    $employee = checkInUserWithRole(UserRoleEnum::Employee);

    actingAs($employee);

    livewire(RegisterCheckIn::class)
        ->call('record', -34.6037, -58.3816, 12.4)
        ->assertNotified('Entrada registrada');

    $checkIn = CheckIn::query()->firstOrFail();

    expect($checkIn->user_id)->toBe($employee->id)
        ->and($checkIn->customer_id)->toBe($employee->customer_id)
        ->and($checkIn->type)->toBe(CheckInTypeEnum::In)
        ->and($checkIn->project_id)->toBeNull()
        ->and((float) $checkIn->latitude)->toBe(-34.6037)
        ->and((float) $checkIn->longitude)->toBe(-58.3816)
        ->and($checkIn->accuracy_m)->toBe(12)
        ->and($checkIn->recorded_at->diffInSeconds(now(), true))->toBeLessThan(5);
});

it('alternates between entry and exit', function (): void {
    $employee = checkInUserWithRole(UserRoleEnum::Employee);

    actingAs($employee);

    $component = livewire(RegisterCheckIn::class)
        ->call('record', -34.6, -58.4, 10)
        ->call('record', -34.6, -58.4, 10)
        ->assertNotified('Salida registrada')
        ->call('record', -34.6, -58.4, 10)
        ->assertNotified('Entrada registrada');

    expect(CheckIn::query()->orderBy('id')->pluck('type')->all())
        ->toBe([CheckInTypeEnum::In, CheckInTypeEnum::Out, CheckInTypeEnum::In]);
});

it('stores the optional project and notes', function (): void {
    $customer = Customer::factory()->create();
    $employee = checkInUserWithRole(UserRoleEnum::Employee, $customer);
    $project = Project::factory()->create(['customer_id' => $customer->id]);

    actingAs($employee);

    livewire(RegisterCheckIn::class)
        ->fillForm(['project_id' => $project->id, 'notes' => 'Galpón norte'])
        ->call('record', -34.6, -58.4, 10);

    $checkIn = CheckIn::query()->firstOrFail();

    expect($checkIn->project_id)->toBe($project->id)
        ->and($checkIn->notes)->toBe('Galpón norte');
});

it('rejects a project from another company', function (): void {
    $employee = checkInUserWithRole(UserRoleEnum::Employee);
    $foreignProject = Project::factory()->create(['customer_id' => Customer::factory()->create()->id]);

    actingAs($employee);

    livewire(RegisterCheckIn::class)
        ->fillForm(['project_id' => $foreignProject->id])
        ->call('record', -34.6, -58.4, 10);

    expect(CheckIn::query()->count())->toBe(0);
});

it('rejects coordinates out of range', function (): void {
    $employee = checkInUserWithRole(UserRoleEnum::Employee);

    actingAs($employee);

    livewire(RegisterCheckIn::class)
        ->call('record', 120, -58.4, 10)
        ->assertNotified('No se pudo registrar la asistencia');

    expect(CheckIn::query()->count())->toBe(0);
});

it('lets employees and managers check in but not owners, admins or panel admins', function (UserRoleEnum $role, bool $allowed): void {
    actingAs(checkInUserWithRole($role));

    expect(RegisterCheckIn::canAccess())->toBe($allowed);
})->with([
    'employee' => [UserRoleEnum::Employee, true],
    'manager' => [UserRoleEnum::Manager, true],
    'owner' => [UserRoleEnum::Owner, false],
    'admin' => [UserRoleEnum::Admin, false],
]);

it('does not let the platform admin check in', function (): void {
    actingAs(User::factory()->admin()->create());

    expect(RegisterCheckIn::canAccess())->toBeFalse();
});

it('blocks recording for users who cannot check in', function (): void {
    actingAs(checkInUserWithRole(UserRoleEnum::Owner));

    livewire(RegisterCheckIn::class)->assertForbidden();
});

it('lets owners, admins and managers review check-ins but not employees', function (UserRoleEnum $role, bool $allowed): void {
    actingAs(checkInUserWithRole($role));

    expect(CheckInResource::canAccess())->toBe($allowed);
})->with([
    'owner' => [UserRoleEnum::Owner, true],
    'admin' => [UserRoleEnum::Admin, true],
    'manager' => [UserRoleEnum::Manager, true],
    'employee' => [UserRoleEnum::Employee, false],
]);

it('only lists the check-ins of the manager company', function (): void {
    $customer = Customer::factory()->create();
    $manager = checkInUserWithRole(UserRoleEnum::Manager, $customer);
    $own = CheckIn::factory()->create(['customer_id' => $customer->id, 'user_id' => $manager->id]);
    $foreign = CheckIn::factory()->create();

    actingAs($manager);

    livewire(ListCheckIns::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('builds a maps link for the recorded location', function (): void {
    $checkIn = CheckIn::factory()->create(['latitude' => -34.6037, 'longitude' => -58.3816]);

    expect($checkIn->mapsUrl())->toContain('-34.6037000,-58.3816000');
});
