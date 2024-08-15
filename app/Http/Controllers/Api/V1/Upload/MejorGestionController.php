<?php

namespace App\Http\Controllers\Api\V1\CargaArchivos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Files\ExcelImportRequest;
use Exception;

class MejorGestionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            ini_set('max_execution_time', 3600);
            ini_set('memory_limit', '4096M');
            return $next($request);
        });
    }

    public function upload(ExcelImportRequest $request)
    {
        try{

            // Obtenemos el archivo y lo convertimos a un array de objetos donde las keys van a ser los titulo de cada columna del archivo
            //$arrayData = Excel::toArray(new ImportExcel, $request->file('file'))[0];

            // $arrayData = $request->validatedData();

           //

            $archivo = $request->file('file');
            $name_file = $archivo->store('mejor_gestion');

            $fpo = fopen( 'procesa.sh' , 'w' );
            fwrite( $fpo , 'python3 /var/www/html/Armagestion/public/verifile.py '.$name_file );
            fclose( $fpo );

            shell_exec( 'python3 probaser.py procesa.sh' );

           return response()->json([
               'name_file' => $name_file,
               'success' => 'Datos subidos con exito',
           ], 200);

        } catch (Exception $e) {
            // Devolver una respuesta de error en caso de excepción
            return response()->json([
                'message' => 'Error al cargar el archivo.',
                'error' =>  $e->getMessage()
            ], 500);
        }
    }
}
