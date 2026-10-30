<?php

namespace App\Filament\Resources\CheckIns\Tables;

use App\Enums\CheckInTypeEnum;
use App\Models\CheckIn;
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
                    ->label('Cliente')
                    ->hidden(fn (): bool => ! Filament::auth()->user()->is_admin),
                TextColumn::make('user.fullName')
                    ->label('Usuario')
                    ->searchable(['name', 'lastname']),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (CheckInTypeEnum $state): string => $state->label())
                    ->color(fn (CheckInTypeEnum $state): string => $state === CheckInTypeEnum::In ? 'success' : 'warning'),
                TextColumn::make('recorded_at')
                    ->label('Fecha y hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->placeholder('-'),
                TextColumn::make('accuracy_m')
                    ->label('Precisión')
                    ->suffix(' m')
                    ->placeholder('-'),
                TextColumn::make('location')
                    ->label('Ubicación')
                    ->state('Ver en mapa')
                    ->url(fn (CheckIn $record): string => $record->mapsUrl(), shouldOpenInNewTab: true)
                    ->color('primary'),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->placeholder('-')
                    ->limit(40),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'lastname'),
                SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->relationship('project', 'name'),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        CheckInTypeEnum::In->value => CheckInTypeEnum::In->label(),
                        CheckInTypeEnum::Out->value => CheckInTypeEnum::Out->label(),
                    ]),
            ]);
    }
}
