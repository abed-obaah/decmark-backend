<?php

use App\Enums\OauthDriverEnum;
use App\Http\Controllers\AddressVerificationController;
use App\Http\Controllers\VerifyMeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\One\User\ArtisanController;
use App\Http\Controllers\Api\One\User\RideController;
use App\Http\Controllers\Api\One\User\WalletController;
use App\Http\Controllers\Api\One\User\AccountController;
use App\Http\Controllers\Api\One\User\CourierController;
use App\Http\Controllers\Api\One\User\ServiceController;
use App\Http\Controllers\Api\One\User\BankCardController;
use App\Http\Controllers\Api\One\User\ProviderController;
use App\Http\Controllers\Api\One\User\Auth\AuthController;
use App\Http\Controllers\Api\One\User\Auth\LoginController;
use App\Http\Controllers\Api\One\User\Auth\PasswordController;
use App\Http\Controllers\Api\One\User\Auth\RegisterController;
use App\Http\Controllers\Api\One\User\Auth\EmailPhoneController;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login', [LoginController::class, 'login'])->name('login');
    Route::put('register', [RegisterController::class, 'register'])->name('register');
    Route::put('register_business', [RegisterController::class, 'register_business'])->name('register_business');
    Route::post('change_password', [PasswordController::class, 'changePassword'])->name('changePassword');

    Route::post(
        'password/request',
        [PasswordController::class, 'requestResetPassword']
    )->name('password.request');
    Route::post(
        'password/reset',
        [PasswordController::class, 'resetPassword']
    )->name('password.reset');

    Route::post(
        'email/resend',
        [EmailPhoneController::class, 'emailResend']
    )->name('email.resend');
    Route::post(
        'email/verify',
        [EmailPhoneController::class, 'emailVerify']
    )->name('email.verify');

    Route::post(
        'phone/resend',
        [EmailPhoneController::class, 'phoneResend']
    )->name('phone.resend');
    Route::post(
        'phone/verify',
        [EmailPhoneController::class, 'phoneVerify']
    )->name('phone.verify');

    Route::post(
        'social/{driver}',
        [AuthController::class, 'soicialLogin']
    )->name('social')->where('driver', implode('|', OauthDriverEnum::values()));

    Route::post('code_to_token', [AuthController::class, 'codeToToken'])->name('codeToToken');

    Route::post('sudo', [AuthController::class, 'sudo'])->name('sudo');

    Route::post(
        'pin/create',
        [AuthController::class, 'pinCreate']
    )->name('pin.create');
    Route::post(
        'pin/change',
        [AuthController::class, 'pinChange']
    )->name('pin.change');

    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
});

Route::middleware(['auth'])->group(function () {
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('', [AccountController::class, 'index'])->name('index');
        Route::get('/isVerified', [AccountController::class, 'checkVerification']);
        Route::get('/notifications', [AccountController::class, 'notifications'])->name('notifications');
        Route::get('/logs', [AccountController::class, 'logs'])->name('logs');
        Route::patch('/fcmUpdate', [AccountController::class, 'addFcm'])->name('fcm.add');
        Route::delete('/fcmUpdate', [AccountController::class, 'removeFcm'])->name('fcm.remove');
        Route::post('', [AccountController::class, 'update']);
    });

    // Route::prefix('messages')->name('messages.')->group(function () {
    //     Route::get('', [AccountController::class, 'index'])->name('index');
    //     Route::get('/{thread}', [AccountController::class, 'show'])->name('show');
    //     Route::post('/{thread}', [AccountController::class, 'store']);
    // });

    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('', [WalletController::class, 'index'])->name('index');

        Route::post('transfer', [WalletController::class, 'transfer'])->name('transfer');
        //Route::post('transfer/multiple', [WalletController::class, 'transferMultiple'])->name('transfer.multiple');

        Route::post('/topup/card', [WalletController::class, 'fundWithCard'])->name('topup.card');

        Route::get(
            'transactions',
            [WalletController::class, 'transactionHistory']
        )->name('transaction.index');
        Route::get(
            'transactions/{transaction}',
            [WalletController::class, 'transaction']
        )->whereUuid('transaction')->name('transaction.single');
        Route::get(
            'transactions/latest/{count?}',
            [WalletController::class, 'transactionLatest']
        )->name('transaction.latest')->whereNumber('count');
        Route::post('transactions/report', [WalletController::class, 'reportTransaction'])->name('transaction.report');

        //Route::get('qr/{format}', [WalletController::class, 'qr'])->name('qr');
    });

    Route::prefix('bank_cards')->name('bank_cards.')->group(function () {
        Route::get('', [BankCardController::class, 'index'])->name('index');
        Route::post('', [BankCardController::class, 'store'])->name('start');
        Route::get('{card}', [BankCardController::class, 'show'])->name('show');
        Route::delete('{card}', [BankCardController::class, 'delete'])->name('delete');
    });

    Route::prefix('artisan')->name('artisan.')->group(function () {
        Route::get('/appointments', [ArtisanController::class, 'show_current']);
        Route::get('/appointments/new', [ArtisanController::class, 'show_new_appointments']);
        Route::get('/appointments/ongoing', [ArtisanController::class, 'show_ongoing']);
        Route::get('/appointments/settled', [ArtisanController::class, 'show_settled']);

        Route::get('/appointments/{schedule}', [ArtisanController::class, 'open']);
        Route::post('/appointments/{schedule}/accept', [ArtisanController::class, 'accept']);
        Route::post('/appointments/{schedule}/decline', [ArtisanController::class, 'decline']);
    });

    // Route::post('/search', [ProviderController::class, 'search']);

    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/allservices', [ServiceController::class, 'index']);
        Route::post('', [ServiceController::class, 'store']);
        Route::get('/artisans/{meter?}', [ServiceController::class, 'artisans'])->whereNumber('meter');
        Route::post('/search', [ServiceController::class, 'search']);
        Route::get('/most-rated', [ServiceController::class, 'most_rated']);
        Route::get('{service}', [ServiceController::class, 'show']);
        Route::post('{service}/rate', [ServiceController::class, 'rate']);
        Route::post('{service}/schedule', [ServiceController::class, 'schedule']);
        Route::patch('{service}/update', [ServiceController::class, 'update']);
        Route::delete('{service}/remove', [ServiceController::class, 'destroy']);
        Route::post('{service}/attachments', [ServiceController::class, 'uploadAttachments']);
    });

    Route::prefix('providers')->name('providers.')->group(function () {
        Route::get('/most_rated', [ProviderController::class, 'most_rated']);
        Route::get('/details/{id}', [ProviderController::class, 'show']);
    });

    Route::prefix('courier')->name('courier.')->controller(CourierController::class)->group(function () {
        Route::post('create', 'create')->name('create');
        Route::post('search', 'search')->name('search');
    });

    Route::prefix('ride')->name('ride.')->controller(RideController::class)->group(function () {
        Route::post('create', 'store');
        Route::patch('update-rider-coordinate', 'updateRiderLocation');
        Route::get('ride-requests', 'getRideRequests');
        Route::patch('{ride}/rate-rider-with-review', 'rateRiderWithReview');
        Route::get('{ride}/rating', 'getRiderRatings');
        Route::patch('ride-request/{action}', 'rideRequestAction');
        Route::post('riders-around/{meter?}', [RideController::class, 'nearbyRiders'])->whereNumber('meter');
        // Route::post('search', 'search')->name('search');
    });
});
