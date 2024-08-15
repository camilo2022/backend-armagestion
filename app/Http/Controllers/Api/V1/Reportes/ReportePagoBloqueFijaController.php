<?php

namespace App\Http\Controllers\Api\V1\Reportes;

use App\Http\Controllers\Controller;
use App\Models\PagoBloqueoFija;
use App\Models\Payments;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportePagoBloqueFijaController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            ini_set('max_execution_time', 3600);
            ini_set('memory_limit', '8192M');
            return $next($request);
        });
    }

    public function downloadProcessedReport()
    {
        try{
            $getDataPagos = $this->getDataPagos();
            $getDataAccounts = $this->getDataAccounts();
            $searchAccounts = $this->searchAccounts($getDataPagos, $getDataAccounts);
            $convertDataToCsv = $this->convertDataToCsv($searchAccounts);
            return response()->file($convertDataToCsv)->deleteFileAfterSend();
        } catch (Exception $e) {
            // Devolver una respuesta de error en caso de excepción
            return response()->json([
                'message' => 'Error al procesar el reporte.',
                'error' =>  $e->getMessage()
            ], 500);
        }
    }

    private function getDataPagos()
    {
        return Payments::all();
    }

    private function getDataAccounts()
    {
        return PagoBloqueoFija::all();
    }

    private function searchAccounts($getDataPagos, $getDataAccounts)
    {
        foreach ($getDataAccounts as $account) {
            $account->pago = $getDataPagos->contains('account', $account->cuenta_fs) ? "true" : "false";
        }
        return $getDataAccounts;
    }

    private function convertDataToCsv($getDataAccounts)
    {
        $nombreAleatorio = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 20);
        $rutaArchivo = 'ReporteGestionProcesado/' . $nombreAleatorio . '.csv';
        Storage::put($rutaArchivo, 'Contenido CSV');
        $rutaArchivo = Storage::path($rutaArchivo);
        $file = fopen($rutaArchivo,'w');
        fputcsv($file, array_keys($getDataAccounts->first()->toArray()), ';');
        foreach ($getDataAccounts as $row) {
            // fputcsv($file, $row->toArray(), ';');
            $csvLine = str_replace(["\r", "\n", "\t"], "", implode(';', $row->toArray()));
            fwrite($file, $csvLine . "\n");
        }
        fclose($file);
        return $rutaArchivo;
    }
}
