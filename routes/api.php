<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CheckInController;
use App\Http\Controllers\Api\V1\CompanyController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DefaultRateController;
use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OptionController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TimeTrackerController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1')
        ->name('auth.forgot-password');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::get('lookups', LookupController::class)->name('lookups');
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('options/{resource}', OptionController::class)->name('options');

        Route::post('time-trackers/auto-complete', [TimeTrackerController::class, 'autoComplete'])
            ->name('time-trackers.auto-complete');
        Route::post('time-trackers/{timeTracker}/approve', [TimeTrackerController::class, 'approve'])
            ->whereNumber('timeTracker')
            ->name('time-trackers.approve');
        Route::post('time-trackers/{timeTracker}/recalculate', [TimeTrackerController::class, 'recalculate'])
            ->whereNumber('timeTracker')
            ->name('time-trackers.recalculate');
        Route::apiResource('time-trackers', TimeTrackerController::class)
            ->parameters(['time-trackers' => 'timeTracker'])
            ->whereNumber('timeTracker');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
        Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::delete('devices', [DeviceController::class, 'destroy'])->name('devices.destroy');

        Route::post('users/invite', [UserController::class, 'invite'])->name('users.invite');

        foreach ([
            'companies' => CompanyController::class,
            'projects' => ProjectController::class,
            'invoices' => InvoiceController::class,
            'payments' => PaymentController::class,
            'customers' => CustomerController::class,
            'users' => UserController::class,
        ] as $name => $controller) {
            Route::apiResource($name, $controller)->parameters([$name => 'id'])->whereNumber('id');
        }

        Route::get('default-rates', [DefaultRateController::class, 'show'])->name('default-rates.show');
        Route::put('default-rates', [DefaultRateController::class, 'update'])->name('default-rates.update');

        Route::get('check-ins/mine', [CheckInController::class, 'mine'])->name('check-ins.mine');
        Route::get('check-ins', [CheckInController::class, 'index'])->name('check-ins.index');
        Route::post('check-ins', [CheckInController::class, 'store'])->name('check-ins.store');
    });
});
