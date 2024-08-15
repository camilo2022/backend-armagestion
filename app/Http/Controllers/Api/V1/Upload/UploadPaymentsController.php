<?php

namespace App\Http\Controllers\Api\V1\Upload;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UploadPayments\UploadPaymentsStoreRequest;
use App\Models\Assignment;
use App\Models\Campaign;
use App\Models\Payments;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class UploadPaymentsController extends Controller
{
    use ApiResponser;
    private $success = 'Consulta Exitosa.';
    private $error = 'Algo salió mal.';

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            ini_set('max_execution_time', 7200);
            ini_set('memory_limit', '4096M');
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        try {
            return $this->successResponse(
                '',
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

    public function store(UploadPaymentsStoreRequest $request)
    {
        try {
            foreach ($request->payments as $pay) {
                $payment = new Payments();
                $payment->model_type = $pay['model_type'];
                $payment->model_id = $pay['model_id'];
                $payment->pay_account = $pay['pay_account'];
                $payment->pay_value = $pay['pay_value'];
                $payment->pay_discount_rate = $pay['pay_discount_rate'];
                $payment->pay_date = Carbon::parse($pay['pay_date'])->format('Y-m-d');
                $payment->cycle_id = $pay['cycle_id'];
                $payment->focus_id = $pay['focus_id'];
                $payment->pay_recaudation_date = Carbon::parse($pay['pay_recaudation_date'])->format('Y-m-d');
                $payment->real_payment = $pay['real_payment'];
                $payment->save();
            }

            // Retornar una respuesta de éxito con un mensaje y código de respuesta
            return $this->successResponse(
                '',
                'Registros creados exitosamente.',
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

    public function update(Request $request, $id)
    {
        try {
            return $this->successResponse(
                '',
                'Registro actualizado exitosamente.',
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

    public function delete(Request $request){
        try {
            return $this->successResponse(
                '',
                'Registro eliminado exitosamente.',
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
}
