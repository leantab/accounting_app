<?php

namespace App\Filament\Resources\TimeTrackers\Pages;

use App\Enums\UserRoleEnum;
use App\Filament\Resources\TimeTrackers\TimeTrackerResource;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTimeTracker extends ViewRecord
{
    protected static string $resource = TimeTrackerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn($record) => (Filament::auth()->user()->is_admin
                    || in_array(Filament::auth()->user()->role, [UserRoleEnum::Admin->value, UserRoleEnum::Owner->value]))
                    && ! $record->approved)
                ->action(function ($record) {
                    $record->update([
                        'approved' => true,
                        'approved_at' => now(),
                        'approved_by' => Filament::auth()->user()->id,
                    ]);

                    Notification::make()
                        ->title('Planilla aprobada')
                        ->success()
                        ->toDatabase();
                }),
        ];
    }
}
