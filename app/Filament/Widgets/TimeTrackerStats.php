<?php

namespace App\Filament\Widgets;

use App\Enums\UserRoleEnum;
use App\Models\TimeTracker;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TimeTrackerStats extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && ! $user->is_admin
            && in_array($user->user_role_id, [UserRoleEnum::Manager->value, UserRoleEnum::Employee->value]);
    }

    protected function getStats(): array
    {
        $ownTrackers = fn () => TimeTracker::query()->where('user_id', Filament::auth()->id());

        return [
            Stat::make(__('dashboard.pending_approval'), $ownTrackers()->where('approved', false)->count())
                ->description(__('dashboard.awaiting_approval'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            Stat::make(__('dashboard.approved_unpaid'), $ownTrackers()->where('approved', true)->where('paid', false)->count())
                ->description(__('dashboard.approved_pending_payment'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info'),
        ];
    }

    protected function getHeading(): ?string
    {
        return __('dashboard.my_time_trackers');
    }
}
