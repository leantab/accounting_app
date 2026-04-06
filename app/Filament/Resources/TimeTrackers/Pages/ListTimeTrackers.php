<?php

namespace App\Filament\Resources\TimeTrackers\Pages;

use App\Filament\Resources\TimeTrackers\TimeTrackerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTimeTrackers extends ListRecords
{
    protected static string $resource = TimeTrackerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Todas' => Tab::make(),
            'Pendiente Aprobación' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('approved', false)),
            'Aprobadas no facturadas' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('approved', true)->where('billed', false)),
            'Pendientes de pago' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('approved', true)->where('billed', true)->where('paid', false)),
            'Pagadas' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('paid', true)),
        ];
    }
}
