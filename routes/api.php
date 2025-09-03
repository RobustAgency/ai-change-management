<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SupabaseController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\PaymentMethodController;

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

Route::middleware(['auth:supabase', 'role:user', 'user.approved'])->group(function () {
    Route::prefix('/plans')->controller(BillingController::class)->group(function () {
        Route::get('', 'index');
        Route::get('subscribe/{plan}', 'subscribe');
        Route::get('cancel', 'cancel');
        Route::get('invoices', 'invoices');
        Route::get('upcoming-invoice', 'upcomingInvoice');
    });

    Route::prefix('payment-method')->controller(PaymentMethodController::class)->group(function () {
        Route::get('add', 'addPaymentMethod');
    });

    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::get('', 'show');
    });

    Route::prefix('projects')->controller(ProjectController::class)->group(function () {
        Route::get('', 'index');
        Route::post('', 'store');
        Route::get('{project}', 'show')->can('view', 'project');
        Route::post('{project}', 'update')->can('update', 'project');
        Route::delete('{project}', 'destroy')->can('delete', 'project');
        Route::get('dispatch-job/{project}', 'dispatchJob');
    });
});
