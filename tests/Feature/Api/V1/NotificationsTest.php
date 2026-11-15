<?php

use App\Jobs\SendTimeTrackerApprovedPush;
use App\Models\DeviceToken;
use App\Models\TimeTracker;
use App\Models\User;
use App\Support\Push\FcmCredentials;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\mock;
use function Pest\Laravel\postJson;

uses(TestCase::class, RefreshDatabase::class);

function approvableTracker(User $owner, User $worker): TimeTracker
{
    return TimeTracker::factory()->create([
        'customer_id' => $owner->customer_id,
        'user_id' => $worker->id,
        'project_id' => null,
        'invoice_id' => null,
        'payment_id' => null,
        'approved_by' => null,
        'approved' => false,
        'name' => 'Festival marzo',
    ]);
}

function fakeFcmCredentials(): void
{
    mock(FcmCredentials::class, function ($mock): void {
        $mock->shouldReceive('configured')->andReturn(true);
        $mock->shouldReceive('projectId')->andReturn('riders-flow-test');
        $mock->shouldReceive('accessToken')->andReturn('token-de-prueba');
    });
}

test('a phone is registered once and moves to whoever signs in on it', function (): void {
    $first = User::factory()->customerEmployee()->create();
    $second = User::factory()->customerEmployee()->create();

    Sanctum::actingAs($first);
    postJson('/api/v1/devices', ['token' => 'abc:123', 'platform' => 'android'])->assertOk();
    postJson('/api/v1/devices', ['token' => 'abc:123', 'platform' => 'android'])->assertOk();
    postJson('/api/v1/devices', ['token' => 'abc:123', 'platform' => 'windows'])->assertUnprocessable();

    expect(DeviceToken::query()->count())->toBe(1)
        ->and(DeviceToken::query()->first()->user_id)->toBe($first->id);

    Sanctum::actingAs($second);
    postJson('/api/v1/devices', ['token' => 'abc:123', 'platform' => 'android'])->assertOk();

    expect(DeviceToken::query()->first()->user_id)->toBe($second->id);

    Sanctum::actingAs($first);
    deleteJson('/api/v1/devices', ['token' => 'abc:123'])->assertOk();

    expect(DeviceToken::query()->count())->toBe(1);

    Sanctum::actingAs($second);
    deleteJson('/api/v1/devices', ['token' => 'abc:123'])->assertOk();

    expect(DeviceToken::query()->count())->toBe(0);
});

test('approving from the app notifies the worker in the list and queues a push', function (): void {
    Queue::fake([SendTimeTrackerApprovedPush::class]);

    $owner = User::factory()->customerOwner()->create();
    $worker = User::factory()->create(['customer_id' => $owner->customer_id]);
    $tracker = approvableTracker($owner, $worker);

    Sanctum::actingAs($owner);
    postJson("/api/v1/time-trackers/{$tracker->id}/approve")->assertOk();

    Queue::assertPushed(SendTimeTrackerApprovedPush::class, fn ($job): bool => $job->timeTrackerId === $tracker->id);

    Sanctum::actingAs($worker);

    $response = getJson('/api/v1/notifications')->assertOk();
    $id = $response->json('data.0.id');

    expect($response->json('meta.unread'))->toBe(1)
        ->and($response->json('data.0'))->toMatchArray([
            'title' => 'Planilla aprobada',
            'body' => 'Festival marzo',
            'time_tracker_id' => $tracker->id,
            'read_at' => null,
        ]);

    getJson('/api/v1/dashboard')->assertJsonPath('data.badges.notifications_unread', 1);
    postJson("/api/v1/notifications/{$id}/read")->assertOk()->assertJsonPath('unread', 0);

    Sanctum::actingAs($owner);
    postJson("/api/v1/notifications/{$id}/read")->assertNotFound();
});

test('approving in the panel also queues a push, and other changes do not', function (): void {
    Queue::fake();

    $owner = User::factory()->customerOwner()->create();
    $tracker = approvableTracker($owner, $owner);

    $tracker->update(['name' => 'Otro nombre']);
    Queue::assertNothingPushed();

    $tracker->update(['approved' => true, 'approved_at' => now(), 'approved_by' => $owner->id]);
    Queue::assertPushed(SendTimeTrackerApprovedPush::class, 1);

    $tracker->update(['billed' => true]);
    Queue::assertPushed(SendTimeTrackerApprovedPush::class, 1);
});

test('all notifications can be marked as read', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $worker = User::factory()->create(['customer_id' => $owner->customer_id]);

    Sanctum::actingAs($owner);
    postJson('/api/v1/time-trackers/'.approvableTracker($owner, $worker)->id.'/approve')->assertOk();
    postJson('/api/v1/time-trackers/'.approvableTracker($owner, $worker)->id.'/approve')->assertOk();

    Sanctum::actingAs($worker);
    postJson('/api/v1/notifications/read-all')->assertOk();

    expect($worker->unreadNotifications()->count())->toBe(0)
        ->and($worker->notifications()->count())->toBe(2);
});

test('the push goes to every phone of the worker and forgets dead tokens', function (): void {
    fakeFcmCredentials();

    $owner = User::factory()->customerOwner()->create();
    $worker = User::factory()->create(['customer_id' => $owner->customer_id]);
    $tracker = approvableTracker($owner, $worker);
    $tracker->updateQuietly(['approved' => true]);

    DeviceToken::query()->create(['user_id' => $worker->id, 'token' => 'vivo', 'platform' => 'android']);
    DeviceToken::query()->create(['user_id' => $worker->id, 'token' => 'muerto', 'platform' => 'android']);
    DeviceToken::query()->create(['user_id' => $owner->id, 'token' => 'ajeno', 'platform' => 'android']);

    Http::fake(fn (Request $request) => $request['message']['token'] === 'muerto'
        ? Http::response(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404)
        : Http::response(['name' => 'projects/riders-flow-test/messages/1']));

    SendTimeTrackerApprovedPush::dispatchSync($tracker->id);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://fcm.googleapis.com/v1/projects/riders-flow-test/messages:send'
        && $request->hasHeader('Authorization', 'Bearer token-de-prueba')
        && $request['message']['token'] === 'vivo'
        && $request['message']['notification']['title'] === 'Planilla aprobada'
        && ((array) $request['message']['data'])['time_tracker_id'] === (string) $tracker->id);

    expect(DeviceToken::query()->pluck('token')->sort()->values()->all())->toBe(['ajeno', 'vivo']);
});

test('nothing is sent while firebase is not configured', function (): void {
    Http::fake();
    config(['fcm.credentials' => null]);

    $owner = User::factory()->customerOwner()->create();
    $tracker = approvableTracker($owner, $owner);
    $tracker->updateQuietly(['approved' => true]);
    DeviceToken::query()->create(['user_id' => $owner->id, 'token' => 'vivo', 'platform' => 'android']);

    SendTimeTrackerApprovedPush::dispatchSync($tracker->id);

    Http::assertNothingSent();
});
