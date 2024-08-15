<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
  Route::post('Managements/Download', [\App\Http\Controllers\Api\V1\ManagementController::class, 'download'])->middleware('can:Managements.Download')->name('Managements.Download');
});
