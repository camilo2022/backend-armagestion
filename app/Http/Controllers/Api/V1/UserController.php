<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\User;
use App\Traits\ApiResponser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\AssignRoleAndPermissionsRequest;
use App\Http\Requests\Api\User\UserIndexRequest;
use App\Http\Resources\Api\User\UserIndexCollection;
use App\Http\Requests\Api\User\RemoveRolesAndPermissionsRequest;
use App\Http\Requests\Api\User\UserDeleteRequest;
use App\Http\Requests\Api\User\UserInactivesRequest;
use App\Http\Requests\Api\User\UserRestoreRequest;
use App\Http\Requests\Api\User\UserStoreRequest;
use App\Http\Requests\Api\User\UserUpdateRequest;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use ApiResponser;
    private $success = 'Consulta Exitosa.';
    private $error = 'Algo salió mal.';

    public function index(UserIndexRequest $request)
    {
        try {
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $end_date = Carbon::parse($request->end_date)->endOfDay();

            //Consulta por nombre
            $users = User::with('roles', 'permissions', 'configurations.campaign.campaign.ally', 'assignments')
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
                ->when($request->filled('campaign_id') && $request->filled('configuration_id'), function ($query) use ($request) {
                    $query->whereDoesntHave('usersHasModels', function ($subQuery) use ($request) {
                        $subQuery->where('campaign_id', $request->campaign_id)
                        ->where('model_id','!=', $request->configuration_id);
                    });
                })
                ->when($request->filled('role'),
                    function ($query) use ($request) {
                        $query->filterByRole($request);
                    }
                )
                ->paginate($request->perPage);

            return $this->successResponse(
                new UserIndexCollection($users),
                $this->success,
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
     * Listado de usuarios inactivos con filtros y paginación.
     *
     * Esta función devuelve un listado paginado de usuarios inactivos aplicando los siguientes filtros:
     *
     * - Filtro por rango de fechas de creación (start_date y end_date).
     * - Filtro por nombre de usuario, apellido, número de documento o correo electrónico (search).
     *
     * Si los parámetros de filtro están presentes en la solicitud, se aplicarán a la consulta de usuarios inactivos.
     *
     * @param \App\Http\Requests\UserInactivesRequest $request La solicitud HTTP con los parámetros
     * de filtro y paginación.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * Lista paginada de usuarios inactivos que cumplen con los filtros especificados.
     */
    public function inactives(UserInactivesRequest $request)
    {
        try {
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $end_date = Carbon::parse($request->end_date)->endOfDay();

            //Consulta por nombre
            $users = User::with('roles','permissions')
            //Consulta por nombre, apellido, cedula o correo
            ->when($request->filled('search'),
                function ($query) use ($request) {
                    $query->search($request->search);
                }
            )
            ->when($request->filled('start_date') && $request->filled('end_date'),
                function ($query) use ($start_date, $end_date) {
                    $query->whereBetween('created_at', [$start_date, $end_date]);
                }
            )
            //Trae los registros 'eliminados'
            ->onlyTrashed()
            ->paginate($request->perPage);

            return $this->successResponse(
                new UserIndexCollection($users),
                $this->success,
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
     * Crear un nuevo usuario.
     *
     * Esta función crea un nuevo usuario con los datos proporcionados en la solicitud.
     *
     * @param \App\Http\Requests\UserStoreRequest $request La solicitud HTTP con los datos del nuevo usuario.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que incluye el usuario creado y un mensaje de éxito.
     *
     * @throws Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function store(UserStoreRequest $request)
    {
        try {
            $user = new User();

            $user->name = $request->name;
            $user->last_name = $request->last_name;
            $user->document_number = $request->document_number;
            $user->phone_number = $request->phone_number;
            $user->address = $request->address;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            // Crear un token de acceso para el usuario utilizando Sanctum
            //$token = $user->createToken('auth_token')->plainTextToken;

            // Devolver una respuesta con el token de acceso y el usuario creado
            //return response()->json([ 'user' => $user, 'token' => $token, 'token_type' => 'Bearer', ], 201);
            return $this->successResponse(
                $user,
                'Usuario creado exitosamente.',
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
     * Actualizar un usuario existente.
     *
     * Esta función actualiza los datos de un usuario existente con los datos proporcionados en la solicitud.
     *
     * @param \App\Http\Requests\UserUpdateRequest $request La solicitud HTTP con los datos de
     * actualización del usuario.
     * @param int $id El ID del usuario que se va a actualizar.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que incluye el usuario actualizado y un mensaje de éxito.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * Devuelve una respuesta de error si el usuario no se encuentra.
     * @throws Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function update(UserUpdateRequest $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $user->name = $request->name;
            $user->last_name = $request->last_name;
            $user->document_number = $request->document_number;
            $user->phone_number = $request->phone_number;
            $user->address = $request->address;
            $user->email = $request->email;
            $user->save();

            return $this->successResponse(
                $user,
                'Registro actualizado exitosamente',
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
     * Eliminar un usuario existente por su ID.
     *
     * Esta función elimina un usuario existente según el ID proporcionado en la solicitud.
     *
     * @param \App\Http\Requests\UserDeleteRequest $request La solicitud HTTP con el ID del usuario a eliminar.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que el usuario se ha eliminado exitosamente (sin datos) y un mensaje de éxito.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * Devuelve una respuesta de error si el usuario no se encuentra.
     * @throws Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function delete(UserDeleteRequest $request)
    {
        try {
            $user = User::findOrFail($request->id)->delete();

            return $this->successResponse(
                $user,
                'Registro eliminado exitosamente',
                204
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
     * Restaurar un usuario inactivo por su ID.
     *
     * Esta función restaura un usuario inactivo según el ID proporcionado en la solicitud.
     *
     * @param \App\Http\Requests\UserRestoreRequest $request La solicitud HTTP con el ID del usuario a restaurar.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que el usuario se ha restaurado exitosamente y un mensaje de éxito.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * Devuelve una respuesta de error si el usuario no se encuentra.
     * @throws Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function restore(UserRestoreRequest $request)
    {
        try {
            $user = User::withTrashed()->findOrFail($request->id)->restore();

            return $this->successResponse(
                $user,
                'Registro restaurado exitosamente',
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
     * Asignar un rol y permisos a un usuario existente.
     *
     * Esta función asigna un rol y permisos a un usuario existente según los datos proporcionados en la solicitud.
     *
     * @param \App\Http\Requests\AssignRoleAndPermissionsRequest $request La solicitud HTTP con los datos de
     * asignación de rol y permisos.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que el rol y los permisos se han asignado exitosamente al usuario y un
     * mensaje de éxito.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * Devuelve una respuesta de error si el usuario o el rol no se encuentran.
     * @throws \Illuminate\Database\QueryException
     * Devuelve una respuesta de error si ocurre una excepción de consulta de base de datos.
     * @throws Exception
     * Devuelve una respuesta de error en caso de otras excepciones.
     */
    public function assignRoleAndPermissions(AssignRoleAndPermissionsRequest $request)
    {
        try {
            // Obtener el rol existente
            $role = Role::where('name',  $request->role)->first();
            $user = User::findOrFail($request->id);

            if (!$role) {
                return $this->successResponse(
                    $user,
                    'El rol especificado no existe',
                    404
                );
            }

            // Verificar si el usuario ya tiene el rol
            if ($user->hasRole($role->name)) {
                return $this->successResponse(
                    $user,
                    'El usuario ya tiene el rol asignado',
                    400
                );
            }
             // Asignar el rol al usuario
            $user->syncRoles([$role]);
            // Asociar los permisos existentes del rol al usuario
            $user->syncPermissions($request->permissions);

            return $this->successResponse(
                $user,
                'Rol y permisos asignados al usuario exitosamente.',
                200
            );
        } catch (QueryException $e) {
            // Manejar la excepción de la base de datos
            return $this->errorResponse(
                [
                    'message' => $this->error,
                    'error' => $e->getMessage()
                ],
                500
            );
        } catch (Exception $e) {
            // Manejar otras excepciones
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
     * Remover todos los roles y permisos de un usuario.
     *
     * Esta función remueve todos los roles y permisos asociados a un usuario según el ID proporcionado en la solicitud.
     *
     * @param \App\Http\Requests\RemoveRolesAndPermissionsRequest $request La solicitud HTTP con el ID del
     * usuario cuyos roles y permisos se deben remover.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que los roles y permisos se han removido exitosamente del
     * usuario y un mensaje de éxito.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * Devuelve una respuesta de error si el usuario no se encuentra.
     * @throws Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function removeRoleAndPermissions(RemoveRolesAndPermissionsRequest $request)
    {
        try {
            $user = User::findOrFail($request->id);
            // Remover todos los roles del usuario
            $user->roles()->detach();
            // Remover todos los permisos del usuario
            $user->permissions()->detach();

            return $this->successResponse(
                $user,
                'Rol y permisos removidos al usuario exitosamente.',
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

    public function get(Request $request)
    {
        $user = User::with('roles.permissions')->findOrFail($request->user_id);
        return $this->successResponse(
            $user,
            'Rol y permisos encontrado.',
            200
        );
    }
}
