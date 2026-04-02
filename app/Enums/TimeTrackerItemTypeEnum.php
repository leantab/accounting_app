<?php

namespace App\Enums;

enum TimeTrackerItemTypeEnum: int
{
    case GENERAL = 1;
    case HOURS = 2;
    case DAY = 3;
    case ASSEMBLY = 4;
    case DISASSEMBLY = 5;
    case GUARD = 6;
    case TRAVEL = 7;
    case EXPENSES = 8;

    public function label(): string
    {
        return match ($this) {
            self::GENERAL => 'General',
            self::HOURS => 'Horas',
            self::DAY => 'Jornada',
            self::ASSEMBLY => 'Montaje',
            self::DISASSEMBLY => 'Desarme',
            self::GUARD => 'Guardia',
            self::TRAVEL => 'Viáticos',
            self::EXPENSES => 'Gastos',
        };
    }
}
