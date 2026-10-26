<?php

use App\Enums\TimeTrackerItemTypeEnum;

it('matches the item type ids and names created by the seeder', function (): void {
    $seeded = [
        'Hora extra', 'Hora extra Rigging', 'Jornal', 'Jornal + Rigging', 'Jornal Armado/Desarme',
        'Doble Jornal', 'Carga/Descarga', 'Depósito', 'Guardia', 'Viáticos', 'Gastos',
    ];

    foreach ($seeded as $index => $name) {
        expect(TimeTrackerItemTypeEnum::from($index + 1)->label())->toBe($name);
    }

    expect(TimeTrackerItemTypeEnum::cases())->toHaveCount(count($seeded));
});

it('treats only extra hour types as hourly', function (): void {
    expect(TimeTrackerItemTypeEnum::hourlyValues())->toBe([1, 2])
        ->and(TimeTrackerItemTypeEnum::hourlyLabels())->toBe(['Hora extra', 'Hora extra Rigging'])
        ->and(TimeTrackerItemTypeEnum::DAY->isHourly())->toBeFalse();
});
