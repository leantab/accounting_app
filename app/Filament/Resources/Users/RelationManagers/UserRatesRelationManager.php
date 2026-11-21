<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Users\UserResource;
use App\Models\TimeTrackerItemType;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                TextColumn::make('timeTrackerItemType.name')
                    ->label(__('rates.item_type_tracker'))
                    ->formatStateUsing(fn (string $state): string => TimeTrackerItemType::translate($state)),
                TextColumn::make('description')
                    ->label(__('common.description')),
                TextColumn::make('rate')
                    ->label(__('rates.rate')),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('time_tracker_item_type_id')
                    ->label(__('rates.item_type'))
                    ->relationship('timeTrackerItemType', 'name')
                    ->getOptionLabelFromRecordUsing(fn (TimeTrackerItemType $record): string => $record->displayName())
                    ->required(),
                TextInput::make('description')
                    ->label(__('common.description'))
                    ->required(),
                TextInput::make('rate')
                    ->label(__('rates.rate'))
                    ->required(),
            ]);
    }
}
