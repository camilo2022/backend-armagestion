<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
   Route::post('RoleAndPermission', [\App\Http\Controllers\Api\V1\UserController::class, 'get']);
   Route::post('Users/Index', [\App\Http\Controllers\Api\V1\UserController::class, 'index'])->middleware('can:Users.Index')->name('Users.Index');
   Route::post('Users/Inactives', [\App\Http\Controllers\Api\V1\UserController::class, 'inactives'])->middleware('can:Users.Inactives')->name('Users.Inactives');
   Route::post('Users/Store', [\App\Http\Controllers\Api\V1\UserController::class, 'store'])->middleware('can:Users.Store')->name('Users.Store');
   Route::put('Users/Update/{id}', [\App\Http\Controllers\Api\V1\UserController::class, 'update'])->middleware('can:Users.Update')->name('Users.Update');
   Route::delete('Users/Delete', [\App\Http\Controllers\Api\V1\UserController::class, 'delete'])->middleware('can:Users.Delete')->name('Users.Delete');
   Route::put('Users/Restore', [\App\Http\Controllers\Api\V1\UserController::class, 'restore'])->middleware('can:Users.Restore')->name('Users.Restore');
   Route::post('Users/AssignRolePermissions',  [\App\Http\Controllers\Api\V1\UserController::class, 'assignRoleAndPermissions'])->middleware('can:Users.AssignRolePermissions')->name('Users.AssignRolePermissions');
   Route::post('Users/RemoveRolePermissions',  [\App\Http\Controllers\Api\V1\UserController::class, 'removeRoleAndPermissions'])->middleware('can:Users.RemoveRolePermissions')->name('Users.RemoveRolePermissions');
   Route::post('RoleAndPermission ',  [\App\Http\Controllers\Api\V1\UserController::class, 'get']);
});
