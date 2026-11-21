<?php

namespace App\Enums;

enum CheckInTypeEnum: int
{
    case In = 1;
    case Out = 2;

    public function label(): string
    {
        return match ($this) {
            self::In => __('check_ins.type_in'),
            self::Out => __('check_ins.type_out'),
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
