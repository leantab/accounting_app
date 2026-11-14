<?php

use App\Enums\TimeTrackerItemTypeEnum;

it('matches the item type ids and names created by the seeder', function (): void {
    $seeded = [
        'Hora extra', 'Hora extra MAIN', 'Hora extra Rigging', 'Hora extra Rigging MAIN',
        'Jornal', 'Jornal MAIN', 'Jornal + Rigging', 'Jornal + Rigging MAIN', 'Jornal Armado/Desarme',
        'Doble Jornal', 'Doble Jornal MAIN', 'Carga/Descarga', 'Carga/Descarga MAIN',
        'Depósito', 'Guardia', 'Consola', 'Viáticos', 'Gastos',
    ];

    foreach ($seeded as $index => $name) {
        expect(TimeTrackerItemTypeEnum::from($index + 1)->label())->toBe($name);
    }

    expect(TimeTrackerItemTypeEnum::cases())->toHaveCount(count($seeded));
});

it('treats only extra hour types as hourly', function (): void {
    expect(TimeTrackerItemTypeEnum::hourlyValues())->toBe([1, 2, 3, 4])
        ->and(TimeTrackerItemTypeEnum::hourlyLabels())->toBe(['Hora extra', 'Hora extra MAIN', 'Hora extra Rigging', 'Hora extra Rigging MAIN'])
        ->and(TimeTrackerItemTypeEnum::DAY->isHourly())->toBeFalse();
});

it('matches the names inserted by the database seeder in the same order', function (): void {
    $seeder = file_get_contents(dirname(__DIR__, 2).'/database/seeders/DatabaseSeeder.php');

    preg_match('/time_tracker_item_types.*?\]\);/s', $seeder, $block);
    preg_match_all("/\['name' => '([^']+)'\]/", $block[0], $names);

    expect(array_map(fn (TimeTrackerItemTypeEnum $type): string => $type->label(), TimeTrackerItemTypeEnum::cases()))
        ->toBe($names[1]);
});

it('gives a rate to every type except travel allowances and expenses', function (): void {
    $withoutRate = array_filter(TimeTrackerItemTypeEnum::cases(), fn (TimeTrackerItemTypeEnum $type): bool => ! $type->hasRate());

    expect(array_values(array_map(fn (TimeTrackerItemTypeEnum $type): string => $type->label(), $withoutRate)))
        ->toBe(['Viáticos', 'Gastos']);
});
