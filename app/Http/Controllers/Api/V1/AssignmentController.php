<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Assignment\AssignmentIndexRequest;
use App\Http\Resources\Api\Assignment\AssignmentIndexCollection;
use App\Models\Assignment;
use App\Traits\ApiResponser;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    use ApiResponser;
    private $success = 'Consulta Exitosa.';
    private $error = 'Algo salió mal.';

    public function index(AssignmentIndexRequest $request)
    {
        try {
            $start_date = Carbon::parse($request->start_date)->startOfDay();
            $end_date = Carbon::parse($request->end_date)->endOfDay();

            // Consultar los enfoques aplicando los filtros
            $assignments = Assignment::with('campaign')
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
                ->when($request->filled('camp_id'),
                    function ($query) use ($request) {
                        $query->where('camp_id', $request->camp_id);
                    }
                )
                ->orderBy('id', 'ASC')
                ->paginate($request->perPage);
            // Retornar una respuesta de éxito con el listado paginado de enfoques
            return $this->successResponse(
                new AssignmentIndexCollection($assignments),
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
}
