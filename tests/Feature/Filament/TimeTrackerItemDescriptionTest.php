<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Filament\Resources\TimeTrackers\Pages\CreateTimeTracker;
use App\Models\Customer;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

it('lets every item type carry an optional description', function (): void {
    foreach (TimeTrackerItemTypeEnum::cases() as $type) {
        TimeTrackerItemType::query()->insertOrIgnore(['id' => $type->value, 'name' => $type->label()]);
    }

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    livewire(CreateTimeTracker::class)
        ->fillForm([
            'customer_id' => Customer::factory()->create()->id,
            'user_id' => $admin->id,
            'name' => 'Con notas',
            'date_start' => '2026-10-01',
            'date_end' => '2026-10-03',
            'items' => [
                ['date' => '2026-10-01', 'time_tracker_item_type_id' => TimeTrackerItemTypeEnum::DAY->value, 'description' => 'Armado de escenario'],
                ['date' => '2026-10-02', 'time_tracker_item_type_id' => TimeTrackerItemTypeEnum::EXTRA_HOUR->value, 'hours' => 2, 'description' => 'Cierre tarde'],
                ['date' => '2026-10-03', 'time_tracker_item_type_id' => TimeTrackerItemTypeEnum::CONSOLE->value],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $items = TimeTracker::query()->where('name', 'Con notas')->firstOrFail()->items()->orderBy('date')->get();

    expect($items->pluck('description')->all())->toBe(['Armado de escenario', 'Cierre tarde', null]);
});
