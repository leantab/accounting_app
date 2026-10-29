<?php

use App\Filament\Money;

it('formats amounts with dots for thousands and a comma for cents', function (mixed $value, ?string $expected): void {
    expect(Money::format($value))->toBe($expected);
})->with([
    'integer' => [800, '800,00'],
    'decimal string from the database' => ['1234.5', '1.234,50'],
    'float' => [1234567.891, '1.234.567,89'],
    'zero' => [0, '0,00'],
    'null' => [null, null],
    'empty string' => ['', null],
]);

it('parses typed amounts', function (mixed $value, ?float $expected): void {
    expect(Money::parse($value))->toBe($expected);
})->with([
    'thousands and cents' => ['1.234,56', 1234.56],
    'millions' => ['1.234.567,89', 1234567.89],
    'cents only' => ['0,5', 0.5],
    'whole number' => ['800', 800.0],
    'integer' => [900, 900.0],
    'float' => [12.5, 12.5],
    'empty' => ['', null],
    'null' => [null, null],
    'letters' => ['abc', null],
    'negative number' => [-5, null],
    'negative string' => ['-5,00', null],
]);
