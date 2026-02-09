<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StripeWebhookController;

Route::get('/', function () {
    return 'Innovative AI Dialogues v1.0.0';
});

Route::post('/stripe/webhook', StripeWebhookController::class)->name('webhook.callback');
