<?php

use App\Providers\ApiServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    ApiServiceProvider::class,
    AppServiceProvider::class,
    AdminPanelProvider::class,
    FortifyServiceProvider::class,
];
