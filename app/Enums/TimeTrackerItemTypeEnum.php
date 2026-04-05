<?php

namespace App\Enums;

enum TimeTrackerItemTypeEnum: int
{
    case HOURS = 1;
    case DAY = 2;
    case HALF_DAY = 3;
    case RIGGING = 4;
    case GUARD = 5;
    case TRAVEL = 6;
    case EXPENSES = 7;

    public function label(): string
    {
        return match ($this) {
            self::HOURS => 'Horas',
            self::DAY => 'Jornada',
            self::HALF_DAY => 'Media Jornada',
            self::RIGGING => 'Montaje',
            self::GUARD => 'Guardia',
            self::TRAVEL => 'Viáticos',
            self::EXPENSES => 'Gastos',
        };
    }
}
