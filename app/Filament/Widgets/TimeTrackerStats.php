<?php

namespace App\Filament\Widgets;

use App\Enums\UserRoleEnum;
use App\Models\TimeTracker;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TimeTrackerStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Mis planillas de horas';

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
            Stat::make('Pendientes de aprobación', $ownTrackers()->where('approved', false)->count())
                ->description('Planillas esperando aprobación')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Aprobadas sin pagar', $ownTrackers()->where('approved', true)->where('paid', false)->count())
                ->description('Planillas aprobadas pendientes de pago')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info'),
        ];
    }
}
