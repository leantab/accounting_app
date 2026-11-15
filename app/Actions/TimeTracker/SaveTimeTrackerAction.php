<?php

namespace App\Actions\TimeTracker;

use App\Models\TimeTracker;
use App\Support\Api\ItemTypes;
use Illuminate\Support\Facades\DB;

class SaveTimeTrackerAction
{
    /**
     * Creates or updates a tracker and replaces its items with the given list.
     *
     * Fields that do not apply to the type of an item are left as they were, like the hidden fields of the panel form.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>|null  $items  Null leaves the items untouched.
     */
    public static function execute(?TimeTracker $timeTracker, array $attributes, ?array $items): TimeTracker
    {
        return DB::transaction(function () use ($timeTracker, $attributes, $items): TimeTracker {
            if ($timeTracker === null) {
                $timeTracker = TimeTracker::query()->create($attributes);
            } else {
                $timeTracker->update($attributes);
            }

            if ($items === null) {
                return $timeTracker;
            }

            $keptIds = [];

            foreach ($items as $item) {
                $type = ItemTypes::enumFor((int) $item['time_tracker_item_type_id']);

                $data = [
                    'time_tracker_item_type_id' => (int) $item['time_tracker_item_type_id'],
                    'date' => $item['date'],
                    'hours' => $type?->isHourly() ? ($item['hours'] ?? null) : null,
                    'time_start' => $item['time_start'] ?? null,
                    'time_end' => $item['time_end'] ?? null,
                ];

                if (ItemTypes::showsDescription($type)) {
                    $data['description'] = $item['description'] ?? null;
                }

                if (ItemTypes::showsAmount($type)) {
                    $data['amount'] = $item['amount'] ?? null;
                }

                $existing = isset($item['id'])
                    ? $timeTracker->items()->whereKey($item['id'])->first()
                    : null;

                if ($existing === null) {
                    $keptIds[] = $timeTracker->items()->create($data)->id;

                    continue;
                }

                $existing->update($data);
                $keptIds[] = $existing->id;
            }

            $timeTracker->items()->whereNotIn('id', $keptIds)->delete();

            return $timeTracker;
        });
    }
}
