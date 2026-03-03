<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\Api\BookImportController;
use App\Http\Controllers\ImageProxyController;

Route::middleware('api')->group(function () {
    // Specific routes must come BEFORE apiResource
    Route::post('books/import', [BookImportController::class, 'import']);
    Route::get('books/template/download', [BookImportController::class, 'template']);
    Route::get('books/reading-history', [BookApiController::class, 'readingHistory']);
    
    Route::apiResource('books', BookApiController::class);
    Route::get('image-proxy', [ImageProxyController::class, 'proxy']);
});
