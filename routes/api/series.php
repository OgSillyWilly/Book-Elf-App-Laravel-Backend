<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SeriesController;

/*
|--------------------------------------------------------------------------
| Series API Routes  
|--------------------------------------------------------------------------
|
| Routes for managing book series, including CRUD operations and progress
| tracking.
|
*/

Route::prefix('series')->group(function () {
    // Standard CRUD operations
    Route::get('/', [SeriesController::class, 'index']);
    Route::post('/', [SeriesController::class, 'store']);
    Route::get('/{series}', [SeriesController::class, 'show']);
    Route::put('/{series}', [SeriesController::class, 'update']);
    Route::patch('/{series}', [SeriesController::class, 'update']);
    Route::delete('/{series}', [SeriesController::class, 'destroy']);
});
