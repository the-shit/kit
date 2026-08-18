<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/', HealthController::class);
Route::get('/health', HealthController::class);
