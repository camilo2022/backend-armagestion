<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
  Route::post('Assignments/Index', [\App\Http\Controllers\Api\V1\AssignmentController::class, 'index'])->middleware('can:Assignments.Index')->name('Assignments.Index');
});
