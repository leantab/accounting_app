<?php

namespace App\Support\Api;

use App\Enums\UserRoleEnum;
use App\Models\CheckIn;
use App\Models\User;

/**
 * Role matrix for the mobile API, mirroring the access checks of the Filament panel.
 *
 * Kept out of app/Policies on purpose: auto-discovered policies would also be applied by Filament to the panel.
 */
final class MobileAccess
{
    public static function canUseApp(User $user): bool
    {
        return $user->is_admin || $user->customer_id !== null;
    }

    public static function hasTenantRole(User $user, UserRoleEnum ...$roles): bool
    {
        if ($user->customer_id === null) {
            return false;
        }

        foreach ($roles as $role) {
            if ((int) $user->user_role_id === $role->value) {
                return true;
            }
        }

        return false;
    }

    public static function isEmployee(User $user): bool
    {
        return ! $user->is_admin && (int) $user->user_role_id === UserRoleEnum::Employee->value;
    }

    /**
     * Platform admins, and the owners and admins of a customer.
     */
    public static function managesCustomer(User $user): bool
    {
        return $user->is_admin || self::hasTenantRole($user, UserRoleEnum::Owner, UserRoleEnum::Admin);
    }

    public static function managesCustomers(User $user): bool
    {
        return (bool) $user->is_admin;
    }

    public static function managesProjects(User $user): bool
    {
        return $user->is_admin
            || self::hasTenantRole($user, UserRoleEnum::Owner, UserRoleEnum::Admin, UserRoleEnum::Manager);
    }

    public static function usesTimeTrackers(User $user): bool
    {
        return self::canUseApp($user);
    }

    public static function approvesTimeTrackers(User $user): bool
    {
        return self::managesCustomer($user);
    }

    public static function reviewsCheckIns(User $user): bool
    {
        return CheckIn::canBeReviewedBy($user);
    }

    public static function recordsCheckIns(User $user): bool
    {
        return CheckIn::canBeRecordedBy($user);
    }

    public static function seesOwnTimeTrackerStats(User $user): bool
    {
        return ! $user->is_admin
            && in_array((int) $user->user_role_id, [UserRoleEnum::Manager->value, UserRoleEnum::Employee->value], true);
    }

    /**
     * What the app may show and do, per section.
     *
     * @return array<string, array<string, bool>>
     */
    public static function abilities(User $user): array
    {
        $managesCustomer = self::managesCustomer($user);
        $managesProjects = self::managesProjects($user);
        $usesTimeTrackers = self::usesTimeTrackers($user);

        return [
            'dashboard' => [
                'stats_overview' => $managesCustomer,
                'time_tracker_stats' => self::seesOwnTimeTrackerStats($user),
            ],
            'customers' => self::crud(self::managesCustomers($user)),
            'users' => [...self::crud($managesCustomer), 'invite' => $managesCustomer],
            'companies' => self::crud($managesCustomer),
            'invoices' => self::crud($managesCustomer),
            'payments' => self::crud($managesCustomer),
            'projects' => self::crud($managesProjects),
            'time_trackers' => [
                'view' => $usesTimeTrackers,
                'create' => $usesTimeTrackers,
                'update' => $usesTimeTrackers,
                'delete' => $usesTimeTrackers,
                'approve' => self::approvesTimeTrackers($user),
                'recalculate' => self::approvesTimeTrackers($user),
                'auto_complete' => $usesTimeTrackers,
                'manage_billing' => $usesTimeTrackers && ! self::isEmployee($user),
                'choose_user' => $usesTimeTrackers && ! self::isEmployee($user),
            ],
            'check_ins' => [
                'view' => self::reviewsCheckIns($user),
                'record' => self::recordsCheckIns($user),
            ],
            'default_rates' => [
                'view' => $managesCustomer,
                'update' => $managesCustomer,
            ],
            'platform' => [
                'choose_customer' => (bool) $user->is_admin,
            ],
        ];
    }

    /**
     * @return array{view: bool, create: bool, update: bool, delete: bool}
     */
    private static function crud(bool $allowed): array
    {
        return [
            'view' => $allowed,
            'create' => $allowed,
            'update' => $allowed,
            'delete' => $allowed,
        ];
    }
}
