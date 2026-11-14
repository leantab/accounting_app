<?php

namespace App\Filament\Resources\TimeTrackers\Pages;

use App\Actions\TimeTracker\AutoCompleteTimeTrackerAction;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\TimeTrackers\TimeTrackerResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTimeTracker extends ViewRecord
{
    protected static string $resource = TimeTrackerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('recalculate')
                ->label('Recalcular monto')
                ->icon('heroicon-o-calculator')
                ->color('gray')
                ->visible(fn (): bool => self::canManageAmounts())
                ->requiresConfirmation()
                ->modalHeading('Recalcular monto')
                ->modalDescription('Se volverán a calcular los montos de cada ítem y el monto final con las tarifas actuales. Los montos actuales se reemplazarán.')
                ->modalSubmitActionLabel('Recalcular')
                ->action(function ($record): void {
                    abort_unless(self::canManageAmounts(), 403);

                    $result = AutoCompleteTimeTrackerAction::recalculate($record);

                    $this->record->refresh()->load('items');
                    $this->refreshFormData(['amount']);

                    if ($result['missing_rates'] !== []) {
                        Notification::make()
                            ->title('Monto recalculado, pero faltan tarifas')
                            ->body('Cargue una tarifa por defecto o individual para: '.implode(', ', $result['missing_rates']).'.')
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Monto recalculado')
                        ->success()
                        ->send();
                }),
            Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record): bool => ! $record->approved
                    && (Filament::auth()->user()->is_admin
                        || in_array(Filament::auth()->user()->user_role_id, [UserRoleEnum::Admin->value, UserRoleEnum::Owner->value])))
                ->action(function ($record) {
                    $record->update([
                        'approved' => true,
                        'approved_at' => now(),
                        'approved_by' => Filament::auth()->user()->id,
                    ]);

                    /** @var User $user */
                    $user = User::find($record->user_id);
                    Notification::make()
                        ->title('Planilla aprobada')
                        ->success()
                        ->sendToDatabase($user);
                }),
        ];
    }

    private static function canManageAmounts(): bool
    {
        $user = Filament::auth()->user();

        return (bool) $user->is_admin
            || in_array($user->user_role_id, [UserRoleEnum::Admin->value, UserRoleEnum::Owner->value], true);
    }
}
