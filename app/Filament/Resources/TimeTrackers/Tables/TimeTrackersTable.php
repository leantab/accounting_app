<?php

namespace App\Filament\Resources\TimeTrackers\Tables;

use App\Actions\TimeTracker\AutoCompleteTimeTrackerAction;
use App\Enums\UserRoleEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
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
                    ->label('Customer')
                    ->searchable()
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin),
                TextColumn::make('user.fullName')
                    ->label('Usuario')
                    ->searchable(),
                TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('date_start')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('date_end')
                    ->label('Fecha de fin')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('hours')
                    ->label('Horas')
                    ->numeric(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('billed')
                    ->label('Facturado')
                    ->boolean(),
                TextColumn::make('invoice.name')
                    ->label('Factura')
                    ->searchable(),
                IconColumn::make('paid')
                    ->label('Pagado')
                    ->boolean(),
                TextColumn::make('payment.id')
                    ->label('Payment')
                    ->searchable(),
                TextColumn::make('paid_date')
                    ->label('Fecha de pago')
                    ->date('d/m/Y')
                    ->sortable(),
                IconColumn::make('approved')
                    ->label('Aprobado')
                    ->boolean(),
                TextColumn::make('approved_at')
                    ->label('Fecha de aprobación')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('approvedBy.fullName')
                    ->label('Aprobado por'),
                TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin),
                SelectFilter::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'lastname'),
                SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->relationship('project', 'name'),
                SelectFilter::make('approved')
                    ->label('Aprobado')
                    ->options([
                        '1' => 'Aprobado',
                        '0' => 'No aprobado',
                    ]),
                SelectFilter::make('billed')
                    ->label('Facturado')
                    ->options([
                        '1' => 'Facturado',
                        '0' => 'No facturado',
                    ]),
                SelectFilter::make('paid')
                    ->label('Pagado')
                    ->options([
                        '1' => 'Pagado',
                        '0' => 'No pagado',
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
                    ->label('Auto-Completar')
                    ->icon('heroicon-o-document-text')
                    ->action(function () {
                        // TODO: Add export action
                        AutoCompleteTimeTrackerAction::execute();
                    }),
            ]);
    }
}
