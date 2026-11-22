<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->restrictSettingsHubToPlatformAdmins();
    }

    /**
     * With shield mode on, the settings hub checks `can('')` to open a page and `can('View:PageName')`
     * to list each card. No shield package is installed, so only platform admins pass those checks.
     */
    protected function restrictSettingsHubToPlatformAdmins(): void
    {
        Gate::before(fn (?User $user, string $ability): ?bool => $ability === '' ? (bool) $user?->is_admin : null);

        foreach (['SettingsHub', 'SiteSettings', 'SocialMenuSettings', 'ColorSettings', 'PWASettingsPage'] as $page) {
            Gate::define('View:'.$page, fn (?User $user): bool => (bool) $user?->is_admin);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
