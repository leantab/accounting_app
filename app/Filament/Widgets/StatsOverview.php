<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Resumen';

    // protected ?string $description = 'An overview of some analytics.';

    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        $isAdmin = $user->is_admin;
        $customer = $user->customer_id;
        return [
            Stat::make('Usuarios', $isAdmin ? User::count() : User::where('customer_id', $customer)->count())
                ->description('Usuarios registrados')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Empresas', $isAdmin ? Company::count() : Company::where('customer_id', $customer)->count())
                ->description('Empresas registradas')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('gray'),
            Stat::make('Proyectos', $isAdmin ? Project::count() : Project::where('customer_id', $customer)->count())
                ->description('Proyectos registrados')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color('info'),
        ];
    }
}
