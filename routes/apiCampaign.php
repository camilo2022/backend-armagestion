<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('Campaigns/Index', [\App\Http\Controllers\Api\V1\CampaignController::class, 'index'])->middleware('can:Campaigns.Index')->name('Campaigns.Index');
    Route::post('Campaigns/Store', [\App\Http\Controllers\Api\V1\CampaignController::class, 'store'])->middleware('can:Campaigns.Store')->name('Campaigns.Store');
    Route::put('Campaigns/Update/{id}', [\App\Http\Controllers\Api\V1\CampaignController::class, 'update'])->middleware('can:Campaigns.Update')->name('Campaigns.Update');
    Route::delete('Campaigns/Delete', [\App\Http\Controllers\Api\V1\CampaignController::class, 'delete'])->middleware('can:Campaigns.Delete')->name('Campaigns.Delete');

});
