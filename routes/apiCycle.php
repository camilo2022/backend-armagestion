<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
  Route::post('Cycles/Index', [\App\Http\Controllers\Api\V1\CycleController::class, 'index'])->middleware('can:Cycles.Index')->name('Cycles.Index');
  Route::post('Cycles/Store', [\App\Http\Controllers\Api\V1\CycleController::class, 'store'])->middleware('can:Cycles.Store')->name('Cycles.Store');
  Route::put('Cycles/Update/{id}', [\App\Http\Controllers\Api\V1\CycleController::class, 'update'])->middleware('can:Cycles.Update')->name('Cycles.Update');
  Route::delete('Cycles/Delete', [\App\Http\Controllers\Api\V1\CycleController::class, 'delete'])->middleware('can:Cycles.Delete')->name('Cycles.Delete');
});
