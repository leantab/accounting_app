<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;

class UserRatesRelationManager extends RelationManager
{
    protected static string $relationship = 'userRates';

    protected static ?string $relatedResource = UserResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción'),
                Tables\Columns\TextColumn::make('rate')
                    ->label('Tarifa'),
                Tables\Columns\TextColumn::make('timeTrackerItemType.name')
                    ->label('Tipo de item'),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->required(),
                Forms\Components\TextInput::make('rate')
                    ->label('Tarifa')
                    ->required(),
                Forms\Components\Select::make('time_tracker_item_type_id')
                    ->label('Tipo de item')
                    ->relationship('timeTrackerItemType', 'name')
                    ->required(),
            ]);
    }
}
