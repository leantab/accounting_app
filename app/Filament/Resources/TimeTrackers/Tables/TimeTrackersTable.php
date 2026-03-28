<?php

namespace App\Filament\Resources\TimeTrackers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TimeTrackersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin),
                TextColumn::make('user.full_name')
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
                IconColumn::make('billed')
                    ->label('Facturado')
                    ->boolean(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->sortable(),
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
                TextColumn::make('approvedBy.full_name')
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
                    ->relationship('user', 'full_name'),
                SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->relationship('project', 'name'),
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
                SelectFilter::make('approved')
                    ->label('Aprobado')
                    ->options([
                        '1' => 'Aprobado',
                        '0' => 'No aprobado',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
