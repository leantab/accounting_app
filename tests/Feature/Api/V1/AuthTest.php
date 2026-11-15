<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

use function Pest\Laravel\getJson;
use function Pest\Laravel\mock;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(TestCase::class, RefreshDatabase::class);

function apiLogin(User $user, array $overrides = []): TestResponse
{
    return postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'Pixel de prueba',
        ...$overrides,
    ]);
}

test('a customer user logs in and receives a token with their abilities', function (): void {
    $user = User::factory()->customerOwner()->create();

    $response = apiLogin($user)->assertOk();

    $response
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.customer.id', $user->customer_id)
        ->assertJsonPath('user.abilities.invoices.view', true)
        ->assertJsonPath('user.abilities.customers.view', false);

    expect($response->json('token'))->toBeString()->not->toBeEmpty()
        ->and($user->tokens()->count())->toBe(1);
});

test('login is case insensitive on the email', function (): void {
    $user = User::factory()->customerOwner()->create(['email' => 'persona@example.com']);

    apiLogin($user, ['email' => 'Persona@Example.com'])->assertOk();
});

test('a wrong password is rejected', function (): void {
    $user = User::factory()->customerOwner()->create();

    apiLogin($user, ['password' => 'incorrecta'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect($user->tokens()->count())->toBe(0);
});

test('an inactive user cannot log in', function (): void {
    $user = User::factory()->customerOwner()->create(['is_active' => false]);

    apiLogin($user)->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('a user without a customer cannot log in unless they are a platform admin', function (): void {
    $orphan = User::factory()->create();
    $admin = User::factory()->admin()->create();

    apiLogin($orphan)->assertUnprocessable()->assertJsonValidationErrors('email');
    apiLogin($admin)->assertOk()->assertJsonPath('user.abilities.customers.view', true);
});

test('login is throttled after repeated failures', function (): void {
    $user = User::factory()->customerOwner()->create();

    foreach (range(1, 5) as $attempt) {
        apiLogin($user, ['password' => 'incorrecta'])->assertUnprocessable();
    }

    apiLogin($user)->assertTooManyRequests();
});

test('a user with two factor enabled must send a code', function (): void {
    $user = User::factory()->customerOwner()->withTwoFactor()->create();

    apiLogin($user)
        ->assertUnprocessable()
        ->assertJsonPath('two_factor_required', true);

    expect($user->tokens()->count())->toBe(0);
});

test('a valid two factor code completes the login', function (): void {
    $user = User::factory()->customerOwner()->withTwoFactor()->create();

    mock(TwoFactorAuthenticationProvider::class)
        ->shouldReceive('verify')
        ->andReturnUsing(fn (string $secret, string $code): bool => $code === '123456');

    apiLogin($user, ['code' => '000000'])
        ->assertUnprocessable()
        ->assertJsonPath('two_factor_required', true);

    apiLogin($user, ['code' => '123456'])->assertOk();
});

test('a recovery code completes the login once', function (): void {
    $user = User::factory()->customerOwner()->withTwoFactor()->create();

    apiLogin($user, ['recovery_code' => 'recovery-code-1'])->assertOk();
    apiLogin($user, ['recovery_code' => 'recovery-code-1'])->assertUnprocessable();
});

test('me requires a token', function (): void {
    getJson('/api/v1/me')->assertUnauthorized();
});

test('me returns the user with role and abilities', function (): void {
    $user = User::factory()->customerEmployee()->create();

    Sanctum::actingAs($user);

    getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.role_id', UserRoleEnum::Employee->value)
        ->assertJsonPath('data.abilities.time_trackers.view', true)
        ->assertJsonPath('data.abilities.time_trackers.approve', false)
        ->assertJsonPath('data.abilities.check_ins.record', true)
        ->assertJsonMissingPath('data.password');
});

test('logout revokes the token in use', function (): void {
    $user = User::factory()->customerOwner()->create();
    $token = apiLogin($user)->json('token');

    withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

test('forgot password answers the same for known and unknown emails', function (): void {
    Notification::fake();

    $user = User::factory()->customerOwner()->create();

    $known = postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();
    $unknown = postJson('/api/v1/auth/forgot-password', ['email' => 'nadie@example.com'])->assertOk();

    expect($known->json('message'))->toBe($unknown->json('message'));

    Notification::assertSentToTimes($user, ResetPassword::class, 1);
});
