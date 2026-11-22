<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Filament\Resources\TimeTrackers\Pages\EditTimeTracker;
use App\Filament\Resources\TimeTrackers\Pages\ViewTimeTracker;
use App\Models\Customer;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItem;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return array{tracker: TimeTracker, daily: TimeTrackerItem, extra: TimeTrackerItem}
 */
function trackerWithDailyAndExtraItems(): array
{
    foreach (TimeTrackerItemTypeEnum::cases() as $type) {
        TimeTrackerItemType::query()->insertOrIgnore(['id' => $type->value, 'name' => $type->label()]);
    }

    $tracker = TimeTracker::factory()->create(['customer_id' => Customer::factory()->create()->id]);

    $daily = $tracker->items()->create([
        'date' => '2026-10-01',
        'time_tracker_item_type_id' => TimeTrackerItemTypeEnum::DAY->value,
        'hours' => 91,
        'description' => 'Armado',
    ]);
    $extra = $tracker->items()->create([
        'date' => '2026-10-02',
        'time_tracker_item_type_id' => TimeTrackerItemTypeEnum::EXTRA_HOUR_RIGGING_MAIN->value,
        'hours' => 37,
    ]);

    return ['tracker' => $tracker, 'daily' => $daily, 'extra' => $extra];
}

it('shows the hours field in the form only for extra hour types', function (): void {
    ['tracker' => $tracker, 'daily' => $daily, 'extra' => $extra] = trackerWithDailyAndExtraItems();

    actingAs(User::factory()->admin()->create());

    livewire(EditTimeTracker::class, ['record' => $tracker->id])
        ->assertFormFieldIsHidden("items.record-{$daily->id}.hours")
        ->assertFormFieldIsVisible("items.record-{$extra->id}.hours");
});

it('keeps start time, end time and description visible for every item type', function (): void {
    ['tracker' => $tracker, 'daily' => $daily, 'extra' => $extra] = trackerWithDailyAndExtraItems();

    actingAs(User::factory()->admin()->create());

    $page = livewire(EditTimeTracker::class, ['record' => $tracker->id]);

    foreach ([$daily, $extra] as $item) {
        foreach (['time_start', 'time_end', 'description'] as $field) {
            $page->assertFormFieldIsVisible("items.record-{$item->id}.{$field}");
        }
    }
});

it('shows the hours of an item on the detail page only for extra hour types', function (): void {
    ['tracker' => $tracker] = trackerWithDailyAndExtraItems();

    actingAs(User::factory()->admin()->create());

    livewire(ViewTimeTracker::class, ['record' => $tracker->id])
        ->assertSee('37')
        ->assertDontSee('91');
});
