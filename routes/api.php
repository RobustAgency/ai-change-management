<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProfileController;

Route::prefix('/plans')->controller(BillingController::class)->group(function () {
    Route::get('', 'index');
    Route::get('add-payment-method', 'addPaymentMethod');
    Route::get('subscribe/{plan}', 'subscribe');
    Route::get('cancel', 'cancel');
});

Route::prefix('profile')->controller(ProfileController::class)->group(function () {
    Route::get('', 'show');
});

Route::get('/subscription-success', function () {
    return '<h1>Subscription Successful</h1>';
});

Route::get('/subscription-cancel', function () {
    return '<h1>Subscription Canceled</h1>';
});
