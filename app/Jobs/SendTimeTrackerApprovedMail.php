<?php

namespace App\Jobs;

use App\Mail\ResendSender;
use App\Mail\TimeTrackerApprovedMail;
use App\Models\TimeTracker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Resend\Exceptions\ErrorException;

class SendTimeTrackerApprovedMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    /**
     * Resend errors that retrying cannot fix: bad request, API key or domain.
     *
     * @var list<int>
     */
    private const NON_RETRYABLE_STATUS_CODES = [400, 401, 403, 422];

    public function __construct(public TimeTracker $timeTracker) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [1, 5, 30];
    }

    public function handle(): void
    {
        $user = $this->timeTracker->user;

        if ($user === null || blank($user->email)) {
            return;
        }

        try {
            ResendSender::send(
                new TimeTrackerApprovedMail($this->timeTracker),
                $user->email,
                'time-tracker-approved/'.$this->timeTracker->id.'-'.(int) round((float) $this->timeTracker->amount * 100),
            );
        } catch (ErrorException $exception) {
            if (in_array($exception->getErrorCode(), self::NON_RETRYABLE_STATUS_CODES, true)) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }
}
