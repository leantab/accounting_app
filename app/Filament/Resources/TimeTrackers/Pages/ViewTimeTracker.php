<?php

namespace App\Filament\Resources\TimeTrackers\Pages;

use App\Actions\TimeTracker\ApproveTimeTrackerAction;
use App\Actions\TimeTracker\AutoCompleteTimeTrackerAction;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\TimeTrackers\TimeTrackerResource;
use App\Models\TimeTrackerItemType;
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
                ->label(__('time_trackers.recalculate'))
                ->icon('heroicon-o-calculator')
                ->color('gray')
                ->visible(fn (): bool => self::canManageAmounts())
                ->requiresConfirmation()
                ->modalHeading(__('time_trackers.recalculate'))
                ->modalDescription(__('time_trackers.recalculate_description'))
                ->modalSubmitActionLabel(__('time_trackers.recalculate_submit'))
                ->action(function ($record): void {
                    abort_unless(self::canManageAmounts(), 403);

                    $result = AutoCompleteTimeTrackerAction::recalculate($record);

                    $this->record->refresh()->load('items');
                    $this->refreshFormData(['amount']);

                    if ($result['missing_rates'] !== []) {
                        Notification::make()
                            ->title(__('time_trackers.recalculated_missing_rates'))
                            ->body(__('rates.missing_body', ['types' => TimeTrackerItemType::translateMany($result['missing_rates'])]))
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('time_trackers.recalculated'))
                        ->success()
                        ->send();
                }),
            Action::make('approve')
                ->label(__('time_trackers.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record): bool => ! $record->approved
                    && (Filament::auth()->user()->is_admin
                        || in_array(Filament::auth()->user()->user_role_id, [UserRoleEnum::Admin->value, UserRoleEnum::Owner->value])))
                ->action(function ($record) {
                    ApproveTimeTrackerAction::execute($record, Filament::auth()->user());
                }),
        ];
    }

    private static function canManageAmounts(): bool
    {
        $user = Filament::auth()->user();

        return (bool) $user->is_admin
            || in_array($user->user_role_id, [UserRoleEnum::Admin->value, UserRoleEnum::Owner->value, UserRoleEnum::Manager->value], true);
    }
}
