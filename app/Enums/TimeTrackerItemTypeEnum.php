<?php

namespace App\Enums;

enum TimeTrackerItemTypeEnum: int
{
    case EXTRA_HOUR = 1;
    case EXTRA_HOUR_RIGGING = 2;
    case DAY = 3;
    case DAY_RIGGING = 4;
    case DAY_SETUP_TEARDOWN = 5;
    case DOUBLE_DAY = 6;
    case LOAD_UNLOAD = 7;
    case WAREHOUSE = 8;
    case GUARD = 9;
    case TRAVEL = 10;
    case EXPENSES = 11;

    public function label(): string
    {
        return match ($this) {
            self::EXTRA_HOUR => 'Hora extra',
            self::EXTRA_HOUR_RIGGING => 'Hora extra Rigging',
            self::DAY => 'Jornal',
            self::DAY_RIGGING => 'Jornal + Rigging',
            self::DAY_SETUP_TEARDOWN => 'Jornal Armado/Desarme',
            self::DOUBLE_DAY => 'Doble Jornal',
            self::LOAD_UNLOAD => 'Carga/Descarga',
            self::WAREHOUSE => 'Depósito',
            self::GUARD => 'Guardia',
            self::TRAVEL => 'Viáticos',
            self::EXPENSES => 'Gastos',
        };
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
        return in_array($this, [self::EXTRA_HOUR, self::EXTRA_HOUR_RIGGING], true);
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
