<?php

use App\Actions\TimeTracker\AutoCompleteTimeTrackerAction;
use App\Enums\TimeTrackerItemTypeEnum;
use App\Jobs\SendTimeTrackerApprovedMail;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Resend\Exceptions\ErrorException;
use Resend\Laravel\Facades\Resend;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function trackerReadyToComplete(bool $approved = true, bool $withRate = true): TimeTracker
{
    $customer = Customer::factory()->create();
    $worker = User::factory()->create(['customer_id' => $customer->id]);
    $dayId = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);

    if ($withRate) {
        DefaultRate::query()->create(['customer_id' => $customer->id, 'time_tracker_item_type_id' => $dayId, 'rate' => 12345.5]);
    }

    $tracker = ($approved ? TimeTracker::factory()->approved() : TimeTracker::factory())
        ->create(['customer_id' => $customer->id, 'user_id' => $worker->id, 'name' => 'Obra Norte', 'amount' => null]);
    $tracker->items()->create(['date' => '2026-10-01', 'time_tracker_item_type_id' => $dayId]);

    return $tracker;
}

it('queues one email job for each approved tracker that ends with a total', function (): void {
    Queue::fake();
    $tracker = trackerReadyToComplete();

    AutoCompleteTimeTrackerAction::execute();

    Queue::assertPushed(SendTimeTrackerApprovedMail::class, 1);
    Queue::assertPushed(SendTimeTrackerApprovedMail::class, fn ($job): bool => $job->timeTracker->is($tracker));
});

it('does not calculate nor email trackers that are not approved', function (): void {
    Queue::fake();
    $tracker = trackerReadyToComplete(approved: false);

    $result = AutoCompleteTimeTrackerAction::execute();

    expect($result['trackers'])->toBe(0)
        ->and($tracker->fresh()->amount)->toBeNull();
    Queue::assertNothingPushed();
});

it('does not email when the final amount is zero', function (): void {
    Queue::fake();
    trackerReadyToComplete(withRate: false);

    AutoCompleteTimeTrackerAction::execute();

    Queue::assertNothingPushed();
});

it('does not email the same tracker again once it has a total', function (): void {
    Queue::fake();
    trackerReadyToComplete();

    AutoCompleteTimeTrackerAction::execute();
    AutoCompleteTimeTrackerAction::execute();

    Queue::assertPushed(SendTimeTrackerApprovedMail::class, 1);
});

it('sends the approval email through the Resend facade in Spanish with the amount to invoice', function (): void {
    $tracker = trackerReadyToComplete();
    $tracker->update(['amount' => 12345.5]);

    $emails = Mockery::mock();
    $emails->shouldReceive('send')->once()->withArgs(function (array $payload, array $options) use ($tracker): bool {
        return $payload['to'] === [$tracker->user->email]
            && $payload['subject'] === 'Tu planilla "Obra Norte" fue aprobada'
            && str_contains($payload['html'], 'Ya fue aprobada tu planilla')
            && str_contains($payload['html'], '$ 12.345,50')
            && $options === ['idempotency_key' => 'time-tracker-approved/'.$tracker->id.'-1234550'];
    });
    Resend::shouldReceive('emails')->once()->andReturn($emails);

    (new SendTimeTrackerApprovedMail($tracker))->handle();
});

it('fails the job without retrying when Resend rejects the request', function (int $status, bool $retried): void {
    $tracker = trackerReadyToComplete();
    $tracker->update(['amount' => 100]);

    $emails = Mockery::mock();
    $emails->shouldReceive('send')->once()->andThrow(new ErrorException(['message' => 'error', 'name' => 'validation_error', 'statusCode' => $status]));
    Resend::shouldReceive('emails')->once()->andReturn($emails);

    $job = (new SendTimeTrackerApprovedMail($tracker))->setJob(Mockery::mock(Job::class)->shouldIgnoreMissing());

    if ($retried) {
        expect(fn () => $job->handle())->toThrow(ErrorException::class);
    } else {
        $job->handle();
    }
})->with([
    'validation error is not retried' => [422, false],
    'invalid api key is not retried' => [401, false],
    'rate limit is retried' => [429, true],
    'server error is retried' => [500, true],
]);

it('implements ShouldQueue so the email never slows the button down', function (): void {
    expect(new SendTimeTrackerApprovedMail(TimeTracker::factory()->make()))
        ->toBeInstanceOf(ShouldQueue::class);
});
