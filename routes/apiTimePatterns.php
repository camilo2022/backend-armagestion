<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('TimePattern/Index', [\App\Http\Controllers\Api\V1\TimePatternController::class, 'index'])->name('Patterns.Index');
    Route::put('TimePattern/Update/{id}', [\App\Http\Controllers\Api\V1\TimePatternController::class, 'update'])->name('Patterns.Update');
    Route::put('TimePattern/Default/{id}', [\App\Http\Controllers\Api\V1\TimePatternController::class, 'DefaultTimePattern'])->name('Patterns.Default');
});
