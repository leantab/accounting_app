<?php

namespace App\Filament\Resources\CheckIns\Tables;

use App\Enums\CheckInTypeEnum;
use App\Models\CheckIn;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CheckInsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('recorded_at', 'desc')
            ->columns([
                TextColumn::make('customer.name')
                    ->label(__('common.customer'))
                    ->hidden(fn (): bool => ! Filament::auth()->user()->is_admin),
                TextColumn::make('user.fullName')
                    ->label(__('common.user'))
                    ->searchable(['name', 'lastname']),
                TextColumn::make('type')
                    ->label(__('common.type'))
                    ->badge()
                    ->formatStateUsing(fn (CheckInTypeEnum $state): string => $state->label())
                    ->color(fn (CheckInTypeEnum $state): string => $state === CheckInTypeEnum::In ? 'success' : 'warning'),
                TextColumn::make('recorded_at')
                    ->label(__('check_ins.date_time'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('project.name')
                    ->label(__('common.project'))
                    ->placeholder('-'),
                TextColumn::make('accuracy_m')
                    ->label(__('check_ins.accuracy'))
                    ->suffix(' m')
                    ->placeholder('-'),
                TextColumn::make('location')
                    ->label(__('check_ins.location'))
                    ->state(__('check_ins.view_on_map'))
                    ->url(fn (CheckIn $record): string => $record->mapsUrl(), shouldOpenInNewTab: true)
                    ->color('primary'),
                TextColumn::make('notes')
                    ->label(__('common.notes'))
                    ->placeholder('-')
                    ->limit(40),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('common.user'))
                    ->relationship('user', 'lastname')
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->full_name),
                SelectFilter::make('project_id')
                    ->label(__('common.project'))
                    ->relationship('project', 'name'),
                SelectFilter::make('type')
                    ->label(__('common.type'))
                    ->options([
                        CheckInTypeEnum::In->value => CheckInTypeEnum::In->label(),
                        CheckInTypeEnum::Out->value => CheckInTypeEnum::Out->label(),
                    ]),
            ]);
    }
}
