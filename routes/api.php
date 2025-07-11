<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemeController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
    
    Route::prefix('memes')->group(function () {
        Route::get('/', [MemeController::class, 'index']);
        Route::post('/', [MemeController::class, 'store']);
        Route::get('/stats', [MemeController::class, 'stats']);
        Route::get('/{id}', [MemeController::class, 'show']);
        Route::put('/{id}', [MemeController::class, 'update']);
        Route::delete('/{id}', [MemeController::class, 'destroy']);
    });

    Route::post('/upload-image', [MemeController::class, 'uploadImage']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
