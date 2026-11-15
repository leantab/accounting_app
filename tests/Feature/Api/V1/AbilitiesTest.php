<?php

use App\Enums\UserRoleEnum;
use App\Models\Customer;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array<string, bool>
 */
function sectionVisibility(User $user): array
{
    $abilities = MobileAccess::abilities($user);

    return [
        'customers' => $abilities['customers']['view'],
        'users' => $abilities['users']['view'],
        'companies' => $abilities['companies']['view'],
        'invoices' => $abilities['invoices']['view'],
        'payments' => $abilities['payments']['view'],
        'projects' => $abilities['projects']['view'],
        'time_trackers' => $abilities['time_trackers']['view'],
        'approve' => $abilities['time_trackers']['approve'],
        'check_ins_review' => $abilities['check_ins']['view'],
        'check_ins_record' => $abilities['check_ins']['record'],
        'default_rates' => $abilities['default_rates']['view'],
        'stats_overview' => $abilities['dashboard']['stats_overview'],
        'time_tracker_stats' => $abilities['dashboard']['time_tracker_stats'],
    ];
}

function tenantUser(UserRoleEnum $role): User
{
    return User::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'user_role_id' => $role->value,
    ]);
}

test('platform admins see every section but do not record attendance', function (): void {
    expect(sectionVisibility(User::factory()->admin()->create()))->toBe([
        'customers' => true,
        'users' => true,
        'companies' => true,
        'invoices' => true,
        'payments' => true,
        'projects' => true,
        'time_trackers' => true,
        'approve' => true,
        'check_ins_review' => true,
        'check_ins_record' => false,
        'default_rates' => true,
        'stats_overview' => true,
        'time_tracker_stats' => false,
    ]);
});

test('owners and admins manage their customer', function (UserRoleEnum $role): void {
    expect(sectionVisibility(tenantUser($role)))->toBe([
        'customers' => false,
        'users' => true,
        'companies' => true,
        'invoices' => true,
        'payments' => true,
        'projects' => true,
        'time_trackers' => true,
        'approve' => true,
        'check_ins_review' => true,
        'check_ins_record' => false,
        'default_rates' => true,
        'stats_overview' => true,
        'time_tracker_stats' => false,
    ]);
})->with([UserRoleEnum::Owner, UserRoleEnum::Admin]);

test('managers see projects, time trackers and attendance', function (): void {
    expect(sectionVisibility(tenantUser(UserRoleEnum::Manager)))->toBe([
        'customers' => false,
        'users' => false,
        'companies' => false,
        'invoices' => false,
        'payments' => false,
        'projects' => true,
        'time_trackers' => true,
        'approve' => false,
        'check_ins_review' => true,
        'check_ins_record' => true,
        'default_rates' => false,
        'stats_overview' => false,
        'time_tracker_stats' => true,
    ]);
});

test('employees only see their time trackers and record attendance', function (): void {
    $abilities = MobileAccess::abilities(tenantUser(UserRoleEnum::Employee));

    expect(sectionVisibility(tenantUser(UserRoleEnum::Employee)))->toBe([
        'customers' => false,
        'users' => false,
        'companies' => false,
        'invoices' => false,
        'payments' => false,
        'projects' => false,
        'time_trackers' => true,
        'approve' => false,
        'check_ins_review' => false,
        'check_ins_record' => true,
        'default_rates' => false,
        'stats_overview' => false,
        'time_tracker_stats' => true,
    ])
        ->and($abilities['time_trackers']['manage_billing'])->toBeFalse()
        ->and($abilities['time_trackers']['choose_user'])->toBeFalse();
});
