<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeWebhookController;

Route::get('/', function () {
    return 'MVP skeleton';
});

Route::post('/stripe/webhook', StripeWebhookController::class)->name('webhook.callback');
