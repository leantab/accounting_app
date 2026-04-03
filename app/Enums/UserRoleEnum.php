<?php

namespace App\Enums;

enum UserRoleEnum: int
{
    case Owner = 1;
    case Admin = 2;
    case Manager = 3;
    case Employee = 4;

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Employee => 'Employee',
        };
    }

    public function getLabel($role)
    {
        return match ($role) {
            self::Owner->value => 'Owner',
            self::Admin->value => 'Admin',
            self::Manager->value => 'Manager',
            self::Employee->value => 'Employee',
        };
    }
}
