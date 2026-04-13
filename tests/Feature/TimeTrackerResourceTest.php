<?php
/*
use App\Enums\UserRoleEnum;
use App\Filament\Resources\TimeTrackers\Pages\CreateTimeTracker;
use App\Filament\Resources\TimeTrackers\Pages\EditTimeTracker;
use App\Filament\Resources\TimeTrackers\Pages\ListTimeTrackers;
use App\Filament\Resources\TimeTrackers\Pages\ViewTimeTracker;
use App\Models\Customer;
use App\Models\Project;
use App\Models\TimeTracker;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use function Pest\Laravel\actingAs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $customer = Customer::factory()->create();

    $customerAdmin = User::factory()->create([
        'is_admin' => false,
        'customer_id' => $customer->id,
        'user_role_id' => UserRoleEnum::Admin->value,
    ]);

    $employee = User::factory()->create([
        'is_admin' => false,
        'customer_id' => $customer->id,
        'user_role_id' => UserRoleEnum::Employee->value,
    ]);

    $project = Project::factory()->create([
        'customer_id' => $customer->id,
    ]);
});

it('renders list page and shows records by permission', function () {
    $adminTimeTracker = TimeTracker::factory()->create();
    $employeeTracker = TimeTracker::factory()->create([
        'customer_id' => $customer->id,
        'user_id' => $employee->id,
        'project_id' => $project->id,
    ]);

    // Admin sees all
    actingAs($admin);

    Live(ListTimeTrackers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$adminTimeTracker, $employeeTracker]);

    // Employee sees only theirs
    actingAs($employee);
    Livewire::test(ListTimeTrackers::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$employeeTracker])
        ->assertCanNotSeeTableRecords([$adminTimeTracker]);
});

it('can render create form and save time tracker', function () {
    actingAs($this->employee);

    Livewire::test(CreateTimeTracker::class)
        ->assertSuccessful()
        ->fillForm([
            'customer_id' => $this->customer->id,
            'user_id' => $this->employee->id,
            'project_id' => $this->project->id,
            'name' => 'Support task',
            'date_start' => now()->format('Y-m-d'),
            'date_end' => now()->addDay()->format('Y-m-d'),
            'items' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseHas('time_trackers', [
        'name' => 'Support task',
        'user_id' => $this->employee->id,
    ]);
});

it('handles employee field restrictions on create', function () {
    actingAs($this->employee);

    Livewire::test(CreateTimeTracker::class)
        ->assertFormFieldHidden('billed')
        ->assertFormFieldHidden('invoice_id')
        ->assertFormFieldHidden('paid')
        ->assertFormFieldHidden('payment_id');
});

it('handles customer admin field visibility on create', function () {
    actingAs($this->customerAdmin);

    Livewire::test(CreateTimeTracker::class)
        ->assertFormFieldVisible('billed')
        ->assertFormFieldVisible('invoice_id')
        ->assertFormFieldVisible('paid')
        ->assertFormFieldVisible('payment_id');
});

it('can render edit form and update data', function () {
    actingAs($this->admin);

    $timeTracker = TimeTracker::factory()->create([
        'customer_id' => $this->customer->id,
        'user_id' => $this->employee->id,
        'project_id' => $this->project->id,
        'name' => 'Initial name'
    ]);

    Livewire::test(EditTimeTracker::class, [
        'record' => $timeTracker->getRouteKey(),
    ])
        ->assertSuccessful()
        ->assertSchemaStateSet(['name' => 'Initial name'])
        ->fillForm([
            'name' => 'Updated name'
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseHas('time_trackers', [
        'id' => $timeTracker->id,
        'name' => 'Updated name'
    ]);
});

it('can render view page', function () {
    actingAs($this->employee);

    $timeTracker = TimeTracker::factory()->create([
        'customer_id' => $this->customer->id,
        'user_id' => $this->employee->id,
        'name' => 'Test Viewing'
    ]);

    Livewire::test(ViewTimeTracker::class, [
        'record' => $timeTracker->getRouteKey(),
    ])->assertSuccessful();
});

it('can approve time tracker and send database notification', function () {
    actingAs($this->customerAdmin); // Needs Admin role

    $timeTracker = TimeTracker::factory()->create([
        'customer_id' => $this->customer->id,
        'user_id' => $this->employee->id,
        'approved' => false,
    ]);

    Livewire::test(ViewTimeTracker::class, [
        'record' => $timeTracker->getRouteKey(),
    ])
        ->assertActionVisible('approve')
        ->callAction('approve');

    $this->assertDatabaseHas('time_trackers', [
        'id' => $timeTracker->id,
        'approved' => true,
        'approved_by' => $this->customerAdmin->id,
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $this->employee->id,
        'notifiable_type' => User::class,
    ]);
});

it('hides approve action for employee and already approved records', function () {
    $timeTracker = TimeTracker::factory()->create([
        'customer_id' => $this->customer->id,
        'user_id' => $this->employee->id,
        'approved' => true,
    ]);

    actingAs($this->customerAdmin);
    Livewire::test(ViewTimeTracker::class, [
        'record' => $timeTracker->getRouteKey(),
    ])->assertActionHidden('approve');

    actingAs($this->employee);
    Livewire::test(ViewTimeTracker::class, [
        'record' => $timeTracker->getRouteKey(),
    ])->assertActionHidden('approve');
});*/
