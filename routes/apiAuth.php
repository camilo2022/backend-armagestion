<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//con el middleware le digo que solo puede tener 4 intentos en una hora probar la contraseña sin fallar
Route::post('login', [\App\Http\Controllers\Api\V1\Auth\LoginController::class, 'login'])->middleware('throttle:4,1');
Route::middleware(['auth:sanctum'])->group(function () {
    //elimina el token
    Route::post('logout', [\App\Http\Controllers\Api\V1\Auth\LogoutController::class, 'logout']);
});
