<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SupabaseController;
use App\Http\Controllers\Admin\UserController;

Route::post('/auth/login', [SupabaseController::class, 'login']);

Route::middleware(['auth:supabase', 'role:admin'])->group(function () {
    Route::prefix('/admin')->group(function () {
        Route::prefix('/users')->controller(UserController::class)->group(function () {
            Route::get('', 'index');
            Route::get('/search', 'search');
            Route::get('/{user}', 'show');
            Route::post('/{user}/activate', 'activate');
            Route::post('/{user}/deactivate', 'deactivate');
        });
    });
});

Route::middleware(['auth:supabase', 'role:user', 'user.active'])->group(function () {
    Route::prefix('/plans')->controller(BillingController::class)->group(function () {
        Route::get('', 'index');
        Route::get('subscribe/{plan}', 'subscribe');
        Route::get('switch/{plan}', 'switch');
        Route::get('cancel', 'cancel');
        Route::get('invoices', 'invoices');
        Route::get('upcoming-invoice', 'upcomingInvoice');
    });

    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::get('', 'show');
    });

    Route::prefix('projects')->controller(ProjectController::class)->group(function () {
        Route::get('', 'index');
        Route::post('', 'store');
        Route::get('generate-content/{project}', 'generateContent')->can('view', 'project');
        Route::get('{project}', 'show')->can('view', 'project');
        Route::post('{project}', 'update')->can('update', 'project');
        Route::delete('{project}', 'destroy')->can('delete', 'project');
    });
});
