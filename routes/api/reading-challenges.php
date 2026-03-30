<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReadingChallengeController;

/*
|--------------------------------------------------------------------------
| Reading Challenge API Routes  
|--------------------------------------------------------------------------
|
| Routes for managing reading challenges, including progress tracking and
| book suggestions.
|
*/

Route::prefix('reading-challenges')->group(function () {
    // Custom routes
    Route::get('active', [ReadingChallengeController::class, 'active']);
    Route::get('{challenge}/suggestions', [ReadingChallengeController::class, 'suggestions']);
    
    // Standard CRUD operations
    Route::get('/', [ReadingChallengeController::class, 'index']);
    Route::post('/', [ReadingChallengeController::class, 'store']);
    Route::get('/{challenge}', [ReadingChallengeController::class, 'show']);
    Route::put('/{challenge}', [ReadingChallengeController::class, 'update']);
    Route::patch('/{challenge}', [ReadingChallengeController::class, 'update']);
    Route::delete('/{challenge}', [ReadingChallengeController::class, 'destroy']);
});
