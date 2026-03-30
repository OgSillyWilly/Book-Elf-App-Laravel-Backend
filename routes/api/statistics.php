<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StatisticsController;

/*
|--------------------------------------------------------------------------
| Statistics API Routes  
|--------------------------------------------------------------------------
|
| Routes for retrieving various reading statistics, including author
| progress, reading patterns, and overall metrics.
|
*/

Route::prefix('statistics')->group(function () {
    Route::get('overview', [StatisticsController::class, 'overview']);
    Route::get('author-progress', [StatisticsController::class, 'authorProgress']);
    Route::get('reading-patterns', [StatisticsController::class, 'readingPatterns']);
    Route::get('by-type', [StatisticsController::class, 'byType']);
    Route::get('top-rated', [StatisticsController::class, 'topRated']);
    Route::get('yearly-history', [StatisticsController::class, 'yearlyHistory']);
    Route::get('series-progress', [StatisticsController::class, 'seriesProgress']);
    Route::post('clear-cache', [StatisticsController::class, 'clearCache']);
});
