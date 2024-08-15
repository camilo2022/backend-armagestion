<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Upload\UploadPaymentsController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Payments\PaymentsDeleteRequest;
use App\Http\Requests\Api\Payments\PaymentsIndexRequest;
use App\Http\Requests\Api\Payments\PaymentsStoreRequest;
use App\Http\Requests\Api\Payments\PaymentsUpdateRequest;
use App\Http\Requests\Api\Payments\PaymentsUploadRequest;
use App\Http\Requests\Api\UploadPayments\UploadPaymentsStoreRequest;
use App\Http\Resources\Api\Payments\PaymentsIndexCollection;
use App\Imports\ImportFilePayments;
use App\Models\Payments;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Readers\LaravelExcelReader;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class PaymentsController extends Controller
{
    use ApiResponser;

    private $success = 'Consulta Exitosa.';

    private $error = 'Algo salió mal.';

    protected $UploadPaymentsController;

    /**
     * Constructor del controlador de pagos.
     *
     * Este constructor inicializa el controlador de carga de pagos y configura límites de ejecución y
     * memoria para las operaciones del controlador.
     *
     * @param \App\Http\Controllers\UploadPaymentsController $UploadPaymentsController El controlador de
     * carga de pagos utilizado por esta instancia.
     */
    public function __construct(UploadPaymentsController $UploadPaymentsController)
    {
        $this->UploadPaymentsController = $UploadPaymentsController;

        $this->middleware(function ($request, $next) {

            // Configurar límite de tiempo de ejecución a 3600 segundos (1 hora)
            ini_set('max_execution_time', 7200);

            // Configurar límite de memoria a 4096 megabytes (4 gigabytes)
            ini_set('memory_limit', '4096M');
            return $next($request);
        });
    }

    /**
     * Listado de pagos con filtros y paginación.
     *
     * Esta función devuelve un listado paginado de pagos aplicando los siguientes filtros:
     *
     * - Filtro por rango de fechas (start_date y end_date).
     * - Filtro por nombre o criterios de búsqueda (search).
     *
     * Si los parámetros de filtro están presentes en la solicitud, se aplicarán a la consulta.
     *
     * @param \App\Http\Requests\PaymentsIndexRequest $request La solicitud HTTP con los
     * parámetros de filtro y paginación.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * Una lista paginada de pagos que cumplen con los filtros especificados.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function index(PaymentsIndexRequest $request)
    {
        
        try {
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $end_date = Carbon::parse($request->end_date)->endOfDay();

            // Consultar pagos con relaciones y aplicar filtros
           /*  $payment = Payments::with(['campaign' => function ($query) {
                $query->where('user_id', Auth::user()->id);
            }, 'campaign.user', 'focus', 'assignment'])
                ->when($request->filled('search'),
                    function ($query) use ($request) {
                        $query->search($request->search);
                    }
                )
                ->when($request->filled('start_date') && $request->filled('end_date'),
                    function ($query) use ($start_date, $end_date) {
                        $query->filterByDate($start_date, $end_date);
                    }
                )
                ->paginate($request->perPage); */

                return $payment = Payments::where('model_id','=', 1841)->paginate($request->perPage);

            // Devolver una respuesta exitosa con los pagos paginados
            return $this->successResponse(
                new PaymentsIndexCollection($payment),
                $this->success,
                200
            );
        } catch (Exception $e) {
            // Devolver una respuesta de error en caso de excepción
            return $this->errorResponse(
                [
                    'message' => $this->error,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }

    /**
     * Crear un nuevo registro de pago.
     *
     * Esta función crea un nuevo registro de pago con los datos proporcionados en la solicitud.
     *
     * @param \App\Http\Requests\PaymentsStoreRequest $request La solicitud HTTP con los datos del pago a crear.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que el registro de pago se ha creado exitosamente y un mensaje de éxito.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function store(PaymentsStoreRequest $request)
    {
        try {
            $payment = new Payments();

            $payment->pay_account = $request->pay_account;
            $payment->pay_value = $request->pay_value;
            $payment->pay_date = Carbon::parse($request->pay_date)->format('Y-m-d');
            $payment->cycle = $request->cycle;
            $payment->campaign_id = $request->campaign_id;
            $payment->focus_id = $request->focus_id;
            $payment->assi_id = $request->assi_id;
            $payment->save();

            // Retornar una respuesta de éxito con el pago creado y un mensaje de éxito
            return $this->successResponse(
                $payment,
                'Registro creado exitosamente.',
                201
            );
        } catch (Exception $e) {
            // Devolver una respuesta de error en caso de excepción
            return $this->errorResponse(
                [
                    'message' => $this->error,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }

    /**
     * Cargar masivamente pagos desde un archivo Excel.
     *
     * Esta función carga masivamente pagos desde un archivo Excel proporcionado en la solicitud.
     *
     * @param \App\Http\Requests\PaymentsUploadRequest $request La solicitud HTTP con el archivo de
     * pagos y otros datos necesarios.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que el masivo de pagos se ha cargado exitosamente y un mensaje de éxito.
     *
     * @throws \Illuminate\Validation\ValidationException
     * Devuelve una respuesta de error en caso de fallos de validación.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function upload(PaymentsUploadRequest $request)
    {
        
     try{

        // Verificar si se ha subido un archivo
                 if ($request->hasFile('pays_file')) {
                     // Obtener el archivo subido
                     $archivoOriginal = $request->file('pays_file');

                     // Leer todos los datos del archivo Excel en una colección
                     $pagosCollection = Excel::toCollection(new ImportFilePayments, $archivoOriginal)->first();

                     // Dividir los datos en lotes de 1000 registros cada uno
                     $lotes = $pagosCollection->chunk(1000);
                     // Procesar cada lote por separado
                     foreach ($lotes as $indice => $lote) {
                         // Crear un nuevo archivo Excel para el lote actual
                         $nombreArchivo = "lote_" . ($indice + 1) . ".xlsx";

                         // Guardar el lote actual en el nuevo archivo Excel
                         Excel::store(collect($lote), $nombreArchivo);

                         // Crear una nueva instancia de UploadPaymentsStoreRequest para procesar los datos del lote
                         $newRequest = new UploadPaymentsStoreRequest();
                         $newRequest->merge([
                             'archivo' => $nombreArchivo, // Pasar el nombre del archivo como parte de la solicitud
                             'payments' => $lote->toArray() // Pasar los datos del lote como parte de la solicitud
                         ]);
                     
                         // Validar los datos del lote
                         $validator = Validator::make(
                             $newRequest->all(),
                             $newRequest->rules(),
                             $newRequest->messages(),
                             $newRequest->attributes()
                         );
                     
                         if ($validator->fails()) {
                             // Captura la excepción de validación y retorna una respuesta de error
                             throw new ValidationException($validator);
                         }
                     
                         // Llamar a la función 'store' del controlador de carga de pagos para guardar los pagos
                         $this->UploadPaymentsController->store($newRequest);
                     }
                 
     
                     return $this->successResponse(
                         'Masivo de pagos cargado exitosamente.',
                         201
                     );
                 

                 } else {
                     return "No se ha subido ningún archivo.";
                 }
             
                     // Llamar a la función 'store' del controlador de carga de pagos para guardar los pagos
             
                 } catch (ValidationException $e) {
                     // Maneja los errores de validación del nuevo formulario y retorna una respuesta de error
                     return $this->errorResponse(
                         [
                             'message' => 'Error de validación',
                             'errors' => $e->errors(),
                         ],
                         422
                     );
                 } catch (Exception $e) {
                     // Devuelve una respuesta de error en caso de excepción
                     return $this->errorResponse(
                         [
                             'message' => $this->error,
                             'error' => $e->getMessage()
                         ],
                         500
                     );
                 
                 
                 }  

       

    }

    /**
     * Actualizar un registro de pago existente.
     *
     * Esta función actualiza un registro de pago existente con los datos proporcionados en la solicitud.
     *
     * @param \App\Http\Requests\PaymentsUpdateRequest $request La solicitud HTTP con los datos del pago a actualizar.
     * @param int $id El identificador del pago a actualizar.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que el registro de pago se ha actualizado exitosamente y un mensaje de éxito.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function update(PaymentsUpdateRequest $request, $id)
    {
        try {
            $payment = Payments::findOrFail($id);

            $payment->pay_account = $request->pay_account;
            $payment->pay_value = $request->pay_value;
            $payment->pay_date = Carbon::parse($request->pay_date)->format('Y-m-d');
            $payment->cycle = $request->cycle;
            $payment->campaign_id = $request->campaign_id;
            $payment->focus_id = $request->focus_id;
            $payment->assi_id = $request->assi_id;
            $payment->save();

            // Retornar una respuesta de éxito con el pago actualizado y un mensaje de éxito
            return $this->successResponse(
                $payment,
                'Registro actualizado exitosamente.',
                200
            );
        } catch (Exception $e) {
            // Devolver una respuesta de error en caso de excepción
            return $this->errorResponse(
                [
                    'message' => $this->error,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }


    public function delete(PaymentsDeleteRequest $request)
    {
        try {
            $payment = Payments::findOrFail($request->id)->delete();

            return $this->successResponse(
                $payment,
                'Registro eliminado exitosamente.',
                200
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                [
                    'message' => $this->error,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }

    /**
     * Cargar masivamente pagos utilizando un script Python.
     *
     * Esta función permite cargar masivamente pagos utilizando un script Python que verifica el archivo proporcionado.
     *
     * @param \App\Http\Requests\PaymentsUploadRequest $request La solicitud HTTP con el archivo de pagos a cargar.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que el masivo de pagos se ha cargado exitosamente y un mensaje de éxito.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function uploadPython(PaymentsUploadRequest $request)
    {
        try{
            // Obtener el archivo cargado
            $file = $request->file('pays_file');

            // Guardar el archivo en una ubicación específica y obtener su nombre
            $nameFile = $file->store('PaymentsFile');

            // Crear un archivo de script shell y escribir en él la ejecución del script Python
            $fopen = fopen(`executeUploadPayments.sh`, `w`);
            fwrite($fopen , `python3 /var/www/html/Armagestion/public/uploadPayments.py $nameFile`);
            fclose($fopen);

            shell_exec(`python3 uploadPaymentsService.py executeUploadPayments.sh`);

            return $this->successResponse(
                '',
                'Masivo de pagos cargado exitosamente.',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                [
                    'message' => $this->error,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }
}
