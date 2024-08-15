<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cycle\CycleDeleteRequest;
use App\Http\Requests\Api\Cycle\CycleStoreRequest;
use App\Http\Requests\Api\Cycle\CycleUpdateRequest;
use App\Http\Resources\Api\Cycle\CycleIndexCollection;
use App\Models\Cycle;
use Carbon\Carbon;
use App\Traits\ApiResponser;
use App\Http\Requests\Api\Cycle\CycleIndexRequest;

class CycleController extends Controller
{
    /**
     * Importar el trait ApiResponser para usar sus métodos de respuesta.
     *
     * El trait ApiResponser proporciona métodos útiles para formatear y enviar respuestas
     * HTTP desde los controladores.
     * Al importar este trait, los controladores pueden acceder a estos métodos para enviar
     * respuestas de manera uniforme.
     */
    use ApiResponser;

    /**
     * Mensaje de éxito predeterminado para respuestas exitosas.
     *
     * Este mensaje se utiliza para indicar el éxito en las respuestas de la API cuando una operación
     * se realiza con éxito.
     *
     * @var string
     */
    private $success = 'Consulta Exitosa.';

    /**
     * Mensaje de error genérico para respuestas de error.
     *
     * Este mensaje se utiliza como respuesta genérica en caso de que ocurra un error no específico en la API.
     *
     * @var string
     */
    private $error = 'Algo salió mal.';

    /**
     * Obtener una lista paginada de ciclos.
     *
     * Esta función devuelve una lista paginada de ciclos que cumplen con los criterios
     * de búsqueda y fecha especificados.
     *
     * @param \App\Http\Requests\CycleIndexRequest $request La solicitud HTTP con los
     * parámetros de búsqueda y paginación.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que contiene la lista paginada de ciclos y un código de respuesta.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function index(CycleIndexRequest $request)
    {
        try {
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $end_date = Carbon::parse($request->end_date)->endOfDay();

            //consultar por nombre
            $cycle = Cycle::when($request->filled('search'),
                    function ($query) use ($request) {
                        $query->search($request->search);
                    }
                )
                ->when($request->filled('start_date') && $request->filled('end_date'),
                    function ($query) use ($start_date, $end_date) {
                        $query->filterByDate($start_date, $end_date);
                    }
                )
                ->paginate($request->perPage);

            // Retornar una respuesta de éxito
            return $this->successResponse(
                new CycleIndexCollection($cycle),
                $this->success,
                200
            );
        } catch (\Exception $e) {
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
     * Almacena un nuevo ciclo en la base de datos.
     *
     * @param  CycleStoreRequest  $request  La solicitud que contiene los datos del ciclo a crear.
     * @return \Illuminate\Http\JsonResponse  Una respuesta JSON que incluye el ciclo creado o un mensaje de error.
     */
    public function store(CycleStoreRequest $request)
    {
        try {
            $cycle = new Cycle();

            $cycle->cycle_name = $request->cycle_name;
            $cycle->cycle_start_date = Carbon::parse($request->cycle_start_date)->format('Y-m-d');
            $cycle->cycle_end_date = Carbon::parse($request->cycle_end_date)->format('Y-m-d');
            $cycle->save();

            // Retornar una respuesta de éxito con el ciclo recién creado y un mensaje de éxito
            return $this->successResponse(
                $cycle,
                'Registro creado exitosamente.',
                201
            );
        } catch (\Exception $e) {
            // En caso de excepción, manejarla y retornar una respuesta de error con un mensaje de error
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
     * Actualiza un ciclo existente en la base de datos.
     *
     * @param  CycleUpdateRequest  $request  La solicitud que contiene los datos actualizados del ciclo.
     * @param  int  $id  El ID del ciclo que se va a actualizar.
     * @return \Illuminate\Http\JsonResponse  Una respuesta JSON que incluye el ciclo actualizado o un mensaje de error.
     */
    public function update(CycleUpdateRequest $request, $id)
    {
        try {
            $cycle = Cycle::findOrFail($id);

            $cycle->cycle_name = $request->cycle_name;
            $cycle->cycle_start_date = Carbon::parse($request->cycle_start_date)->format('Y-m-d');
            $cycle->cycle_end_date = Carbon::parse($request->cycle_end_date)->format('Y-m-d');
            $cycle->save();

            // Retornar una respuesta de éxito con el ciclo actualizado y un mensaje de éxito
            return $this->successResponse(
                $cycle,
                'Registro actualizado exitosamente.',
                200
            );
        } catch (\Exception $e) {
            // En caso de excepción, manejarla y retornar una respuesta de error con un mensaje de error
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
     * Elimina un ciclo existente de la base de datos.
     *
     * @param  CycleDeleteRequest  $request  La solicitud que contiene el ID del ciclo que se va a eliminar.
     * @return \Illuminate\Http\JsonResponse  Una respuesta JSON que indica si el ciclo se eliminó
     * correctamente o muestra un mensaje de error.
     */
    public function delete(CycleDeleteRequest $request){
        try {
            $cycle = Cycle::findOrFail($request->id)->delete();

            // Retornar una respuesta de éxito
            return $this->successResponse(
                $cycle,
                'Registro eliminado exitosamente.',
                200
            );
        } catch (\Exception $e) {
            // En caso de excepción, manejarla y retornar una respuesta de error con un mensaje de error
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
