<?php

use App\Http\Controllers\AskController;
use App\Http\Controllers\AssignController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MattermostWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::post('/ask', AskController::class);
Route::post('/assign', AssignController::class);
Route::post('/webhooks/mattermost', MattermostWebhookController::class);
