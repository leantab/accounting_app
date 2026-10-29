<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Money;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer.name')
                    ->label('Cliente')
                    ->visible(fn () => Filament::auth()->user()->is_admin),
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('lastname')
                    ->label('Apellido'),
                TextEntry::make('role.name')
                    ->label('Rol'),
                TextEntry::make('email')
                    ->label('Email'),
                TextEntry::make('phone')
                    ->label('Teléfono')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->label('Dirección')
                    ->placeholder('-'),
                TextEntry::make('tax_id')
                    ->label('CUIL/CUIT')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                IconEntry::make('is_admin')
                    ->label('Admin')
                    ->visible(fn () => Filament::auth()->user()->is_admin)
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('fixed_daily_rate')
                    ->label('Tarifa fija por jornada')
                    ->numeric(2, ',', '.')
                    ->placeholder('-'),
                TextEntry::make('fixed_monthly_rate')
                    ->label('Tarifa fija mensual')
                    ->numeric(2, ',', '.')
                    ->placeholder('-'),
                Section::make('Tarifas individuales')
                    ->description('Si no hay una tarifa individual, se usa la tarifa por defecto.')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema(self::individualRateEntries()),
            ]);
    }

    /**
     * @return array<int, TextEntry>
     */
    private static function individualRateEntries(): array
    {
        return TimeTrackerItemType::withRates()
            ->map(fn (TimeTrackerItemType $type): TextEntry => TextEntry::make('individual_rate_'.$type->id)
                ->label($type->name)
                ->state(fn (User $record): string => self::formatIndividualRate($record, $type->id)))
            ->all();
    }

    private static function formatIndividualRate(User $record, int $typeId): string
    {
        $custom = $record->userRates->firstWhere('time_tracker_item_type_id', $typeId);

        if ($custom !== null) {
            return Money::format($custom->rate);
        }

        $default = $record->effectiveRateFor($typeId);

        return $default === null
            ? 'Por defecto'
            : 'Por defecto ('.Money::format($default).')';
    }
}
