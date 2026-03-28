<?php

namespace App\Actions\TimeTracker;

use App\Models\TimeTracker;
use App\Models\User;
use Carbon\Carbon;

class AutoCompleteAction
{
    public function execute()
    {
        $timeTrackers = TimeTracker::whereNull('amount  ')->get();

        foreach ($timeTrackers as $timeTracker) {
            $user = User::find($timeTracker->user_id);
            $rates = $user->userRates;

            $amount = 0;
            foreach ($timeTracker->items as $item) {
                $rate = $rates->where('time_tracker_item_type_id', $item->time_tracker_item_type_id)->first()->rate;
                if (empty($item->hours)) {
                    $hours = Carbon::parse($item->time_end)->diffInHours(Carbon::parse($item->time_start));
                    $item->update([
                        'hours' => $hours,
                    ]);
                }
                $item->fresh();
                $amount += $item->hours * $rate;
            }

            $timeTracker->update([
                'amount' => $amount,
            ]);
        }
    }
}
