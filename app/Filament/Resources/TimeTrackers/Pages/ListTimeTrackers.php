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
            __('time_trackers.tab_all') => Tab::make(),
            __('time_trackers.tab_pending_approval') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approved', false)),
            __('time_trackers.tab_approved_not_billed') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approved', true)->where('billed', false)),
            __('time_trackers.tab_pending_payment') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('approved', true)->where('billed', true)->where('paid', false)),
            __('time_trackers.tab_paid') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('paid', true)),
        ];
    }
}
