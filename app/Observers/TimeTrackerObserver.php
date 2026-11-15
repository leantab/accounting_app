<?php

namespace App\Observers;

use App\Jobs\SendTimeTrackerApprovedPush;
use App\Models\TimeTracker;

class TimeTrackerObserver
{
    /**
     * Fires for approvals made in the panel and in the app alike.
     */
    public function updated(TimeTracker $timeTracker): void
    {
        if ($timeTracker->wasChanged('approved') && $timeTracker->approved) {
            SendTimeTrackerApprovedPush::dispatch($timeTracker->id)->afterCommit();
        }
    }
}
