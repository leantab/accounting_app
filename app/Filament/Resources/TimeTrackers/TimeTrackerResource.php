<?php

namespace App\Filament\Resources\TimeTrackers;

use App\Filament\Resources\TimeTrackers\Pages\CreateTimeTracker;
use App\Filament\Resources\TimeTrackers\Pages\EditTimeTracker;
use App\Filament\Resources\TimeTrackers\Pages\ListTimeTrackers;
use App\Filament\Resources\TimeTrackers\Pages\ViewTimeTracker;
use App\Filament\Resources\TimeTrackers\Schemas\TimeTrackerForm;
use App\Filament\Resources\TimeTrackers\Schemas\TimeTrackerInfolist;
use App\Filament\Resources\TimeTrackers\Tables\TimeTrackersTable;
use App\Models\TimeTracker;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TimeTrackerResource extends Resource
{
    protected static ?string $model = TimeTracker::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Planillas de horas';

    protected static ?string $recordTitleAttribute = 'Planilla de horas';

    public static function form(Schema $schema): Schema
    {
        return TimeTrackerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TimeTrackerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimeTrackersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTimeTrackers::route('/'),
            'create' => CreateTimeTracker::route('/create'),
            'view' => ViewTimeTracker::route('/{record}'),
            'edit' => EditTimeTracker::route('/{record}/edit'),
        ];
    }
}
