<?php

namespace App\Actions\TimeTracker;

use App\Actions\Rates\ResolveEffectiveUserRate;
use App\Enums\TimeTrackerItemTypeEnum;
use App\Models\DefaultRate;
use App\Models\TimeTracker;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AutoCompleteTimeTrackerAction
{
    /**
     * Trackers without a total, or with a zero total from an earlier run before rates existed, are calculated again.
     *
     * @return array{trackers: int, missing_rates: list<string>}
     */
    public static function execute(): array
    {
        $missingRates = [];

        $timeTrackers = TimeTracker::query()
            ->where(fn ($query) => $query->whereNull('amount')->orWhere('amount', 0))
            ->with(['items.timeTrackerItemType', 'user.userRates'])
            ->get();

        $defaultRatesByCustomer = DefaultRate::query()
            ->whereIn('customer_id', $timeTrackers->pluck('customer_id')->filter()->unique()->values())
            ->get()
            ->groupBy(fn (DefaultRate $defaultRate): int => (int) $defaultRate->customer_id);

        foreach ($timeTrackers as $timeTracker) {
            $user = $timeTracker->user;

            if ($user === null) {
                continue;
            }

            /** @var Collection<int, DefaultRate> $defaultRates */
            $defaultRates = $defaultRatesByCustomer->get((int) $user->customer_id, collect());

            $amount = 0.0;

            foreach ($timeTracker->items as $item) {
                $rate = ResolveEffectiveUserRate::execute(
                    $user->userRates,
                    $defaultRates,
                    (int) $item->time_tracker_item_type_id,
                ) ?? 0.0;

                $isHourly = in_array($item->timeTrackerItemType->name, TimeTrackerItemTypeEnum::hourlyLabels(), true);

                if ($isHourly && empty($item->hours)) {
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

                if ($isHourly) {
                    $itemAmount = $itemHours * $rate;
                } elseif ($rate > 0) {
                    $itemAmount = $rate;
                }

                if ($rate <= 0 && $itemAmount <= 0) {
                    $missingRates[$item->timeTrackerItemType->name] = $item->timeTrackerItemType->name;
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

        return [
            'trackers' => $timeTrackers->count(),
            'missing_rates' => array_values($missingRates),
        ];
    }
}
