<?php

namespace App\Filament\Widgets;

use App\Enums\UserRoleEnum;
use App\Models\TimeTracker;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class TimeTrackerStatusStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->is_admin
            || ($user->customer_id !== null && in_array($user->user_role_id, [
                UserRoleEnum::Owner->value,
                UserRoleEnum::Admin->value,
                UserRoleEnum::Manager->value,
            ], true));
    }

    protected function getStats(): array
    {
        $pendingPayment = $this->pendingPayment()->count();
        $overdue = $this->pendingPayment()->whereDate('expected_payment_date', '<', today())->count();

        return [
            Stat::make(__('dashboard.pending_approval'), TimeTracker::query()->where('approved', false)->count())
                ->description(__('dashboard.awaiting_approval'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            Stat::make(__('dashboard.pending_invoice'), TimeTracker::query()->where('approved', true)->where('billed', false)->count())
                ->description(__('dashboard.approved_not_billed'))
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info'),
            Stat::make(__('dashboard.pending_payment'), $pendingPayment)
                ->description($overdue > 0
                    ? __('dashboard.billed_not_paid_overdue', ['count' => $overdue])
                    : __('dashboard.billed_not_paid'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color($overdue > 0 ? 'danger' : 'gray'),
        ];
    }

    /**
     * @return Builder<TimeTracker>
     */
    private function pendingPayment(): Builder
    {
        return TimeTracker::query()->where('approved', true)->where('billed', true)->where('paid', false);
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.time_tracker_status');
    }
}
