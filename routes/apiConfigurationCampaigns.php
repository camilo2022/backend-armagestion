<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('ConfigurationCampaigns/Index', [\App\Http\Controllers\Api\V1\ConfigurationController::class, 'index'])->middleware('can:ConfigurationCampaigns.Index')->name('ConfigurationCampaigns.Index');
    Route::post('ConfigurationCampaigns/Store', [\App\Http\Controllers\Api\V1\ConfigurationController::class, 'store'])->middleware('can:ConfigurationCampaigns.Store')->name('ConfigurationCampaigns.Store');
    Route::put('ConfigurationCampaigns/Update/{id}', [\App\Http\Controllers\Api\V1\ConfigurationController::class, 'update'])->middleware('can:ConfigurationCampaigns.Update')->name('ConfigurationCampaigns.Update');
    Route::delete('ConfigurationCampaigns/Delete', [\App\Http\Controllers\Api\V1\ConfigurationController::class, 'delete'])->middleware('can:ConfigurationCampaigns.Delete')->name('ConfigurationCampaigns.Delete');
});
