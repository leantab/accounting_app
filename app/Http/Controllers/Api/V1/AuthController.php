<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\V1\MeResource;
use App\Models\User;
use App\Support\Api\MobileAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function login(LoginRequest $request, TwoFactorAuthenticationProvider $twoFactor): JsonResponse
    {
        $throttleKey = $request->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'message' => "Demasiados intentos. Probá de nuevo en {$seconds} segundos.",
            ], 429);
        }

        $user = User::query()->where('email', $request->email())->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => 'El email o la contraseña no son correctos.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está desactivada.',
            ]);
        }

        if (! MobileAccess::canUseApp($user)) {
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta todavía no está asociada a una organización.',
            ]);
        }

        if ($this->hasTwoFactorEnabled($user)) {
            $code = $request->string('code')->toString();
            $recoveryCode = $request->string('recovery_code')->toString();

            if ($code === '' && $recoveryCode === '') {
                return response()->json([
                    'message' => 'Ingresá el código de verificación.',
                    'two_factor_required' => true,
                ], 422);
            }

            if (! $this->passesTwoFactor($user, $twoFactor, $code, $recoveryCode)) {
                RateLimiter::hit($throttleKey);

                return response()->json([
                    'message' => 'El código de verificación no es válido.',
                    'two_factor_required' => true,
                    'errors' => ['code' => ['El código de verificación no es válido.']],
                ], 422);
            }
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken($request->string('device_name')->toString());

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new MeResource($user->load(['customer', 'role'])),
        ]);
    }

    public function me(Request $request): MeResource
    {
        return new MeResource($request->user()->load(['customer', 'role']));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /**
     * Always answers the same way, so the endpoint cannot be used to find out which emails are registered.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink(['email' => mb_strtolower($request->string('email')->toString())]);

        return response()->json([
            'message' => 'Si el email está registrado, te enviamos un enlace para restablecer tu contraseña.',
        ]);
    }

    private function hasTwoFactorEnabled(User $user): bool
    {
        return $user->two_factor_secret !== null && $user->two_factor_confirmed_at !== null;
    }

    private function passesTwoFactor(
        User $user,
        TwoFactorAuthenticationProvider $twoFactor,
        string $code,
        string $recoveryCode,
    ): bool {
        if ($code !== '') {
            return $twoFactor->verify(decrypt($user->two_factor_secret), $code);
        }

        $matched = collect($user->recoveryCodes())
            ->first(fn (string $candidate): bool => hash_equals($candidate, $recoveryCode));

        if ($matched === null) {
            return false;
        }

        $user->replaceRecoveryCode($matched);

        return true;
    }
}
