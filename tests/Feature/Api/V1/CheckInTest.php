<?php

use App\Enums\CheckInTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\CheckIn;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(TestCase::class, RefreshDatabase::class);

test('an employee records entry then exit', function (): void {
    $employee = User::factory()->customerEmployee()->create();
    $project = Project::factory()->create(['customer_id' => $employee->customer_id]);

    Sanctum::actingAs($employee);

    getJson('/api/v1/check-ins/mine')
        ->assertOk()
        ->assertJsonPath('next_type.value', CheckInTypeEnum::In->value)
        ->assertJsonCount(0, 'data');

    postJson('/api/v1/check-ins', [
        'latitude' => -34.6037,
        'longitude' => -58.3816,
        'accuracy' => 12.6,
        'project_id' => $project->id,
        'notes' => 'Llegué',
    ])
        ->assertCreated()
        ->assertJsonPath('message', 'Entrada registrada')
        ->assertJsonPath('next_type.label', 'Salida')
        ->assertJsonPath('data.0.accuracy_m', 13)
        ->assertJsonPath('data.0.project.id', $project->id);

    postJson('/api/v1/check-ins', ['latitude' => -34.6, 'longitude' => -58.38])
        ->assertCreated()
        ->assertJsonPath('message', 'Salida registrada')
        ->assertJsonPath('next_type.value', CheckInTypeEnum::In->value)
        ->assertJsonCount(2, 'data');

    expect(CheckIn::query()->where('user_id', $employee->id)->where('customer_id', $employee->customer_id)->count())->toBe(2);
});

test('the location and project are validated', function (): void {
    $employee = User::factory()->customerEmployee()->create();
    $foreignProject = Project::factory()->create();

    Sanctum::actingAs($employee);

    postJson('/api/v1/check-ins', ['latitude' => 120, 'longitude' => -58])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('latitude');

    postJson('/api/v1/check-ins', ['latitude' => -34, 'longitude' => -58, 'project_id' => $foreignProject->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('project_id');

    expect(CheckIn::query()->count())->toBe(0);
});

test('owners review attendance but do not record it', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $employee = User::factory()->create(['customer_id' => $owner->customer_id, 'user_role_id' => UserRoleEnum::Employee->value]);
    $outsider = User::factory()->customerEmployee()->create();

    $own = CheckIn::factory()->create(['customer_id' => $owner->customer_id, 'user_id' => $employee->id]);
    CheckIn::factory()->create(['customer_id' => $outsider->customer_id, 'user_id' => $outsider->id]);

    Sanctum::actingAs($owner);

    $response = getJson('/api/v1/check-ins')->assertOk();

    expect(collect($response->json('data'))->pluck('id')->all())->toBe([$own->id])
        ->and($response->json('data.0.type_label'))->toBe('Entrada')
        ->and($response->json('data.0.maps_url'))->toContain('google.com/maps');

    postJson('/api/v1/check-ins', ['latitude' => -34, 'longitude' => -58])->assertForbidden();
    getJson('/api/v1/check-ins/mine')->assertForbidden();
});

test('employees cannot review attendance', function (): void {
    Sanctum::actingAs(User::factory()->customerEmployee()->create());

    getJson('/api/v1/check-ins')->assertForbidden();
});

test('attendance can be filtered by type and worker', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $ana = User::factory()->create(['customer_id' => $owner->customer_id, 'name' => 'Ana', 'lastname' => 'Suarez']);
    $beto = User::factory()->create(['customer_id' => $owner->customer_id, 'name' => 'Beto', 'lastname' => 'Lopez']);

    $in = CheckIn::factory()->create(['customer_id' => $owner->customer_id, 'user_id' => $ana->id]);
    $out = CheckIn::factory()->out()->create(['customer_id' => $owner->customer_id, 'user_id' => $beto->id]);

    Sanctum::actingAs($owner);

    $ids = fn (string $query): array => collect(getJson("/api/v1/check-ins?{$query}")->assertOk()->json('data'))->pluck('id')->all();

    expect($ids('type=2'))->toBe([$out->id])
        ->and($ids("user_id={$ana->id}"))->toBe([$in->id])
        ->and($ids('search=lopez'))->toBe([$out->id]);
});
