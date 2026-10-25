<?php

namespace App\Actions\Rates;

use App\Models\DefaultRate;
use App\Models\UserRate;
use Illuminate\Support\Collection;

class ResolveEffectiveUserRate
{
    /**
     * A custom user rate wins, including an explicit zero. The customer default is used only when that user has no row for the item type.
     *
     * @param  Collection<int, UserRate>  $userRates
     * @param  Collection<int, DefaultRate>  $defaultRates
     */
    public static function execute(Collection $userRates, Collection $defaultRates, int $timeTrackerItemTypeId): ?float
    {
        $customRate = $userRates->firstWhere('time_tracker_item_type_id', $timeTrackerItemTypeId);

        if ($customRate !== null) {
            return (float) $customRate->rate;
        }

        $defaultRate = $defaultRates->firstWhere('time_tracker_item_type_id', $timeTrackerItemTypeId);

        if ($defaultRate === null) {
            return null;
        }

        return (float) $defaultRate->rate;
    }
}
