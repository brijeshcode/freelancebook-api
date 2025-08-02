<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| User Management Routes
|--------------------------------------------------------------------------
*/
 
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::name('users.')->prefix('users')->controller(UserController::class)->group(function () {
        
        // Standard CRUD operations
        Route::get('/', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::get('{user}', 'show')->name('show');
        Route::put('{user}', 'update')->name('update');
        Route::patch('{user}', 'update')->name('patch');
        Route::delete('{user}', 'destroy')->name('destroy');
        
        // Soft delete management routes
        Route::get('with-trashed/list', 'indexWithTrashed')->name('with-trashed');
        Route::get('only-trashed/list', 'onlyTrashed')->name('only-trashed');
        Route::patch('{id}/restore', 'restore')->name('restore')->whereNumber('id');
        Route::delete('{id}/force-destroy', 'forceDestroy')->name('force-destroy')->whereNumber('id');
        
        // Role management route
        Route::patch('{user}/change-role', 'changeRole')->name('change-role');
    });

    
    Route::name('clients.')->prefix('clients')->controller(ClientController::class)->group(function () {
        
        // Standard CRUD operations
        Route::get('/', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::get('{client}', 'show')->name('show');
        Route::put('{client}', 'update')->name('update');
        Route::patch('{client}', 'update')->name('patch');
        Route::delete('{client}', 'destroy')->name('destroy');
        
        // Soft delete management routes
        Route::get('with-trashed/list', 'indexWithTrashed')->name('with-trashed');
        Route::get('only-trashed/list', 'onlyTrashed')->name('only-trashed');
        Route::patch('{id}/restore', 'restore')->name('restore')->whereNumber('id');
        Route::delete('{id}/force-destroy', 'forceDestroy')->name('force-destroy')->whereNumber('id');
        
        // Client-specific routes
        Route::patch('{client}/change-status', 'changeStatus')->name('change-status');
    });

    Route::apiResource('projects', ProjectController::class);
    
    // Soft delete additional routes
    Route::get('projects-trashed', [ProjectController::class, 'trashed']);
    Route::patch('projects/{id}/restore', [ProjectController::class, 'restore']);
    Route::delete('projects/{id}/force-delete', [ProjectController::class, 'forceDelete']);
    

    Route::apiResource('services', ServiceController::class);
    
    // Additional service routes
    Route::prefix('services')->name('services.')->group(function () {
        
        // Restore soft deleted service
        Route::patch('{id}/restore', [ServiceController::class, 'restore'])
            ->name('restore');
        
        // Permanently delete service
        Route::delete('{id}/force-delete', [ServiceController::class, 'forceDelete'])
            ->name('force-delete');
        
        // Toggle service active status
        Route::patch('{service}/toggle-status', [ServiceController::class, 'toggleStatus'])
            ->name('toggle-status');
        
        // Get services ready for billing
        Route::get('ready-for-billing', [ServiceController::class, 'readyForBilling'])
            ->name('ready-for-billing');
        
        // Get recurring services
        Route::get('recurring', [ServiceController::class, 'recurring'])
            ->name('recurring');
    });

    Route::name('auth.')->controller(AuthController::class)->group(function () {

        Route::get('/user', 'user')->name('user');
        Route::post('/logout', 'logout')->name('logout');
        Route::post('/logout-all', 'logoutAll')->name('logout_all');
        Route::post('/refresh', 'refresh')->name('refresh');
        Route::get('/sessions', 'sessions')->name('sessions');
        Route::delete('/sessions/{tokenId}', 'revokeSession')->name('session_tokenId');
        Route::post('/change-password', 'changePassword')->name('change-password');
    });


    Route::apiResource('invoices', InvoiceController::class);
    Route::patch('invoices/{invoice}/mark-as-sent', [InvoiceController::class, 'markAsSent']);
    
    // Payment Management
    Route::apiResource('payments', PaymentController::class);
    Route::patch('payments/{payment}/verify', [PaymentController::class, 'verify']);

});