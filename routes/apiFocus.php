<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
  Route::post('Focus/Index', [\App\Http\Controllers\Api\V1\FocusController::class, 'index'])->middleware('can:Focus.Index')->name('Focus.Index');
  Route::post('Focus/Store', [\App\Http\Controllers\Api\V1\FocusController::class, 'store'])->middleware('can:Focus.Store')->name('Focus.Store');
  Route::put('Focus/Update/{id}', [\App\Http\Controllers\Api\V1\FocusController::class, 'update'])->middleware('can:Focus.Update')->name('Focus.Update');
  Route::delete('Focus/Delete', [\App\Http\Controllers\Api\V1\FocusController::class, 'delete'])->middleware('can:Focus.Delete')->name('Focus.Delete');
});
