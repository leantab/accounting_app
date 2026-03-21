<?php

namespace App\Filament\Resources\TimeTrackers;

use App\Filament\Resources\TimeTrackers\Pages\ManageTimeTrackers;
use App\Models\TimeTracker;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimeTrackerResource extends Resource
{
    protected static ?string $model = TimeTracker::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->default(auth()->user()->customer_id)
                    ->hidden()
                    ->required(),
                Select::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->default(auth()->user()->id)
                    ->hidden()
                    ->required(),
                Select::make('project_id')
                    ->label('Proyecto')
                    ->relationship('project', 'name')
                    ->required(),
                TextInput::make('date_start')
                    ->label('Fecha de inicio')
                    ->required(),
                TextInput::make('date_end')
                    ->label('Fecha de fin')
                    ->required(),
                TextInput::make('hours')
                    ->label('Horas'),
                TextInput::make('description')
                    ->label('Descripción'),
                TextInput::make('amount')
                    ->label('Monto')
                    ->disabled(true),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('customer.name')
                    ->label('Cliente'),
                TextEntry::make('user.full_name')
                    ->label('Usuario'),
                TextEntry::make('project.name')
                    ->label('Proyecto'),
                TextEntry::make('date_start')
                    ->label('Fecha de inicio'),
                TextEntry::make('date_end')
                    ->label('Fecha de fin'),
                TextEntry::make('hours')
                    ->label('Horas'),
                TextEntry::make('description')
                    ->label('Descripción'),
                TextEntry::make('amount')
                    ->label('Monto'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTimeTrackers::route('/'),
        ];
    }
}
