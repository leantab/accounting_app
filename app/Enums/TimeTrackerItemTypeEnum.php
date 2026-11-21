<?php

namespace App\Enums;

enum TimeTrackerItemTypeEnum: int
{
    case EXTRA_HOUR = 1;
    case EXTRA_HOUR_MAIN = 2;
    case EXTRA_HOUR_RIGGING = 3;
    case EXTRA_HOUR_RIGGING_MAIN = 4;
    case DAY = 5;
    case DAY_MAIN = 6;
    case DAY_RIGGING = 7;
    case DAY_RIGGING_MAIN = 8;
    case DAY_SETUP_TEARDOWN = 9;
    case DOUBLE_DAY = 10;
    case DOUBLE_DAY_MAIN = 11;
    case LOAD_UNLOAD = 12;
    case LOAD_UNLOAD_MAIN = 13;
    case WAREHOUSE = 14;
    case GUARD = 15;
    case CONSOLE = 16;
    case TRAVEL = 17;
    case EXPENSES = 18;

    public function label(): string
    {
        return match ($this) {
            self::EXTRA_HOUR => 'Hora extra',
            self::EXTRA_HOUR_MAIN => 'Hora extra MAIN',
            self::EXTRA_HOUR_RIGGING => 'Hora extra Rigging',
            self::EXTRA_HOUR_RIGGING_MAIN => 'Hora extra Rigging MAIN',
            self::DAY => 'Jornal',
            self::DAY_MAIN => 'Jornal MAIN',
            self::DAY_RIGGING => 'Jornal + Rigging',
            self::DAY_RIGGING_MAIN => 'Jornal + Rigging MAIN',
            self::DAY_SETUP_TEARDOWN => 'Jornal Armado/Desarme',
            self::DOUBLE_DAY => 'Doble Jornal',
            self::DOUBLE_DAY_MAIN => 'Doble Jornal MAIN',
            self::LOAD_UNLOAD => 'Carga/Descarga',
            self::LOAD_UNLOAD_MAIN => 'Carga/Descarga MAIN',
            self::WAREHOUSE => 'Depósito',
            self::GUARD => 'Guardia',
            self::CONSOLE => 'Consola',
            self::TRAVEL => 'Viáticos',
            self::EXPENSES => 'Gastos',
        };
    }

    /**
     * The name shown to the user in the current language. label() stays the canonical name stored in the database.
     */
    public function translatedLabel(): string
    {
        return __('item_types.'.strtolower($this->name));
    }

    /**
     * Expenses and travel allowances are reimbursed as entered, so they have no individual rate.
     */
    public function hasRate(): bool
    {
        return ! in_array($this, [self::TRAVEL, self::EXPENSES], true);
    }

    /**
     * Types that are charged per worked hour.
     */
    public function isHourly(): bool
    {
        return in_array($this, [
            self::EXTRA_HOUR,
            self::EXTRA_HOUR_MAIN,
            self::EXTRA_HOUR_RIGGING,
            self::EXTRA_HOUR_RIGGING_MAIN,
        ], true);
    }

    /**
     * @return list<int>
     */
    public static function hourlyValues(): array
    {
        return array_values(array_map(
            fn (self $type): int => $type->value,
            array_filter(self::cases(), fn (self $type): bool => $type->isHourly()),
        ));
    }

    /**
     * @return list<string>
     */
    public static function hourlyLabels(): array
    {
        return array_values(array_map(
            fn (self $type): string => $type->label(),
            array_filter(self::cases(), fn (self $type): bool => $type->isHourly()),
        ));
    }
}
