<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TimePattern\TimePatternIndexCollection;
use App\Models\Configuration;
use App\Models\FunctionPattern;
use App\Models\TimePattern;
use App\Traits\ApiResponser;
use Exception;
use Illuminate\Http\Request;

class TimePatternController extends Controller
{

    use ApiResponser;
    private $success = 'Consulta Exitosa.';
    private $error = 'Algo salió mal.';

    public function index(Request $request)
    {
        $timePatterns = TimePattern::orderBy('id_time_patterns', 'ASC')
            ->paginate($request->perPage);
        return $this->successResponse(
            new TimePatternIndexCollection($timePatterns),
            $this->success,
            200
        );
    }

    public function update(Request $request, $id)
    {
        try {
            // Obtener la instancia de TimePattern que cumple con las condiciones
            $timePattern = TimePattern::where('id_configurations', $request->configuration_id)/* $request->configuration_id */
                ->where('id_function', $request->function_id)
                ->first();

            // Decodificar los datos JSON usando el campo horaPattern del request
            $decodedData = json_decode($timePattern->{$request->horaPattern}, true);

            //Descodificar los datos JSON traidos por el request por parte del usuario para actualizarlos
            $decodedDataValue = json_decode($request->value, true);

            $array = [
                'no_efectiva_1' => $decodedDataValue['no_efectiva_1'] ?? $decodedData['no_efectiva_1'],
                'no_efectiva_2' => $decodedDataValue['no_efectiva_2'] ?? $decodedData['no_efectiva_2'],
                'efectiva' => $decodedDataValue['efectiva'] ?? $decodedData['efectiva'],
                'promesa' => $decodedDataValue['promesa'] ?? $decodedData['promesa'],
            ];

            $timePattern->id_function = $request->function_id;
            $timePattern->{$request->horaPattern} = json_encode($array);
            $timePattern->id_configurations = $request->configuration_id;/* $request->configuration_id */
            $timePattern->save();

            return response()->json([
                'TimePattern' => $timePattern,
                'status' => 'success'
            ]);
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

    public function DefaultTimePattern(Request $request, $id)
    {
        try {
            $timePatterns = TimePattern::where('id_configurations', $id)/* $request->configuration_id */
                ->orderBy('id_time_patterns', 'ASC')
                ->get();
            $functions = FunctionPattern::all();
            foreach ($timePatterns as $index => $timePattern) {
                $timePattern->id_function = $functions[$index]->id_function;
                $timePattern->objects_8_in_10 = json_encode([
                    'no_efectiva_1' => 0,
                    'no_efectiva_2' => 0,
                    'efectiva' => 1,
                    'promesa' => 1,
                ]);
                $timePattern->objects_16_in_17 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 3,
                    'efectiva' => 0,
                    'promesa' => 0,
                ]);
                $timePattern->objects_12_in_13 = json_encode([
                    'no_efectiva_1' => 0,
                    'no_efectiva_2' => 0,
                    'efectiva' => 0,
                    'promesa' => 1,
                ]);
                $timePattern->objects_15_in_16 = json_encode([
                    'no_efectiva_1' => 0,
                    'no_efectiva_2' => 0,
                    'efectiva' => 1,
                    'promesa' => 0,
                ]);
                $timePattern->objects_11_in_13 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 3,
                    'efectiva' => 0,
                    'promesa' => 0,
                ]);
                $timePattern->objects_08_in_14 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 2,
                    'efectiva' => 1,
                    'promesa' => 0,
                ]);
                $timePattern->objects_15_in_18 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 3,
                    'efectiva' => 0,
                    'promesa' => 0,
                ]);
                $timePattern->objects_13_in_17 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 2,
                    'efectiva' => 1,
                    'promesa' => 0,
                ]);
                $timePattern->objects_08_in_13 = json_encode([
                    'no_efectiva_1' => 0,
                    'no_efectiva_2' => 0,
                    'efectiva' => 1,
                    'promesa' => 0,
                ]);
                $timePattern->objects_16_in_17_50 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 2,
                    'efectiva' => 0,
                    'promesa' => 1,
                ]);
                $timePattern->id_configurations = $id;/* $request->configuration_id */
                $timePattern->save();
            }
            return response()->json([
                'TimePattern' => $timePattern,
                'status' => 'success'
            ]);
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

    public function storeTimePatterConfiguration($id)
    {
        try {
            $functions = FunctionPattern::orderBy('id_function', 'ASC')->get();
            foreach ($functions as $function) {
                $timePattern = new TimePattern();
                $timePattern->id_function = $function->id_function;
                $timePattern->objects_8_in_10 = json_encode([
                    'no_efectiva_1' => 0,
                    'no_efectiva_2' => 0,
                    'efectiva' => 1,
                    'promesa' => 1,
                ]);
                $timePattern->objects_16_in_17 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 3,
                    'efectiva' => 0,
                    'promesa' => 0,
                ]);
                $timePattern->objects_12_in_13 = json_encode([
                    'no_efectiva_1' => 0,
                    'no_efectiva_2' => 0,
                    'efectiva' => 0,
                    'promesa' => 1,
                ]);
                $timePattern->objects_15_in_16 = json_encode([
                    'no_efectiva_1' => 0,
                    'no_efectiva_2' => 0,
                    'efectiva' => 1,
                    'promesa' => 0,
                ]);
                $timePattern->objects_11_in_13 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 3,
                    'efectiva' => 0,
                    'promesa' => 0,
                ]);
                $timePattern->objects_08_in_14 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 2,
                    'efectiva' => 1,
                    'promesa' => 0,
                ]);
                $timePattern->objects_15_in_18 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 3,
                    'efectiva' => 0,
                    'promesa' => 0,
                ]);
                $timePattern->objects_13_in_17 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 2,
                    'efectiva' => 1,
                    'promesa' => 0,
                ]);
                $timePattern->objects_08_in_13 = json_encode([
                    'no_efectiva_1' => 0,
                    'no_efectiva_2' => 0,
                    'efectiva' => 1,
                    'promesa' => 0,
                ]);
                $timePattern->objects_16_in_17_50 = json_encode([
                    'no_efectiva_1' => 1,
                    'no_efectiva_2' => 2,
                    'efectiva' => 0,
                    'promesa' => 1,
                ]);
                $timePattern->id_configurations = $id;/* $request->configuration_id */
                $timePattern->save();
            }
            return response()->json([
                'status' => 'success'
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(
                [
                    'message' => $this->error,
                    'error' => $e->getMessage()
                ],
                500
            );;
        }
    }
}
