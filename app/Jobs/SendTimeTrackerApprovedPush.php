<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\TimeTracker;
use App\Support\Push\FcmClient;
use App\Support\Push\PushResult;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

/**
 * Tells the owner of a time tracker, on their phones, that it was approved.
 */
class SendTimeTrackerApprovedPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [5, 30];

    public function __construct(public int $timeTrackerId) {}

    public function handle(FcmClient $fcm): void
    {
        if (! $fcm->configured()) {
            return;
        }

        $timeTracker = TimeTracker::query()->withoutGlobalScopes()->find($this->timeTrackerId);

        if ($timeTracker === null || ! $timeTracker->approved) {
            return;
        }

        $failed = false;

        foreach (DeviceToken::query()->where('user_id', $timeTracker->user_id)->get() as $device) {
            $result = $fcm->send(
                $device->token,
                __('time_trackers.approved_notification'),
                __('time_trackers.approved_push_body', ['name' => $timeTracker->name]),
                ['type' => 'time_tracker_approved', 'time_tracker_id' => (string) $timeTracker->id],
            );

            match ($result) {
                PushResult::Sent => $device->update(['last_used_at' => now()]),
                PushResult::InvalidToken => $device->delete(),
                PushResult::Failed => $failed = true,
            };
        }

        if ($failed) {
            throw new RuntimeException('Some push notifications could not be delivered.');
        }
    }
}
