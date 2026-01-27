<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Frontend Pages and API Documentation
|--------------------------------------------------------------------------
|
| These routes serve the frontend HTML pages and API documentation.
| All API functionality is in routes/api.php
|
*/

// Frontend routes - serve static HTML files from public/frontend
Route::get('/frontend/{page}', function ($page) {
    $path = public_path("frontend/{$page}.html");
    if (file_exists($path)) {
        return response()->file($path);
    }
    abort(404);
})->where('page', '(index|login|register|dashboard|video)');

// API Documentation
Route::get('/', function () {
    return view('api');
});
