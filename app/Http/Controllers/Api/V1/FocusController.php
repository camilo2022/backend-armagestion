<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Focus\FocusDeleteRequest;
use App\Http\Requests\Api\Focus\FocusStoreRequest;
use App\Http\Requests\Api\Focus\FocusUpdateRequest;
use App\Http\Requests\Api\Focus\FocusIndexRequest;
use App\Http\Resources\Api\Focus\FocusIndexCollection;
use App\Models\Focus;
use App\Traits\ApiResponser;
use Carbon\Carbon;

class FocusController extends Controller
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
     * Listado de enfoques con filtros y paginación.
     *
     * Esta función devuelve un listado paginado de enfoques aplicando los siguientes filtros:
     *
     * - Filtro por nombre (search).
     * - Filtro por rango de fechas de enfoque (start_date y end_date).
     *
     * Si los parámetros de filtro están presentes en la solicitud, se aplicarán a la consulta.
     *
     * @param \App\Http\Requests\FocusIndexRequest $request La solicitud HTTP con los parámetros de filtro y paginación.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que contiene el listado paginado de enfoques que cumplen con los filtros especificados.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function index(FocusIndexRequest $request)
    {
        try {
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $end_date = Carbon::parse($request->end_date)->endOfDay();

            // Consultar los enfoques aplicando los filtros
            $focus = Focus::with('alli')
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
                ->orderBy('id', 'ASC')
                ->paginate($request->perPage);
            // Retornar una respuesta de éxito con el listado paginado de enfoques
            return $this->successResponse(
                new FocusIndexCollection($focus),
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
     * Almacenar un nuevo enfoque.
     *
     * Esta función crea un nuevo enfoque con los datos proporcionados en la solicitud.
     *
     * @param \App\Http\Requests\FocusStoreRequest $request La solicitud HTTP con los datos del enfoque a crear.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que contiene el enfoque recién creado con un mensaje y código de respuesta.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function store(FocusStoreRequest $request)
    {
        try {
            $focus = new Focus();

            $focus->focus_name = $request->focus_name;
            $focus->focus_description = $request->focus_description;
            $focus->alli_id = $request->alli_id;
            $focus->save();

            // Retornar una respuesta de éxito con el enfoque recién creado
            return $this->successResponse(
                $focus,
                'Registro creado exitosamente.',
                201
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
     * Actualizar un enfoque existente.
     *
     * Esta función actualiza un enfoque existente con los datos proporcionados en la solicitud.
     *
     * @param \App\Http\Requests\FocusUpdateRequest $request La solicitud HTTP con los datos actualizados del enfoque.
     * @param int $id El identificador único del enfoque que se va a actualizar.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que contiene el enfoque actualizado con un mensaje y código de respuesta.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function update(FocusUpdateRequest $request, $id)
    {
        try {
            $focus = Focus::findOrFail($id);

            $focus->focus_name = $request->focus_name;
            $focus->focus_description = $request->focus_description;
            $focus->alli_id = $request->alli_id;
            $focus->save();

            // Retornar una respuesta de éxito con el enfoque actualizado
            return $this->successResponse(
                $focus,
                'Registro actualizado exitosamente.',
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
     * Eliminar un enfoque existente.
     *
     * Esta función elimina un enfoque existente por su ID.
     *
     * @param \App\Http\Requests\FocusDeleteRequest $request La solicitud HTTP con el ID del enfoque a eliminar.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica si el enfoque se eliminó exitosamente con un mensaje y código de respuesta.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function delete(FocusDeleteRequest $request){
        try {
            $focus = Focus::findOrFail($request->id)->delete();

            // Retornar una respuesta de éxito
            return $this->successResponse(
                $focus,
                'Registro eliminado exitosamente.',
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
}
