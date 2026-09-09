<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Vehicle Maintenance History API',
        'api' => url('/api'),
    ]);
});
