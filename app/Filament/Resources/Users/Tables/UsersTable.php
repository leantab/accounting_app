<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => Filament::auth()->user()->is_admin ? $query : $query->where('customer_id', Filament::auth()->user()->customer_id))
            ->columns([
                TextColumn::make('customer.name')
                    ->label(__('common.customer'))
                    ->visible(fn () => Filament::auth()->user()->is_admin)
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('common.name'))
                    ->searchable(),
                TextColumn::make('lastname')
                    ->label(__('common.last_name'))
                    ->searchable(),
                TextColumn::make('role.name')
                    ->label(__('common.role'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('common.email'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('common.phone'))
                    ->searchable(),
                TextColumn::make('address')
                    ->label(__('common.address'))
                    ->searchable(),
                TextColumn::make('tax_id')
                    ->label(__('common.cuil_cuit'))
                    ->searchable(),
                TextColumn::make('fixed_daily_rate')
                    ->label(__('users.fixed_daily'))
                    ->numeric(2, ',', '.')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('fixed_monthly_rate')
                    ->label(__('users.fixed_monthly'))
                    ->numeric(2, ',', '.')
                    ->placeholder('-')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('common.active'))
                    ->boolean(),
                IconColumn::make('is_admin')
                    ->label(__('common.admin'))
                    ->boolean()
                    ->visible(fn () => Filament::auth()->user()->is_admin),
                TextColumn::make('created_at')
                    ->label(__('common.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('common.updated'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
