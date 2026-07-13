<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OAuthController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\Admin\LeaveController as AdminLeaveController;

// Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// OAuth Routes
Route::get('/oauth/{provider}', [OAuthController::class, 'redirect']);
Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Employee Leave Routes
    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::post('/leaves', [LeaveController::class, 'store']);
    Route::get('/leaves/{leaf}', [LeaveController::class, 'show']);

    // Admin Leave Routes
    Route::middleware('isAdmin')->prefix('admin')->group(function () {
        Route::get('/leaves', [AdminLeaveController::class, 'index']);
        Route::patch('/leaves/{leaf}/status', [AdminLeaveController::class, 'update']);
    });
});
