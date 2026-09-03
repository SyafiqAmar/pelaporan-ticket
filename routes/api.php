<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;
use Orion\Facades\Orion;

Route::post('/login', [AuthController::class, 'login']);

Orion::resource('faqs', FaqController::class)->withoutMiddleware(['auth:sanctum']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Orion::resource('tickets', TicketController::class);
});
