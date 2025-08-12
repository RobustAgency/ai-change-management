<?php

use App\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Route;

Route::prefix('/plans')->controller(BillingController::class)->group(function () {
    Route::get('/subscribe/{plan}', 'subscribe')->name('plans.subscribe');
});

Route::get('/subscription-success', function () {
    return '<h1>Subscription Successful</h1>';
});

Route::get('/subscription-cancel', function () {
    return '<h1>Subscription Canceled</h1>';
});
