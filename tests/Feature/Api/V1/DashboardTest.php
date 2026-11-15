<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

use function Pest\Laravel\getJson;

uses(TestCase::class, RefreshDatabase::class);

test('owners get the overview counted for their customer only', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $otherCustomer = Customer::factory()->create();

    User::factory()->create(['customer_id' => $owner->customer_id]);
    User::factory()->count(3)->create(['customer_id' => $otherCustomer->id]);

    $company = Company::factory()->create(['customer_id' => $owner->customer_id]);
    $otherCompany = Company::factory()->create(['customer_id' => $otherCustomer->id]);

    Project::factory()->create(['customer_id' => $owner->customer_id, 'company_id' => $company->id]);
    Project::factory()->count(2)->create(['customer_id' => $otherCustomer->id, 'company_id' => $otherCompany->id]);

    Invoice::factory()->create([
        'customer_id' => $owner->customer_id,
        'from_company_id' => $company->id,
        'to_company_id' => $company->id,
        'payed' => false,
    ]);

    Sanctum::actingAs($owner);

    $response = getJson('/api/v1/dashboard')->assertOk();

    expect($response->json('data.sections'))->toHaveCount(1)
        ->and($response->json('data.sections.0.key'))->toBe('stats_overview')
        ->and(collect($response->json('data.sections.0.stats'))->pluck('value', 'key')->all())->toBe([
            'users' => 2,
            'companies' => 1,
            'projects' => 1,
        ])
        ->and($response->json('data.badges.invoices_unpaid'))->toBe(1);
});

test('employees get their own time tracker stats and no invoice badge', function (): void {
    $employee = User::factory()->customerEmployee()->create();
    $colleague = User::factory()->create([
        'customer_id' => $employee->customer_id,
        'user_role_id' => UserRoleEnum::Employee->value,
    ]);

    TimeTracker::factory()->create(['customer_id' => $employee->customer_id, 'user_id' => $employee->id, 'approved' => false]);
    TimeTracker::factory()->create(['customer_id' => $employee->customer_id, 'user_id' => $employee->id, 'approved' => true, 'paid' => false]);
    TimeTracker::factory()->count(2)->create(['customer_id' => $employee->customer_id, 'user_id' => $colleague->id, 'approved' => false]);

    Sanctum::actingAs($employee);

    $response = getJson('/api/v1/dashboard')->assertOk();

    expect($response->json('data.sections.0.key'))->toBe('time_tracker_stats')
        ->and(collect($response->json('data.sections.0.stats'))->pluck('value', 'key')->all())->toBe([
            'pending_approval' => 1,
            'approved_unpaid' => 1,
        ])
        ->and($response->json('data.badges.time_trackers_pending'))->toBe(1)
        ->and($response->json('data.badges.invoices_unpaid'))->toBeNull();
});

test('lookups list roles, project statuses and item types with their flags', function (): void {
    UserRole::query()->create(['name' => 'Owner']);
    ProjectStatus::query()->create(['name' => 'Borrador']);
    TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXTRA_HOUR);
    TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXPENSES);

    Sanctum::actingAs(User::factory()->customerEmployee()->create());

    $response = getJson('/api/v1/lookups')->assertOk();

    $types = collect($response->json('data.time_tracker_item_types'))->keyBy('key');

    expect($response->json('data.user_roles.0.name'))->toBe('Owner')
        ->and($response->json('data.project_statuses.0.name'))->toBe('Borrador')
        ->and($types['EXTRA_HOUR'])->toMatchArray(['name' => 'Hora extra', 'has_rate' => true, 'is_hourly' => true])
        ->and($types['EXPENSES'])->toMatchArray(['name' => 'Gastos', 'has_rate' => false, 'is_hourly' => false]);
});

test('lookups and dashboard require a token', function (): void {
    getJson('/api/v1/lookups')->assertUnauthorized();
    getJson('/api/v1/dashboard')->assertUnauthorized();
});
