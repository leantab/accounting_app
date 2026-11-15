<?php

namespace App\Actions\TimeTracker;

use App\Models\TimeTracker;
use App\Models\User;
use Filament\Notifications\Notification;

class ApproveTimeTrackerAction
{
    /**
     * Marks the tracker as approved and tells its owner, the same way the panel does.
     */
    public static function execute(TimeTracker $timeTracker, User $approver): TimeTracker
    {
        $timeTracker->update([
            'approved' => true,
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);

        AutoCompleteTimeTrackerAction::recalculate($timeTracker);

        $owner = User::query()->find($timeTracker->user_id);

        if ($owner !== null) {
            Notification::make()
                ->title('Planilla aprobada')
                ->body($timeTracker->name)
                ->success()
                ->viewData(['time_tracker_id' => $timeTracker->id])
                ->sendToDatabase($owner);
        }

        return $timeTracker;
    }
}
