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
    // Book management routes
    require __DIR__ . '/api/books.php';
    
    // Media & utility routes
    require __DIR__ . '/api/media.php';
});
