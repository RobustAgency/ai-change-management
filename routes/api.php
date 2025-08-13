<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;

Route::prefix('/plans')->controller(BillingController::class)->group(function () {
    Route::get('', 'index');
    Route::get('subscribe/{plan}', 'subscribe');
    Route::get('cancel', 'cancel');

});

Route::get('/subscription-success', function () {
    return '<h1>Subscription Successful</h1>';
});

Route::get('/subscription-cancel', function () {
    return '<h1>Subscription Canceled</h1>';
});
