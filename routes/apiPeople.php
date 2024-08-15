<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
  Route::post('People/Index', [\App\Http\Controllers\Api\V1\PeopleController::class, 'index']);
  Route::post('People/Store', [\App\Http\Controllers\Api\V1\PeopleController::class, 'store']);
  Route::put('People/Update/{id}', [\App\Http\Controllers\Api\V1\PeopleController::class, 'update']);
  Route::delete('People/Delete', [\App\Http\Controllers\Api\V1\PeopleController::class, 'delete']);
});
