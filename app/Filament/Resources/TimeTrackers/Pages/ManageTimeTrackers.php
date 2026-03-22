<?php

namespace App\Filament\Resources\TimeTrackers\Pages;

use App\Filament\Resources\TimeTrackers\TimeTrackerResource;
use App\Models\TimeTracker;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
// use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

class ManageTimeTrackers extends ManageRecords
{
    protected static string $resource = TimeTrackerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Crear'),
            // DeleteAction::make()
            //     ->label('Eliminar'),
            // ->requiresConfirmation(),
        ];
    }

    protected function getInfoListActions(): array
    {
        return [
            Action::make('Approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->action(function (TimeTracker $record): void {
                    $user = Filament::auth()->user();
                    $record->update([
                        'is_approved' => true,
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                    ]);
                })
                ->requiresConfirmation()
                ->color('warning')
                ->modalIcon('heroicon-o-check')
                ->modalIconColor('warning'),
        ];
    }
}
