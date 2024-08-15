<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Configuration;
use App\Models\UsersHasModels;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Exception;

class UsersHasConfigurationController extends Controller
{
    use ApiResponser;
    private $success = 'Consulta Exitosa.';
    private $error = 'Algo salió mal.';

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
                $UsersHasModels->model_id = $request->configuration_id;
                $UsersHasModels->model_type = Configuration::class;
                $UsersHasModels->campaign_id = $request->campaign_id;
                $UsersHasModels->save();
            }

            return $this->successResponse(
                '',
                'Usuarios asignados a la configuracion de campaña exitosamente.',
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
            UsersHasModels::withTrashed()
            ->whereHasMorph('model', [Configuration::class], function ($query) use ($request) {
                $query->where('model_id', $request->id);
            })->update(['campaign_id' => $request->campaign_id]);

            // Eliminar usuarios que ya no están asignados con soft deletes
            UsersHasModels::whereNotIn('user_id', $request->users_assigned)
                ->where('campaign_id', $request->campaign_id)
                ->whereHasMorph('model', [Configuration::class], function ($query) use ($request) {
                    $query->where('model_id', $request->id);
                })->delete();

            // Obtener los usuarios asignados activos
            $assigneds = UsersHasModels::select('user_id')
                ->whereHasMorph('model', [Configuration::class], function ($query) use ($request) {
                    $query->where('model_id', $request->id);
                })
                ->pluck('user_id')
                ->toArray();

            // Agregar nuevos usuarios
            $users = array_diff($request->users_assigned, $assigneds);
            foreach ($users as $user) {
                $UsersHasModel = UsersHasModels::where('user_id', $user)
                    ->where('campaign_id', $request->campaign_id)
                    ->whereHasMorph('model', [Configuration::class], function ($query) use ($request) {
                        $query->where('model_id', $request->id);
                    })->onlyTrashed()
                    ->first();
                if ($UsersHasModel) {
                    $UsersHasModel->restore();
                }else {
                    $UsersHasModel = new UsersHasModels();
                    $UsersHasModel->user_id = $user;
                    $UsersHasModel->model_type = Configuration::class;
                    $UsersHasModel->model_id = $request->id;
                    $UsersHasModel->campaign_id = $request->campaign_id;
                }
                $UsersHasModel->save();
            }

            return $this->successResponse(
                '',
                'Usuarios (des)asignados a la configuracion de campaña exitosamente.',
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
