<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageProxyController;

/*
|--------------------------------------------------------------------------
| Media API Routes
|--------------------------------------------------------------------------
|
| Routes for handling media-related operations such as image proxying,
| file uploads, and other media services.
|
*/

Route::get('image-proxy', [ImageProxyController::class, 'proxy']);
