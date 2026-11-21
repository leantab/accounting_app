<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeTracker;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $sections = [];

        if (MobileAccess::managesCustomer($user)) {
            $users = User::query()
                ->when(! $user->is_admin, fn ($query) => $query->where('customer_id', $user->customer_id));

            $sections[] = [
                'key' => 'stats_overview',
                'heading' => __('dashboard.summary'),
                'stats' => [
                    $this->stat('users', __('users.navigation'), $users->count(), __('dashboard.registered_users'), 'primary'),
                    $this->stat('companies', __('companies.plural'), Company::query()->count(), __('dashboard.registered_companies'), 'gray'),
                    $this->stat('projects', __('projects.plural'), Project::query()->count(), __('dashboard.registered_projects'), 'info'),
                ],
            ];
        }

        if (MobileAccess::seesOwnTimeTrackerStats($user)) {
            $ownTrackers = fn () => TimeTracker::query()->where('user_id', $user->id);

            $sections[] = [
                'key' => 'time_tracker_stats',
                'heading' => __('dashboard.my_time_trackers'),
                'stats' => [
                    $this->stat(
                        'pending_approval',
                        __('dashboard.pending_approval'),
                        $ownTrackers()->where('approved', false)->count(),
                        __('dashboard.awaiting_approval'),
                        'warning',
                    ),
                    $this->stat(
                        'approved_unpaid',
                        __('dashboard.approved_unpaid'),
                        $ownTrackers()->where('approved', true)->where('paid', false)->count(),
                        __('dashboard.approved_pending_payment'),
                        'info',
                    ),
                ],
            ];
        }

        return response()->json([
            'data' => [
                'sections' => $sections,
                'badges' => [
                    'time_trackers_pending' => TimeTracker::query()
                        ->when(MobileAccess::isEmployee($user), fn ($query) => $query->where('user_id', $user->id))
                        ->where('approved', false)
                        ->count(),
                    'invoices_unpaid' => MobileAccess::managesCustomer($user)
                        ? Invoice::query()->where('payed', false)->count()
                        : null,
                    'notifications_unread' => $user->unreadNotifications()->count(),
                ],
            ],
        ]);
    }

    /**
     * @return array{key: string, label: string, value: int, description: string, color: string}
     */
    private function stat(string $key, string $label, int $value, string $description, string $color): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'description' => $description,
            'color' => $color,
        ];
    }
}
