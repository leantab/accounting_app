<?php

namespace App\Filament\Resources\TimeTrackers\Pages;

use App\Filament\Resources\TimeTrackers\TimeTrackerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTimeTrackers extends ListRecords
{
    protected static string $resource = TimeTrackerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
