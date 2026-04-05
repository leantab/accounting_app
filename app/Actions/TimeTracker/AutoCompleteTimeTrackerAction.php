<?php

namespace App\Actions\TimeTracker;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Models\TimeTracker;
use App\Models\User;
use Carbon\Carbon;

class AutoCompleteTimeTrackerAction
{
    public static function execute(): void
    {
        $timeTrackers = TimeTracker::query()
            ->whereNull('amount')
            ->with(['items.timeTrackerItemType'])
            ->get();

        foreach ($timeTrackers as $timeTracker) {
            $user = User::find($timeTracker->user_id);

            if ($user === null) {
                continue;
            }

            $rates = $user->userRates;

            $amount = 0.0;

            foreach ($timeTracker->items as $item) {
                $rate = (float) ($rates->firstWhere('time_tracker_item_type_id', $item->time_tracker_item_type_id)?->rate ?? 0);

                if (empty($item->hours)) {
                    if ($item->time_start !== null && $item->time_end !== null) {
                        $hours = (int) Carbon::parse($item->time_start)->diffInHours(Carbon::parse($item->time_end));
                        $item->update([
                            'hours' => $hours,
                        ]);
                    }
                }

                $item->refresh();

                $itemHours = (float) ($item->hours ?? 0);
                $itemAmount = (float) ($item->amount ?? 0);

                if ($item->timeTrackerItemType->name === TimeTrackerItemTypeEnum::HOURS->label()) {
                    $itemAmount = $itemHours * $rate;
                } elseif ($rate > 0) {
                    $itemAmount = $rate;
                }

                $item->update([
                    'amount' => $itemAmount,
                ]);

                $amount += $itemAmount;
            }

            $timeTracker->update([
                'amount' => $amount,
            ]);
        }
    }
}
