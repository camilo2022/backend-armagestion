<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use App\Models\ConfigurationModel;
use App\Http\Controllers\Api\V1\UsersHasConfigurationController;
use App\Http\Requests\Api\Configuration\ConfigurationIndexRequest;
use App\Http\Requests\Api\Configuration\ConfigurationStoreRequest;
use App\Http\Requests\Api\Configuration\ConfigurationUpdateRequest;
use App\Http\Requests\Api\Configuration\ConfigurationDeleteRequest;
use App\Traits\ApiResponser;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Configuration\ConfigurationIndexCollection;
use App\Models\Assignment;
use App\Models\Campaign;
use App\Models\Configuration;
use App\Models\Focus;
use Exception;
use Illuminate\Support\Facades\Auth;

class ConfigurationController extends Controller
{
    use ApiResponser;
    private $success = 'Consulta Exitosa.';
    private $error = 'Algo salió mal.';

    protected $UsersHasConfigurationController;

    public function __construct(UsersHasConfigurationController $UsersHasConfigurationController)
    {
        $this->UsersHasConfigurationController = $UsersHasConfigurationController;
    }

    public function index(ConfigurationIndexRequest $request)
    {
        try {
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $end_date = Carbon::parse($request->end_date)->endOfDay();

            $configuration = Configuration::with('focus.focus', 'assignments.assignment', 'campaign.campaign', 'users', 'time_patterns')
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
            ->when(!Auth::user()->hasRole('Administrador'),
                function ($query) {
                    $query->where('user_id', '=', Auth::user()->id);
                }
            )
            ->orderBy('id', 'ASC')
            ->paginate($request->perPage);

            return $this->successResponse(
                new ConfigurationIndexCollection($configuration),
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

    public function store(ConfigurationStoreRequest $request)
    {

       /*  return $request; */
        try {
            $configuration = new Configuration();
            $configuration->cycle_code = $request->cycle_code;
            $configuration->user_interactions_min_count = $request->user_interactions_min_count;
            $configuration->user_interactions_max_count = $request->user_interactions_max_count;
            $configuration->effectiveness_percentage = $request->effectiveness_percentage;
            $configuration->payment_agreement_percentage = $request->payment_agreement_percentage;
            $configuration->payment_agreement_true_percentage = $request->payment_agreement_true_percentage;
            $configuration->type_service_percentage = $request->type_service_percentage;
            $configuration->user_id = $request->userCoordinador_id;
            //Validar el tipo de configuracion (fija o movil)
            if ($request->valueData == 2) {
                //configuracion fija
                $configuration->confirmation_block_fija = true;
                $configuration->confirmation_block_movil = false;
            } elseif ($request->valueData == 3) {
                //configuracion movil
                $configuration->confirmation_block_fija = false;
                $configuration->confirmation_block_movil = true;
            } else {
                //configuracion normal
                $configuration->confirmation_block_fija = false;
                $configuration->confirmation_block_movil = false;
            }

            $configuration->save();

            $configuration_model = new ConfigurationModel();
            $configuration_model->configuration_id = $configuration->id;
            $configuration_model->model_id = $request->campaign_id;
            $configuration_model->model_type = Campaign::class;
            $configuration_model->save();

            foreach ($request->assignment_id as $assignment_id) {
                $configuration_model = new ConfigurationModel();
                $configuration_model->configuration_id = $configuration->id;
                $configuration_model->model_id = $assignment_id;
                $configuration_model->model_type = Assignment::class;
                $configuration_model->save();

            }

            foreach ($request->focus_ids as $focus_id) {
                $configuration_model = new ConfigurationModel();
                $configuration_model->configuration_id = $configuration->id;
                $configuration_model->model_id = $focus_id;
                $configuration_model->model_type = Focus::class;
                $configuration_model->save();
            }

            $request->merge([
                'configuration_id' => $configuration->id
            ]);

            $this->UsersHasConfigurationController->store($request);

            // Accede al controlador de PatronesHorarios para crear los distintos horarios dependiendo de su función
            // a la configuración recien creada.
            $controladorTimePattern = new TimePatternController();
            $controladorTimePattern->storeTimePatterConfiguration($configuration->id);

            return $this->successResponse(
                $configuration,
                'Configuracion de campaña creada exitosamente.',
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

    public function update(ConfigurationUpdateRequest $request, $id)
    {
        try {
            $configuration = Configuration::findOrFail($id);

            $configuration->cycle_code = $request->cycle_code;
            $configuration->user_interactions_min_count = $request->user_interactions_min_count;
            $configuration->user_interactions_max_count = $request->user_interactions_max_count;
            $configuration->effectiveness_percentage = $request->effectiveness_percentage;
            $configuration->payment_agreement_percentage = $request->payment_agreement_percentage;
            $configuration->payment_agreement_true_percentage = $request->payment_agreement_true_percentage;
            $configuration->type_service_percentage = $request->type_service_percentage;
            $configuration->user_id = $request->userCoordinador_id;
            $configuration->save();

            ConfigurationModel::where('configuration_id', $configuration->id)->delete();

            $configuration_model = new ConfigurationModel();
            $configuration_model->configuration_id = $configuration->id;
            $configuration_model->model_id = $request->campaign_id;
            $configuration_model->model_type = Campaign::class;
            $configuration_model->save();

            foreach ($request->assignment_id as $assignment_id) {
                $configuration_model = new ConfigurationModel();
                $configuration_model->configuration_id = $configuration->id;
                $configuration_model->model_id = $assignment_id;
                $configuration_model->model_type = Assignment::class;
                $configuration_model->save();
            }

            foreach ($request->focus_ids as $focus_id) {
                $configuration_model = new ConfigurationModel();
                $configuration_model->configuration_id = $configuration->id;
                $configuration_model->model_id = $focus_id;
                $configuration_model->model_type = Focus::class;
                $configuration_model->save();
            }

            $this->UsersHasConfigurationController->update($request);

            return $this->successResponse(
                $configuration,
                'Configuracion de campaña actualizada exitosamente.',
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

    public function delete(ConfigurationDeleteRequest $request)
    {
        try {
            $this->UsersHasConfigurationController->delete($request);

            $configuration = ConfigurationModel::findOrFail($request->id)->delete();

            return $this->successResponse(
                $configuration,
                'Registro eliminado exitosamente.',
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
}
