<?php

namespace App\Enums;

enum CheckInTypeEnum: int
{
    case In = 1;
    case Out = 2;

    public function label(): string
    {
        return match ($this) {
            self::In => 'Entrada',
            self::Out => 'Salida',
        };
    }

    public function next(): self
    {
        return match ($this) {
            self::In => self::Out,
            self::Out => self::In,
        };
    }
}
