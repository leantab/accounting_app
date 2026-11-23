<?php

namespace App\Filament\Resources\TimeTrackers\Pages;

use App\Filament\Resources\TimeTrackers\TimeTrackerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTimeTracker extends EditRecord
{
    protected static string $resource = TimeTrackerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
