<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

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
                TextColumn::make('description')
                    ->label('Descripción'),
                TextColumn::make('rate')
                    ->label('Tarifa'),
                TextColumn::make('timeTrackerItemType.name')
                    ->label('Tipo de item Planilla'),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('description')
                    ->label('Descripción')
                    ->required(),
                TextInput::make('rate')
                    ->label('Tarifa')
                    ->required(),
                Select::make('time_tracker_item_type_id')
                    ->label('Tipo de item')
                    ->relationship('timeTrackerItemType', 'name')
                    ->required(),
            ]);
    }
}
