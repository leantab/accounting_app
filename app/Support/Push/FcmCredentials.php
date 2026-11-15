<?php

namespace App\Support\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Reads the Firebase service account and exchanges it for a short-lived access token.
 */
class FcmCredentials
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function configured(): bool
    {
        $path = config('fcm.credentials');

        return is_string($path) && $path !== '' && is_readable($path);
    }

    public function projectId(): string
    {
        return (string) $this->account()['project_id'];
    }

    public function accessToken(): string
    {
        return Cache::remember('fcm.access_token', now()->addMinutes(50), function (): string {
            $account = $this->account();

            $response = Http::asForm()->post($account['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->assertion($account),
            ])->throw();

            return (string) $response->json('access_token');
        });
    }

    /**
     * @return array{project_id: string, client_email: string, private_key: string, token_uri: string}
     */
    private function account(): array
    {
        $account = json_decode((string) file_get_contents((string) config('fcm.credentials')), true);

        if (! is_array($account) || ! isset($account['project_id'], $account['client_email'], $account['private_key'])) {
            throw new RuntimeException('The Firebase service account file is not valid.');
        }

        $account['token_uri'] ??= 'https://oauth2.googleapis.com/token';

        return $account;
    }

    /**
     * A signed JWT asking Google for a messaging token on behalf of the service account.
     *
     * @param  array{client_email: string, private_key: string, token_uri: string}  $account
     */
    private function assertion(array $account): string
    {
        $encode = fn (array $part): string => rtrim(strtr(base64_encode((string) json_encode($part)), '+/', '-_'), '=');

        $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
            'iss' => $account['client_email'],
            'scope' => self::SCOPE,
            'aud' => $account['token_uri'],
            'iat' => time(),
            'exp' => time() + 3600,
        ]);

        if (! openssl_sign($unsigned, $signature, $account['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('The Firebase service account key could not sign the request.');
        }

        return $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }
}
