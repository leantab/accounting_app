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
                    ->label(__('common.customer'))
                    ->visible(fn () => Filament::auth()->user()->is_admin),
                TextEntry::make('name')
                    ->label(__('common.name')),
                TextEntry::make('lastname')
                    ->label(__('common.last_name')),
                TextEntry::make('role.name')
                    ->label(__('common.role')),
                TextEntry::make('email')
                    ->label(__('common.email')),
                TextEntry::make('phone')
                    ->label(__('common.phone'))
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->label(__('common.address'))
                    ->placeholder('-'),
                TextEntry::make('tax_id')
                    ->label(__('common.cuil_cuit'))
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->label(__('common.active'))
                    ->boolean(),
                IconEntry::make('is_admin')
                    ->label(__('common.admin'))
                    ->visible(fn () => Filament::auth()->user()->is_admin)
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label(__('common.created'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('common.updated'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('fixed_daily_rate')
                    ->label(__('users.fixed_daily_rate'))
                    ->numeric(2, ',', '.')
                    ->placeholder('-'),
                TextEntry::make('fixed_monthly_rate')
                    ->label(__('users.fixed_monthly_rate'))
                    ->numeric(2, ',', '.')
                    ->placeholder('-'),
                Section::make(__('users.individual_rates'))
                    ->description(__('users.individual_rates_fallback'))
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
                ->label($type->displayName())
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
