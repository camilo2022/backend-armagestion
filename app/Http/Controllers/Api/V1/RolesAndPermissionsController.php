<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\Api\RolesAndPermissions\RolesAndPermissionsCollection;
use App\Http\Requests\Api\RolesAndPermissions\RolesAndPermissionsIndexRequest;
use App\Http\Requests\Api\RolesAndPermissions\RolesAndPermissionsStoreRequest;
use App\Http\Requests\Api\RolesAndPermissions\RolesAndPermissionsDeleteRequest;
use App\Http\Requests\Api\RolesAndPermissions\RolesAndPermissionsUpdateRequest;
use Exception;

class RolesAndPermissionsController extends Controller
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
     * Listado de roles y permisos con filtros y paginación.
     *
     * Esta función devuelve un listado paginado de roles con sus permisos aplicando los siguientes filtros:
     *
     * - Filtro por rango de fechas de creación (start_date y end_date).
     * - Filtro por nombre de rol (search).
     *
     * Si los parámetros de filtro están presentes en la solicitud, se aplicarán a la consulta.
     *
     * @param \App\Http\Requests\RolesAndPermissionsIndexRequest $request La solicitud HTTP con los
     * parámetros de filtro y paginación.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     * Una lista paginada de roles y sus permisos que cumplen con los filtros especificados.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function index(RolesAndPermissionsIndexRequest $request)
    {
        try{
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $end_date = Carbon::parse($request->end_date)->endOfDay();

            // Consultar roles con relaciones y aplicar filtros
            $rolesAndPermissions = Role::with('permissions')
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
                ->paginate($request->perPage);

            // Devolver una respuesta exitosa con los roles y permisos paginados
            return $this->successResponse(
                new RolesAndPermissionsCollection($rolesAndPermissions),
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
     * Crear un nuevo rol con permisos.
     *
     * Esta función crea un nuevo rol con los permisos proporcionados en la solicitud.
     *
     * @param \App\Http\Requests\RolesAndPermissionsStoreRequest $request La solicitud HTTP con los datos del nuevo rol y sus permisos.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que el rol y los permisos se han creado correctamente y un mensaje de éxito.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function store(RolesAndPermissionsStoreRequest $request)
    {
        try {
            DB::beginTransaction();

             // Crear el rol con el nombre proporcionado en la solicitud
            $role = Role::create(
                [
                    'name' => $request->roles
                ]
            );

            // Asignar permisos al rol
            $permissions = collect($request->permissions)->map(function ($permissions) {
                // Crear o recuperar un permiso con el nombre proporcionado
                return Permission::firstOrCreate(
                    [
                        'name' => $permissions
                    ]
                );
            });

            // Sincronizar los permisos con el rol
            $role->syncPermissions($permissions);

            // Confirmar la transacción de base de datos
            DB::commit();

            // Devolver una respuesta exitosa con el rol y los permisos creados
            return $this->successResponse(
                $role,
                'Roles y permisos creados correctamente',
                201
            );
        } catch (Exception $e) {
            // Deshacer la transacción en caso de excepción y devolver una respuesta de error
            DB::rollback();
            return $this->errorResponse(
                [
                    'message' => $this->error,
                    'error' => $e->getMessage()
                ],
                500
            );
        }
    }


    public function update(RolesAndPermissionsUpdateRequest $request, $roleId)
    {
        try {
            DB::beginTransaction();

            // Encontrar el rol
            $role = Role::findOrFail($roleId);

            $currentPermissions = collect($request->permissions);

            // Obtener los permisos actuales del rol
            $existingPermissions = $role->permissions->pluck('name');

            // Detectar nuevos permisos agregados
            $newPermissions = $currentPermissions->diff($existingPermissions);

            // Crear o recuperar nuevos permisos y agregarlos al arreglo de permisos actuales
            foreach ($newPermissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $currentPermissions->push($permission->name); // Agregar el nuevo permiso al arreglo
            }

            // Utilizar syncPermissions() para sincronizar todos los permisos al rol
            $role->syncPermissions($currentPermissions);

            // Detectar permisos eliminados
            $removedPermissions = $existingPermissions->diff($currentPermissions);

            // Revocar permisos del rol y eliminarlos si no están asignados a ningún rol
            foreach ($removedPermissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();

                if ($permission) {
                    $role->revokePermissionTo($permission);

                    // Verificar si el permiso ya no está asignado a ningún rol
                    if ($permission->roles->isEmpty()) {
                        $permission->delete();
                    }
                }
            }

            // Utilizar syncPermissions() para sincronizar todos los permisos al rol
            $role->syncPermissions($currentPermissions);

            // Confirmar la transacción de base de datos
            DB::commit();

            // Devolver una respuesta exitosa con el rol y los permisos actualizados
            return $this->successResponse(
                $role,
                'Rol y permisos actualizados correctamente',
                200
            );
        } catch (Exception $e) {
            // Deshacer la transacción en caso de excepción y devolver una respuesta de error
            DB::rollback();
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
     * Eliminar roles y permisos.
     *
     * Esta función elimina los roles y permisos especificados en la solicitud.
     *
     * @param \App\Http\Requests\RolesAndPermissionsDeleteRequest $request La solicitud HTTP con los IDs
     * de los roles y permisos a eliminar.
     *
     * @return \Illuminate\Http\JsonResponse
     * Una respuesta JSON que indica que los roles y permisos se han eliminado correctamente y un mensaje de éxito.
     *
     * @throws \Exception
     * Devuelve una respuesta de error en caso de excepción.
     */
    public function delete(RolesAndPermissionsDeleteRequest $request)
    {
        try {
            // Iniciar una transacción de base de datos
            DB::beginTransaction();

            // Eliminar roles y permisos
            Role::whereIn('id', $request->role_id)->delete();
            Permission::whereIn('id', $request->permission_id)->delete();

            // Confirmar la transacción de base de datos
            DB::commit();

            // Devolver una respuesta exitosa
            return $this->successResponse(
                '',
                'Roles y permisos eliminados correctamente',
                200
            );
        } catch (Exception $e) {
            // Deshacer la transacción en caso de excepción y devolver una respuesta de error
            DB::rollback();
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
