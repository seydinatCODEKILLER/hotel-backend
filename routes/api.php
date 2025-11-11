<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;
use Illuminate\Support\Facades\Route;

// Routes d'authentification
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Routes protégées
Route::middleware('auth:sanctum')->group(function () {
    // Routes utilisateur
    Route::post('/auth/update-avatar', [AuthController::class, 'updateAvatar']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);

    // Routes statistiques
    Route::get('/hotels/statistiques', [HotelController::class, 'statistiques']);
    Route::get('/hotels/statistiques/graphiques', [HotelController::class, 'statistiquesGraphiques']);
    
    // Routes hotels
    Route::apiResource('hotels', HotelController::class);
    Route::patch('/hotels/{id}/restore', [HotelController::class, 'restore']);
    Route::post('/hotels/{id}/update-photo', [HotelController::class, 'updatePhoto']);
    
    // Route pour récupérer les enums
    Route::get('/enums', [HotelController::class, 'getEnums']);
});

Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route not found.'
    ], 404);
});