<?php

namespace App\Filament\Resources\TimeTrackers\Tables;

use App\Actions\TimeTracker\AutoCompleteTimeTrackerAction;
use App\Enums\UserRoleEnum;
use App\Models\TimeTrackerItemType;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimeTrackersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                $user = Filament::auth()->user();
                if ($user->is_admin) {
                    return $query;
                }
                if ($user->user_role_id == UserRoleEnum::Employee->value) {
                    return $query->where('user_id', $user->id);
                }

                return $query->where('customer_id', $user->customer_id);
            })
            ->columns([
                TextColumn::make('customer.name')
                    ->label(__('common.customer'))
                    ->searchable()
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin),
                TextColumn::make('user.fullName')
                    ->label(__('common.user'))
                    ->searchable(),
                TextColumn::make('project.name')
                    ->label(__('common.project'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('common.name'))
                    ->searchable(),
                TextColumn::make('date_start')
                    ->label(__('common.start_date'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('date_end')
                    ->label(__('common.end_date'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('common.amount'))
                    ->numeric(2, ',', '.')
                    ->sortable(),
                IconColumn::make('approved')
                    ->label(__('common.approved'))
                    ->boolean(),
                IconColumn::make('billed')
                    ->label(__('common.billed'))
                    ->boolean(),
                IconColumn::make('paid')
                    ->label(__('common.paid'))
                    ->boolean(),
                TextColumn::make('approved_at')
                    ->label(__('time_trackers.approved_at'))
                    ->date('d/m/Y'),
                TextColumn::make('approvedBy.fullName')
                    ->label(__('time_trackers.approved_by')),
                TextColumn::make('invoice.name')
                    ->label(__('common.invoice'))
                    ->searchable(),
                TextColumn::make('payment.id')
                    ->label(__('common.payment'))
                    ->searchable(),
                TextColumn::make('expected_payment_date')
                    ->label(__('time_trackers.expected_payment_date'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('paid_date')
                    ->label(__('time_trackers.paid_date'))
                    ->date('d/m/Y'),
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('common.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label(__('common.customer'))
                    ->relationship('customer', 'name')
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin),
                SelectFilter::make('user_id')
                    ->label(__('common.user'))
                    ->relationship('user', 'lastname'),
                SelectFilter::make('project_id')
                    ->label(__('common.project'))
                    ->relationship('project', 'name'),
                SelectFilter::make('approved')
                    ->label(__('common.approved'))
                    ->options([
                        '1' => __('common.approved'),
                        '0' => __('common.not_approved'),
                    ]),
                SelectFilter::make('billed')
                    ->label(__('common.billed'))
                    ->options([
                        '1' => __('common.billed'),
                        '0' => __('common.not_billed'),
                    ]),
                SelectFilter::make('paid')
                    ->label(__('common.paid'))
                    ->options([
                        '1' => __('common.paid'),
                        '0' => __('common.not_paid'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
                Action::make('auto_complete')
                    ->label(__('time_trackers.auto_complete'))
                    ->icon('heroicon-o-document-text')
                    ->action(function (): void {
                        $result = AutoCompleteTimeTrackerAction::execute();

                        if ($result['trackers'] === 0) {
                            Notification::make()
                                ->title(__('time_trackers.nothing_to_calculate'))
                                ->info()
                                ->send();

                            return;
                        }

                        if ($result['missing_rates'] !== []) {
                            Notification::make()
                                ->title(__('time_trackers.calculated_missing_rates'))
                                ->body(__('rates.missing_body', ['types' => TimeTrackerItemType::translateMany($result['missing_rates'])]))
                                ->warning()
                                ->persistent()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('time_trackers.calculated'))
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
