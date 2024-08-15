<?php

namespace App\Http\Requests\Api\UploadPayments;

use App\Models\Assignment;
use App\Models\Campaign;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UploadPaymentsStoreRequest extends FormRequest
{
    /**
     * Maneja una solicitud fallida de validación.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        // Lanzar una excepción de validación con los errores de validación obtenidos
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación',
            'errors' => $validator->errors()
        ], 422));
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules = [
            'payments' => 'required|array',
            'payments.*.pay_account' => 'required',
            'payments.*.pay_value' => 'required|numeric',
            'payments.*.pay_discount_rate' => 'required|min:0|max:100',
            'payments.*.pay_date' => 'required|date|date_format:Y-m-d',
            'payments.*.cycle_id' => 'required|exists:cycles,id',
            'payments.*.focus_id' => 'required|exists:focus,id',
            'payments.*.real_payment' => 'required',
            'payments.*.pay_recaudation_date' => 'nullable|date|date_format:Y-m-d',
            'payments.*.assi_id' => 'nullable|exists:assignments,id',
            'payments.*.camp_id' => 'nullable|exists:campaigns,id',
        ];

        foreach ($this->payments as $index => $payment) {
            $rules["payments.{$index}.model_type"] = [
                'required', 'string', Rule::in([Campaign::class, Assignment::class])
            ];
            $rules["payments.{$index}.model_id"] = [
                'required', 'numeric', 'exists:' . $payment['model_type'] . ',id'
            ];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'min' => 'El valor minimo del campo :attribute debe ser de :min',
            'max' => 'El valor maximo del campo :attribute debe ser de :max',
            'array' => 'El campo :attribute debe ser un arreglo.',
            'required' => 'El campo :attribute es requerido.',
            'numeric' => 'El campo :attribute debe ser numerico.',
            'date' => 'El campo :attribute debe ser una fecha.',
            'required' => 'El campo :attribute es requerido.',
            'exists' => 'El campo :attribute no existe en la base de datos.',
        ];
    }

    public function attributes()
    {
        return [
            'payments.*.pay_account' => 'numero de cuenta del pago',
            'payments.*.pay_value' => 'valor del pago',
            'payments.*.pay_discount_rate' => 'porcetaje de descuento al pago',
            'payments.*.pay_date' => 'fecha de pago',
            'payments.*.cycle_id' => 'identificador del ciclo',
            'payments.*.focus_id' => 'identificador del foco',
            'payments.*.assi_id' => 'identificador de la aignacion',
            'payments.*.camp_id' => 'identificador de la campaña',
        ];
    }
}
