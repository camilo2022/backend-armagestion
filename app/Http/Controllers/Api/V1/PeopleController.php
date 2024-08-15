<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\People;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\People\PeopleIndexRequest;
use App\Http\Requests\Api\People\PeopleStoreRequest;
use App\Http\Requests\Api\People\PeopleDeleteRequest;
use App\Http\Requests\Api\People\PeopleUpdateRequest;
use App\Http\Resources\Api\People\PeopleIndexCollection;

class PeopleController extends Controller
{

    //trait para personalizar las respuestas

    use ApiResponser;

     /**
     * Listado de usuarios con filtros y paginación.
     *
     * Esta función devuelve un listado paginado de usuarios aplicando los siguientes filtros:
     *
     * - Filtro por rango de fechas de creación (start_date y end_date).
     * - Filtro por nombre de usuario (name).
     * - Filtro por apellido de usuario (last_name).
     * - Filtro por número de documento (document_type).
     *
     * Si los parámetros de filtro están presentes en la solicitud, se aplicarán a la consulta.
     *
     * @param \App\Http\Requests\PeopleIndexRequest $request La solicitud HTTP con los parámetros de filtro y paginación.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator Lista paginada de usuarios que cumplen con los filtros especificados.
     */

    public function index(PeopleIndexRequest $request)
    {
        try {
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $end_date = Carbon::parse($request->end_date)->endOfDay();

            //consultar por nombre
            $people = People::when($request->has('peop_name'), function ($query) use ($request) {
                return $query->where('peop_name', 'like', '%' . $request->peop_name . '%');
            })
            //Consulta por apellido
            ->when($request->has('peop_last_name'), function ($query) use ($request) {
                return $query->where('peop_last_name', 'like', '%' . $request->peop_last_name . '%');
            })
            //Consulta por numero de documento
            ->when($request->has('peop_dni'), function ($query) use ($request) {
                return $query->where('peop_dni', $request->peop_dni);
            })->whereBetween('created_at', [$start_date, $end_date])->paginate($request->perPage);

            return $this->successResponse(new PeopleIndexCollection($people), 'Consulta Exitosa', 200);
        } catch (\Exception $e) {
            return $this->errorResponse([ 'message' => 'Algo salió mal', 'error' => $e->getMessage()], 500);
        }

    }

    /**
     * The function stores a new person in the database and returns a success response or an error response.
     *
     * @param PeopleStoreRequest request The  parameter is an instance of the PeopleStoreRequest
     *
     * @return a success response with a message "Persona creada exitosamente" and a status code of 200
     *  the exception, along with a status code of 500.
     */


    public function store(PeopleStoreRequest $request)
    {

        try {
            $person = new People();
            $person->peop_name = $request->peop_name;
            $person->peop_last_name = $request->peop_last_name;
            $person->peop_dni = $request->peop_dni;
            $person->peop_status = $request->peop_status;
            $person->save();

             // Retornar una respuesta de éxito con un mensaje y código de respuesta
            return $this->successResponse(' ', 'Persona creada exitosamente', 201);
        } catch (\Exception $e) {
            return $this->errorResponse([ 'message' => 'Algo salió mal', 'error' => $e->getMessage()], 500);
        }

    }

    /**
     * Method update
     *
     * @param PeopleUpdateRequest
     * @param $id $id
     *
     * @return void
     */

    public function update(PeopleUpdateRequest $request, $id)
    {

        try {
            $person = People::findOrFail($id);

            $person->peop_name = $request->peop_name;
            $person->peop_last_name = $request->peop_last_name;
            $person->peop_dni = $request->peop_dni;
            $person->peop_status = $request->peop_status;
            $person->save();

            return $this->successResponse([], 'Registro actualizado exitosamente', 200);
        } catch (\Exception $e) {
            return $this->errorResponse(['message' => 'Algo salió mal', 'error' => $e->getMessage()], 500);
        }

    }


      /**
     * The function deletes a record from the "People" table in a database and returns a success or
     * error response.
     *
     * @param Request request
     *
     * @return a success response with an empty array and a message "Registro eliminado exitosamente"
     */

     public function delete(PeopleDeleteRequest $request){
        try {
            $person = People::findOrFail($request->id);
            $person->delete();
          //resive tres parametros data, mensaje y codigo de status
            return $this->successResponse('', 'Registro eliminado exitosamente', 204);
        } catch (\Exception $e) {
            return $this->errorResponse(['message' => 'Algo salió mal', 'error' => $e->getMessage()], 500);
        }
    }
   /*  public function destroy($id)
    {

    } */
}
