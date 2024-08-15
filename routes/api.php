<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/* Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
}); */

Route::post('mejor_gestion/upload', [\App\Http\Controllers\Api\V1\CargaArchivos\MejorGestionController::class, 'upload'])->middleware('auth:sanctum');;
Route::get('processed_report/download', [\App\Http\Controllers\Api\V1\Reportes\ProcesoReporteController::class, 'downloadProcessedReport'])->middleware('auth:sanctum');;
Route::get('processed_report/acconts_mangnaments/download', [\App\Http\Controllers\Api\V1\Reportes\ReportePagoBloqueFijaController::class, 'downloadProcessedReport'])->middleware('auth:sanctum');;
