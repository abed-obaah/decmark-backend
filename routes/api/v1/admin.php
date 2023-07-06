<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\One\Admin\AuthController;
use App\Http\Controllers\Api\One\Admin\AccountController;
use App\Http\Controllers\Api\One\Admin\PaymentsController;
use App\Http\Controllers\Api\One\Admin\DashboardController;
use App\Http\Controllers\Api\One\Admin\ServiceProviderController;
use App\Http\Controllers\Api\One\Admin\ServiceRecieverController;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::middleware('auth:api-admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'getData']);
    Route::prefix('account')->group(function () {
        Route::get('/profile', [AccountController::class, 'profile']);
        Route::patch('/update-profile', [AccountController::class, 'updateProfile']);
        Route::get('/profile/{id}', [DashboardController::class, 'viewUser']);
        Route::post('password/request', [AccountController::class, 'requestResetPassword']);
        Route::post('password/reset', [AccountController::class, 'resetPassword']);
    });

    Route::prefix('providers')->group(function() {
        Route::get('/', [ServiceProviderController::class, 'getProviders']);
        Route::get('/{id}', [ServiceProviderController::class, 'viewProvider']);
        Route::delete('/{id}', [ServiceProviderController::class, 'deleteProvider']);
        Route::patch('/suspend/{id}', [ServiceProviderController::class, 'suspendProvider']);
        Route::patch('/unsuspend/{id}', [ServiceProviderController::class, 'unsuspendProvider']);
    });

    Route::prefix('recievers')->group(function () {
        Route::get('/', [ServiceRecieverController::class, 'getAll']);
        Route::get('/{id}', [ServiceRecieverController::class, 'show']);
        Route::delete('/{id}', [ServiceRecieverController::class, 'delete']);
        Route::patch('/suspend/{id}', [ServiceRecieverController::class, 'suspend']);
        Route::patch('/unsuspend/{id}', [ServiceRecieverController::class, 'unsuspend']);
    });

    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentsController::class, 'index']);
    });
});
