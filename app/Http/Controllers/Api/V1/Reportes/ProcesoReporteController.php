<?php

namespace App\Http\Controllers\Api\V1\Reportes;

use App\Http\Controllers\Controller;
use App\Models\BloqueoFija;
use App\Models\Configuracion;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Faker\Factory as Faker;
use Illuminate\Http\Response;

class ProcesoReporteController extends Controller
{
    public function downloadProcessedReport()
    {
        try{
            $value = 'BLOQ 1ER FACTURA';
            $getSettings = $this->getSettings($value);
            $getData = $this->getData($value, $getSettings[0]);
            $setParameters = $this->setParameters($getSettings[0], $getSettings[1], $getData[1], $getData[2]);
            $dataOrganization = $this->dataOrganization($getData[3], $setParameters[0], $setParameters[1], $getData[0]);
            $dataProcessed = $this->dataProcessed($dataOrganization, $setParameters[2], $setParameters[3]);
            $setEffectivePercentage = $this->setEffectivePercentage($getSettings[2], $dataProcessed);
            $setGestionPercentage = $this->setGestionPercentage($getSettings[3], $setEffectivePercentage);
            $convertDataToCsv = $this->convertDataToCsv($setGestionPercentage);

            return response()->file($convertDataToCsv)->deleteFileAfterSend();
        } catch (Exception $e) {
            // Devolver una respuesta de error en caso de excepción
            return response()->json([
                'message' => 'Error al procesar el reporte.',
                'error' =>  $e->getMessage()
            ], 500);
        }
    }

    private function getSettings($value)
    {
        $settings = Configuracion::where('foco_aliado', '=', $value)->first(); // consulta configuracion por foco
        $userSetting = $settings->total_usuarios; // cantidad total de usuarios
        $gestionesRange = json_decode($settings->total_gestiones); // max y min de gestiones
        $porcentajeAcuerdo = $settings->porcentaje_acuerdo; // porcentaje de acuerdos de pagos
        $porcentajeEfectividad = $settings->porcentaje_efectividad; // porcentaje de efectivos
        return [$userSetting, $gestionesRange, $porcentajeAcuerdo, $porcentajeEfectividad];
    }

    private function getData($value, $userSetting)
    {
        $data = BloqueoFija::where('foco_aliado', '=', $value)->get(); // gestiones
        $cantidadDatos = $data->count(); // cantidad de informacion
        $ejecutivos = $data->pluck('ejecutivo')->unique()->values(); // ejecutivos unicos
        while($ejecutivos->count() < $userSetting) {
            $ejecutivos[] = Faker::create()->name;
        }
        $cantidadEjecutivos = $ejecutivos->count(); // cantidad de ejecutivos unicos
        return [$data, $cantidadDatos, $cantidadEjecutivos, $ejecutivos];
    }

    private function setParameters($userSetting, $gestionesRange, $cantidadDatos, $cantidadEjecutivos)
    {
        $gestionesMin = $gestionesRange[0]; // gestiones minima por ejecutivo
        $gestionesMax = $gestionesRange[1]; // gestiones maxima por ejecutivo
        $gestionesMinTotal = $gestionesRange[0] * $userSetting; // gestiones minima total
        $gestionesMaxTotal = $gestionesRange[1] * $userSetting; // gestiones maxima total
        $promedioActual = ceil($cantidadDatos / $cantidadEjecutivos); // promedio actual de datos por ejecutivo
        $promedioEsperado = ceil(($userSetting * ($gestionesMin + random_int(0, ($gestionesMax - $gestionesMin)))) / $cantidadEjecutivos); // promedio esperado de datos por ejecutivo
        return[$promedioActual, $promedioEsperado, $gestionesMax, $gestionesMin, $gestionesMinTotal, $gestionesMaxTotal];
    }

    private function dataOrganization($ejecutivos, $promedioActual, $promedioEsperado, $data)
    {
        $newData = [];
        foreach ($ejecutivos as $ejecutivo) {
            $porEjecutivo = $promedioActual < $promedioEsperado ? $promedioActual : $promedioEsperado ; // comparo promedios y asigno
            // comparo la cantidad de datos actua con el promedio y asigno un ramdom
            $datosEjecutivo = $porEjecutivo < $data->count() ? $data->random($porEjecutivo) : $data->random($data->count());
            $data = $data->diff($datosEjecutivo);
            $datosEjecutivo = $datosEjecutivo->map(function($item) use ($ejecutivo) {
                $item['ejecutivo'] = $ejecutivo;
                return $item;
            });
            $newData[] = [
              'ejecutivo' => $ejecutivo,
              'data' => $datosEjecutivo->collect()
            ];
        }
        return $newData;
    }

    private function dataProcessed($newData, $gestionesMax, $gestionesMin)
    {
        $data = [];
        foreach ($newData as $newD) {
            if($newD['data']->count() < $gestionesMin){
                while($gestionesMin  + random_int(0, ($gestionesMax - $gestionesMin)) > $newD['data']->count()){
                    $datosAleatorios = collect($newD['data'])->random(1);
                    $newD['data'] = $newD['data']->merge($datosAleatorios);
                }
            }
            $data = array_merge($data, $newD['data']->toArray());
        }
        shuffle($data);
        return $data;
    }

    private function setEffectivePercentage($porcentajeAcuerdo, $dataProcessed)
    {
        $cantidadTotal = collect($dataProcessed)->count();
        $cantidadEfectivasTrue = collect($dataProcessed)->where('efectiva', 'true')->count();
        $cantidadEfectivasFalse = ceil(($cantidadTotal * $porcentajeAcuerdo / 100) - $cantidadEfectivasTrue);
        $efectivasFalse = collect($dataProcessed)->where('efectiva', 'false');
        if($cantidadEfectivasFalse > 0){
            for ($i=0; $i<$cantidadEfectivasFalse ; $i++) {
                $elemento = $efectivasFalse->random(1)->first();
                $key = array_search($elemento, $dataProcessed);
                $dataProcessed[$key]['efectiva'] = 'true';
            }
        }
        return $dataProcessed;
    }

    private function setGestionPercentage($porcentajeEfectividad, $dataProcessed)
    {
        $cantidadEfectivas = collect($dataProcessed)->where('efectiva', 'true')->count();
        $cantidadGestion = collect($dataProcessed)->where('efectiva', 'true')->where('gestion', 'promesa de pago')->count();
        $cantidadGestionPP = ceil(round($cantidadEfectivas * $porcentajeEfectividad / 100) - $cantidadGestion);
        if($cantidadGestionPP > 0){
            while (collect($dataProcessed)->where('efectiva', 'true')->where('gestion', 'promesa de pago')->count() < round($cantidadEfectivas * $porcentajeEfectividad / 100)){
                $elemento = collect($dataProcessed)->where('efectiva', 'true')->whereNotIn('gestion', ['promesa de pago'])->random(1)->first();
                $key = array_search($elemento, $dataProcessed);
                $dataProcessed[$key]['gestion'] = 'promesa de pago';
            }
        }
        return $dataProcessed;
    }

    private function convertDataToCsv($dataProcessed)
    {
        $nombreAleatorio = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 20);
        $rutaArchivo = 'ReporteGestionProcesado/' . $nombreAleatorio . '.csv';
        Storage::put($rutaArchivo, 'Contenido CSV');
        $rutaArchivo = Storage::path($rutaArchivo);
        $file = fopen($rutaArchivo,'w');
        fputcsv($file, array_keys(reset($dataProcessed)), ';');
        foreach ($dataProcessed as $row) {
            fputcsv($file, $row, ';');
        }
        fclose($file);
        return $rutaArchivo;
    }
}
