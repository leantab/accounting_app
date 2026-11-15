<?php

namespace App\Support\Push;

use Illuminate\Support\Facades\Http;

/**
 * Sends one push notification through the FCM HTTP v1 API.
 */
class FcmClient
{
    public function __construct(private readonly FcmCredentials $credentials) {}

    public function configured(): bool
    {
        return $this->credentials->configured();
    }

    /**
     * @param  array<string, string>  $data  Extra values the app reads when the notification is opened.
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): PushResult
    {
        $response = Http::withToken($this->credentials->accessToken())
            ->acceptJson()
            ->post("https://fcm.googleapis.com/v1/projects/{$this->credentials->projectId()}/messages:send", [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => (object) $data,
                    'android' => ['priority' => 'high'],
                ],
            ]);

        if ($response->successful()) {
            return PushResult::Sent;
        }

        $status = $response->json('error.status');
        $errorCode = $response->json('error.details.0.errorCode');

        if ($response->status() === 404 || $errorCode === 'UNREGISTERED' || ($status === 'INVALID_ARGUMENT' && $errorCode === 'INVALID_ARGUMENT')) {
            return PushResult::InvalidToken;
        }

        return PushResult::Failed;
    }
}
