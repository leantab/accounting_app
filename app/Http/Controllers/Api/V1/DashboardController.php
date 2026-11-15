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
                'heading' => 'Resumen',
                'stats' => [
                    $this->stat('users', 'Usuarios', $users->count(), 'Usuarios registrados', 'primary'),
                    $this->stat('companies', 'Empresas', Company::query()->count(), 'Empresas registradas', 'gray'),
                    $this->stat('projects', 'Proyectos', Project::query()->count(), 'Proyectos registrados', 'info'),
                ],
            ];
        }

        if (MobileAccess::seesOwnTimeTrackerStats($user)) {
            $ownTrackers = fn () => TimeTracker::query()->where('user_id', $user->id);

            $sections[] = [
                'key' => 'time_tracker_stats',
                'heading' => 'Mis planillas de horas',
                'stats' => [
                    $this->stat(
                        'pending_approval',
                        'Pendientes de aprobación',
                        $ownTrackers()->where('approved', false)->count(),
                        'Planillas esperando aprobación',
                        'warning',
                    ),
                    $this->stat(
                        'approved_unpaid',
                        'Aprobadas sin pagar',
                        $ownTrackers()->where('approved', true)->where('paid', false)->count(),
                        'Planillas aprobadas pendientes de pago',
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
