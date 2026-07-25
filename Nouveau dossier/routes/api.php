<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PurchaseRequestController;
use Illuminate\Support\Facades\Route;

// PUBLIC ROUTES
Route::prefix('public')->group(function () {
    Route::get('/cars', [CarController::class, 'index']);
    Route::get('/cars/{car}', [CarController::class, 'show']);
    Route::get('/brands', [BrandController::class, 'index']);
});

// AUTH ROUTES
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'changePassword']);

    // ---- Client (normal user): browse, favorite, message, request to buy ----
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites/{carId}/toggle', [FavoriteController::class, 'toggle']);

    Route::get('/conversations', [MessageController::class, 'conversations']);
    Route::get('/conversations/{otherUserId}', [MessageController::class, 'thread']);
    Route::post('/messages', [MessageController::class, 'send']);

    Route::post('/cars/{car}/request', [PurchaseRequestController::class, 'store']); // "je veux acheter cette voiture"
    Route::get('/my-requests', [PurchaseRequestController::class, 'myRequests']);

    // =====================================================
    // ADMIN-ONLY ROUTES — single shop owner
    // =====================================================
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);

        // ---- Car inventory (ONLY the admin) ----
        Route::get('/cars', [CarController::class, 'adminIndex']);
        Route::post('/cars', [CarController::class, 'store']);
        Route::match(['put', 'patch'], '/cars/{car}', [CarController::class, 'update']);
        Route::delete('/cars/{car}', [CarController::class, 'destroy']);
        Route::post('/cars/{car}/images', [CarController::class, 'addImages']);
        Route::delete('/cars/{car}/images/{image}', [CarController::class, 'deleteImage']);
        Route::patch('/cars/{car}/sold', [CarController::class, 'markSold']);
        Route::patch('/cars/{car}/sell-unit', [CarController::class, 'sellUnit']);
        Route::patch('/cars/{car}/renew', [CarController::class, 'renew']);
        Route::patch('/cars/{car}/approve', [CarController::class, 'approve']);
        Route::patch('/cars/{car}/reject', [CarController::class, 'reject']);
        Route::patch('/cars/{car}/feature', [CarController::class, 'toggleFeatured']);

        // ---- Purchase requests ("demandes d'achat") ----
        Route::get('/requests', [PurchaseRequestController::class, 'adminIndex']);
        Route::patch('/requests/{purchaseRequest}/status', [PurchaseRequestController::class, 'updateStatus']);

        Route::get('/users', [AdminController::class, 'users']);
        Route::patch('/users/{user}/ban', [AdminController::class, 'toggleBan']);

        Route::get('/reports', [AdminController::class, 'reports']);
        Route::patch('/reports/{report}/resolve', [AdminController::class, 'resolveReport']);

        Route::post('/brands', [BrandController::class, 'store']);
        Route::put('/brands/{brand}', [BrandController::class, 'update']);
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);
    });
});