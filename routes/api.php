<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\SupabaseController;
use App\Http\Controllers\Admin\UserController;

Route::post('/auth/login', [SupabaseController::class, 'login']);

Route::middleware(['auth:supabase', 'role:admin'])->group(function () {
    Route::prefix('/admin')->group(function () {
        Route::prefix('/users')->controller(UserController::class)->group(function () {
            Route::get('', 'index');
            Route::get('/search', 'search');
            Route::get('/{user}', 'show');
            Route::post('/{user}/approve', 'approve');
            Route::post('/{user}/revoke-approval', 'revokeApproval');
        });
    });
});

// Route::middleware(['auth:supabase', 'role:user', 'user.approved'])->group(function () {
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
// });
