<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookApiController;
use App\Http\Controllers\ImageProxyController;

Route::middleware('api')->group(function () {
    Route::apiResource('books', BookApiController::class);
    Route::get('image-proxy', [ImageProxyController::class, 'proxy']);
});
