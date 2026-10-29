<?php

namespace App\Filament;

use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Support\RawJs;

/**
 * Argentine money formatting: thousands separated by dots, cents by a comma (1.234,56).
 */
class Money
{
    public const DECIMAL_SEPARATOR = ',';

    public const THOUSANDS_SEPARATOR = '.';

    public static function input(string $name): TextInput
    {
        return TextInput::make($name)
            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
            ->inputMode('decimal')
            ->formatStateUsing(fn (mixed $state): ?string => self::format($state))
            ->dehydrateStateUsing(fn (mixed $state): ?float => self::parse($state))
            ->rules([
                fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && self::parse($value) === null) {
                        $fail('El monto debe ser un número válido mayor o igual a 0, por ejemplo 1.234,56.');
                    }
                },
            ]);
    }

    /**
     * Formats a stored number (e.g. 1234.5) for display and inputs (1.234,50).
     */
    public static function format(mixed $value): ?string
    {
        if (is_string($value) && preg_match('/^\d{1,3}(\.\d{3})*,\d{2}$/', $value)) {
            return $value;
        }

        if (blank($value) || ! is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 2, self::DECIMAL_SEPARATOR, self::THOUSANDS_SEPARATOR);
    }

    /**
     * Reads a typed amount. Numbers are taken as they are; strings use the Argentine format (1.234,56).
     */
    public static function parse(mixed $value): ?float
    {
        if (blank($value)) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return $value >= 0 ? (float) $value : null;
        }

        $normalized = str_replace([self::THOUSANDS_SEPARATOR, self::DECIMAL_SEPARATOR], ['', '.'], trim((string) $value));

        if (! preg_match('/^\d+(\.\d+)?$/', $normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}
