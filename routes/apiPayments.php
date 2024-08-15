<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('Payments/Index', [\App\Http\Controllers\Api\V1\PaymentsController::class, 'index'])->middleware('can:Payments.Index')->name('Payments.Index');
    Route::post('Payments/Upload', [\App\Http\Controllers\Api\V1\PaymentsController::class, 'upload'])->middleware('can:Payments.Upload')->name('Payments.Upload');
    Route::post('Payments/Store', [\App\Http\Controllers\Api\V1\PaymentsController::class, 'store'])->middleware('can:Payments.Store')->name('Payments.Store');
    Route::put('Payments/Update/{id}', [\App\Http\Controllers\Api\V1\PaymentsController::class, 'update'])->middleware('can:Payments.Update')->name('Payments.Update');
    Route::delete('Payments/Delete', [\App\Http\Controllers\Api\V1\PaymentsController::class, 'delete'])->middleware('can:Payments.Delete')->name('Payments.Delete');

});
