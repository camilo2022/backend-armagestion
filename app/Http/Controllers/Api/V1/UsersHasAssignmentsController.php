<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadUsersHasAssignments\UploadUsersHasAssignmentsStoreRequest;
use App\Imports\ImportFileUsersHasAssignments;
use App\Models\Assignment;
use App\Models\UsersHasModels;
use App\Traits\ApiResponser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class UsersHasAssignmentsController extends Controller

{
    use ApiResponser;
    private $success = 'Consulta Exitosa.';
    private $error = 'Algo salió mal.';

    /**
     * Obtener una lista de elementos según la solicitud.
     *
     * Esta función devuelve una respuesta con los datos proporcionados en la solicitud,
     * indicando que los datos se han obtenido exitosamente.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos a devolver como respuesta.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que contiene los datos de la solicitud y un mensaje de éxito.
     *
     * @throws Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function index(Request $request)
    {
        try {
            return $this->successResponse(
                $request,
                $this->success,
                201
            );
        } catch(Exception $e) {
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
     * Asignar usuarios a una configuración de campaña.
     *
     * Esta función asigna usuarios a una configuración de campaña específica según los datos
     * proporcionados en la solicitud.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos de asignación de usuarios.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que los usuarios se han asignado a la configuración de
     * campaña exitosamente y un mensaje de éxito.
     *
     * @throws Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function store(Request $request)
    {
        try {
            foreach ($request->users_assigned as $user_assigned) {
                $UsersHasModels = new UsersHasModels();

                $UsersHasModels->user_id = $user_assigned;
                $UsersHasModels->model_id = $request->assignment_id;
                $UsersHasModels->model_type = Assignment::class;
                $UsersHasModels->save();
            }

            return $this->successResponse(
                '',
                'Usuarios asignados a la asignacion exitosamente.',
                201
            );
        } catch(Exception $e) {
            return $this->errorResponse(
                [
                    'message' => $this->error,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }

    public function upload(Request $request)
    {
        try {
            // Leer los pagos desde el archivo Excel
            $usersHasAssignments = Excel::toCollection(new ImportFileUsersHasAssignments, $request->file('users_assig_file'))->first();

            // Crear una nueva solicitud para almacenar los datos del masivo de pagos
            $newRequest = new UploadUsersHasAssignmentsStoreRequest();
            $newRequest->merge(
                [
                    'users_assigs' => $usersHasAssignments->toArray()
                ]
            );

            // Validar los datos del masivo de pagos
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

            $usersAssignments = $usersHasAssignments->groupBy('assi_id')->map(function ($items, $assi_id) {
                return [
                    'assi_id' => $assi_id,
                    'user_ids' => $items->pluck('user_id')->all()
                ];
            });

            foreach ($usersAssignments as $usersAssignment) {
                // Eliminar usuarios que ya no están asignados
                UsersHasModels::whereNotIn('user_id', $usersAssignment->user_ids)
                ->whereHasMorph('model', [Assignment::class], function ($query, $usersAssignment) {
                    $query->where('model_id', $usersAssignment->assi_id);
                })
                ->delete();

                // Restaurar usuarios eliminados
                UsersHasModels::whereIn('user_id', $usersAssignment->user_ids)
                ->whereHasMorph('model', [Assignment::class], function ($query, $usersAssignment) {
                    $query->where('model_id', $usersAssignment->assi_id);
                })
                ->onlyTrashed()
                ->restore();

                // Obtener los usuarios asignados activos
                $assigneds = UsersHasModels::select('user_id')
                ->whereHasMorph('model', [Assignment::class], function ($query, $usersAssignment) {
                    $query->where('model_id', $usersAssignment->id);
                })
                ->pluck('user_id')
                ->toArray();

                // Agregar nuevos usuarios
                $users = array_diff($usersAssignment->user_ids, $assigneds);

                foreach ($users as $user) {
                    $UsersHasModels = new UsersHasModels();
                    $UsersHasModels->user_id = $user;
                    $UsersHasModels->model_id = $usersAssignment->assi_id;
                    $UsersHasModels->model_type = Assignment::class;
                    $UsersHasModels->save();
                }
            }

            return $this->successResponse(
                '',
                'Usuarios asignados a la asignacion exitosamente.',
                201
            );
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
     * Actualizar la asignación de usuarios a una configuración de campaña.
     *
     * Esta función actualiza la asignación de usuarios a una configuración de campaña específica según
     * los datos proporcionados en la solicitud.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los datos de asignación de usuarios.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que la asignación de usuarios se ha actualizado exitosamente y un mensaje de éxito.
     *
     * @throws Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function update(Request $request)
    {
        try {
            // Eliminar usuarios que ya no están asignados
            UsersHasModels::whereNotIn('user_id', $request->users_assigned)
            ->whereHasMorph('model', [Assignment::class], function ($query, $request) {
                $query->where('model_id', $request->id);
            })
            ->delete();

            // Restaurar usuarios eliminados
            UsersHasModels::whereIn('user_id', $request->users_assigned)
            ->whereHasMorph('model', [Assignment::class], function ($query, $request) {
                $query->where('model_id', $request->id);
            })
            ->onlyTrashed()
            ->restore();

            // Obtener los usuarios asignados activos
            $assigneds = UsersHasModels::select('user_id')
            ->whereHasMorph('model', [Assignment::class], function ($query, $request) {
                $query->where('model_id', $request->id);
            })
            ->pluck('user_id')
            ->toArray();

            // Agregar nuevos usuarios
            $users = array_diff($request->users_assigned, $assigneds);
            foreach ($users as $user) {
                $UsersHasModels = new UsersHasModels();
                $UsersHasModels->user_id = $user;
                $UsersHasModels->model_id = $request->id;
                $UsersHasModels->model_type = Assignment::class;
                $UsersHasModels->save();
            }

            return $this->successResponse(
                '',
                'Usuarios (des)asignados a la asignacion exitosamente.',
                201
            );
        } catch(Exception $e) {
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
     * Eliminar registros de usuarios con configuraciones de campaña asociadas.
     *
     * Esta función elimina los registros de usuarios que están asociados a una configuración de campaña específica,
     * identificada por su ID proporcionado en la solicitud.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con el ID de la configuración de campaña
     * para la cual se deben eliminar los registros de usuarios.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que los registros de usuarios se han eliminado exitosamente y un mensaje de éxito.
     *
     * @throws Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function delete(Request $request)
    {
        try {
            $usersHasConfCamp = UsersHasModels::where('configuration_id', '=', $request->id)->delete();

            return $this->successResponse(
                $usersHasConfCamp,
                'Registros eliminados exitosamente.',
                201
            );
        } catch(Exception $e) {
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
