<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\Api\BookImportController;

/*
|--------------------------------------------------------------------------
| Book API Routes  
|--------------------------------------------------------------------------
|
| All routes related to book management, including CRUD operations,
| imports, and reading history.
|
*/

Route::prefix('books')->group(function () {
    // Custom routes - must be registered before resource routes
    Route::post('import', [BookImportController::class, 'import']);
    Route::get('template/download', [BookImportController::class, 'template']);
    Route::get('reading-history', [BookApiController::class, 'readingHistory']);
    
    // Standard CRUD operations
    Route::get('/', [BookApiController::class, 'index']);
    Route::post('/', [BookApiController::class, 'store']);
    Route::get('/{book}', [BookApiController::class, 'show']);
    Route::put('/{book}', [BookApiController::class, 'update']);
    Route::patch('/{book}', [BookApiController::class, 'update']);
    Route::delete('/{book}', [BookApiController::class, 'destroy']);
});
