<?php

use App\Http\Controllers\Api\V1\SensorColmenaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/colmenas', [SensorColmenaController::class, 'index']);
    Route::get('/colmenas/{id}', [SensorColmenaController::class, 'show']);
    Route::post('/colmenas', [SensorColmenaController::class, 'store']);
    Route::put('/colmenas/{id}', [SensorColmenaController::class, 'update']);
    Route::delete('/colmenas/{id}', [SensorColmenaController::class, 'destroy']);
});