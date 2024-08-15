<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
  Route::post('RolesAndPermissions/Index', [\App\Http\Controllers\Api\V1\RolesAndPermissionsController::class, 'index'])->middleware('can:RolesAndPermissions.Index')->name('RolesAndPermissions.Index');
  Route::post('RolesAndPermissions/Store', [\App\Http\Controllers\Api\V1\RolesAndPermissionsController::class, 'store'])->middleware('can:RolesAndPermissions.Store')->name('RolesAndPermissions.Store');
  Route::put('RolesAndPermissions/Update/{id}', [\App\Http\Controllers\Api\V1\RolesAndPermissionsController::class, 'update'])->middleware('can:RolesAndPermissions.Update')->name('RolesAndPermissions.Update');
  Route::delete('RolesAndPermissions/Delete', [\App\Http\Controllers\Api\V1\RolesAndPermissionsController::class, 'delete'])->middleware('can:RolesAndPermissions.Delete')->name('RolesAndPermissions.Delete');
});
