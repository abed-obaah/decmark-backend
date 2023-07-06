<?php

use App\Http\Controllers\ExternalController;
use Illuminate\Support\Facades\Route;

Route::post('webhook/{driver}', [ExternalController::class, 'webhook']);