<?php

namespace App\Providers;

use App\Models\TimeTracker;
use App\Observers\TimeTrackerObserver;
use Illuminate\Support\ServiceProvider;

/**
 * Wiring that exists for the mobile API.
 */
class ApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        TimeTracker::observe(TimeTrackerObserver::class);
    }
}
