<?php

namespace App\Filament\Resources\CheckIns;

use App\Filament\Resources\CheckIns\Pages\ListCheckIns;
use App\Filament\Resources\CheckIns\Tables\CheckInsTable;
use App\Models\CheckIn;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CheckInResource extends Resource
{
    protected static ?string $model = CheckIn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Asistencia';

    protected static ?string $modelLabel = 'Registro de asistencia';

    protected static ?string $pluralModelLabel = 'Registros de asistencia';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && CheckIn::canBeReviewedBy($user);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return CheckInsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCheckIns::route('/'),
        ];
    }
}
