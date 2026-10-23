<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Split into modular files for better organization and performance.
| Each feature has its own route file in the routes/api/ directory.
|
*/

Route::middleware('api')->group(function () {
    // Authentication routes
    require __DIR__ . '/api/auth.php';
    
    // Everything below is per-user data and requires a bearer token
    Route::middleware('auth:sanctum')->group(function () {
        require __DIR__ . '/api/books.php';
        require __DIR__ . '/api/series.php';
        require __DIR__ . '/api/reading-challenges.php';
        require __DIR__ . '/api/statistics.php';
        require __DIR__ . '/api/media.php';
    });
});
