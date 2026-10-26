<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Models\UserRate;
use Filament\Facades\Filament;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
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
                TextEntry::make('effective_hourly_rate')
                    ->label('Tarifa por hora extra')
                    ->state(fn (User $record): string => self::formatEffectiveRate($record, TimeTrackerItemTypeEnum::EXTRA_HOUR)),
                TextEntry::make('effective_daily_rate')
                    ->label('Tarifa por jornal')
                    ->state(fn (User $record): string => self::formatEffectiveRate($record, TimeTrackerItemTypeEnum::DAY)),
                TextEntry::make('fixed_daily_rate')
                    ->label('Tarifa fija por jornada')
                    ->placeholder('-'),
                TextEntry::make('fixed_monthly_rate')
                    ->label('Tarifa fija mensual')
                    ->placeholder('-'),
                RepeatableEntry::make('userRates')
                    ->label('Tarifas de Usuario')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('description')
                            ->label('Descripción'),
                        TextEntry::make('rate')
                            ->label('Tarifa'),
                        TextEntry::make('timeTrackerItemType.name')
                            ->label('Tipo de item'),
                    ]),
            ]);
    }

    private static function formatEffectiveRate(User $record, TimeTrackerItemTypeEnum $type): string
    {
        $typeId = TimeTrackerItemType::findIdFor($type);

        if ($typeId === null) {
            return 'Sin definir';
        }

        $rate = $record->effectiveRateFor($typeId);

        if ($rate === null) {
            return 'Sin definir';
        }

        $formatted = number_format($rate, 2, '.', '');

        $isCustom = $record->userRates->contains(
            fn (UserRate $userRate): bool => (int) $userRate->time_tracker_item_type_id === $typeId,
        );

        return $isCustom ? $formatted : "{$formatted} (por defecto)";
    }
}
