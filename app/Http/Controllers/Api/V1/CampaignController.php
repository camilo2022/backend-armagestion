<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Campaign\CampaignDeleteRequest;
use App\Traits\ApiResponser;
use App\Models\Campaign;
use App\Http\Resources\Api\Campaign\CampaignIndexCollection;
use App\Http\Requests\Api\Campaign\CampaignIndexRequest;
use App\Http\Requests\Api\Campaign\CampaignStoreRequest;
use App\Http\Requests\Api\Campaign\CampaignUpdateRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
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
     * The index function in this PHP code retrieves campaigns based on search criteria such as name or
     * description, as well as a specified date range.
     *
     * @param CampaignIndexRequest request The  parameter is an instance of the
     * CampaignIndexRequest class. It is used to retrieve the search query, start date, end date, and
     * pagination information from the HTTP request.
     *
     * @return a response with a success message, a status code of 200, and a collection of campaigns
     * that match the search criteria.
     */

    public function index(CampaignIndexRequest $request)
    {
        try {
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $end_date = Carbon::parse($request->end_date)->endOfDay();
            
            //consultar por nombre o descripcion de la campaña
            $campaign = Campaign::with('user')
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

            return $this->successResponse(
                new CampaignIndexCollection($campaign),
                $this->success,
                200
            );

            
        } catch (\Exception $e) {
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
     * The above function is used to store a new campaign in a database and return a success response
     * with a message and status code, or an error response with a message and status code if something
     * goes wrong.
     *
     * @param CampaignStoreRequest request The  parameter is an instance of the
     * CampaignStoreRequest class. It is used to retrieve the input data from the HTTP request made to
     * the store method. This class typically extends the base Laravel Request class and may contain
     * validation rules and custom logic for handling the request data.
     *
     * @return a success response with the created campaign, a success message, and a status code of
     * 200 if the campaign is successfully stored. If an exception occurs, it returns an error response
     * with a message indicating that something went wrong and the error message from the exception,
     * along with a status code of 500.
     */
    public function store(CampaignStoreRequest $request)
    {
        
        try {
            $campaign = new Campaign();
            /* $campaign->csts_id = $request->csts_id; */
            $campaign->camp_name = $request->camp_name;
            $campaign->camp_description = $request->camp_description;
            $campaign->user_id = $request->user_id;
            $campaign->camp_status = $request->camp_status;
            $campaign->save();

            // Retornar una respuesta de éxito con un mensaje y código de respuesta
            return $this->successResponse(
                $campaign,
                'Registro creado exitosamente.',
                200
            );
        } catch (\Exception $e) {
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
     * The above function updates a campaign record in the database with the provided request data.
     *
     * @param CampaignUpdateRequest request The  parameter is an instance of the
     * CampaignUpdateRequest class. It is used to retrieve the data sent in the HTTP request to update
     * the campaign.
     * @param id The "id" parameter is the identifier of the campaign that needs to be updated. It is
     * used to find the specific campaign record in the database.
     *
     * @return a success response with the updated campaign data and a message indicating that the
     * record was successfully updated. The HTTP status code returned is 200.
     */
    public function update(Request $request, $id)
    {
        return $request;
        try {
 
            $campaign = Campaign::findOrFail($id);
            
            /* $campaign->csts_id = $request->csts_id; */
            $campaign->camp_name = $request->camp_name;
            $campaign->camp_description = $request->camp_description;
            $campaign->user_id = $request->user_id;
            $campaign->camp_status = $request->camp_status;

            $campaign->save();

            return $this->successResponse(
                $campaign,
                'Registro actualizado exitosamente.',
                200
            );
        } catch (\Exception $e) {
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
     * The above function deletes a campaign record and returns a success response if successful, or an
     * error response if an exception occurs.
     *
     * @param CampaignDeleteRequest request The parameter `` is an instance of the
     * `CampaignDeleteRequest` class. It is used to validate and retrieve the data sent in the request
     * to delete a campaign.
     *
     * @return a success response with the deleted campaign, a success message, and a status code of
     * 200 if the campaign is successfully deleted. If an exception occurs, it returns an error
     * response with a message indicating that something went wrong and the error message from the
     * exception, along with a status code of 500.
     */
    public function delete(CampaignDeleteRequest $request)
    {
       try {
            $campaign = Campaign::findOrFail($request->id)->delete();

            return $this->successResponse(
                $campaign,
                'Registro eliminado exitosamente',
                200
            );
        } catch (\Exception $e) {
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
