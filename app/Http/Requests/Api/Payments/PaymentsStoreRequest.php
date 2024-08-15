<?php

namespace App\Http\Requests\Api\Payments;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PaymentsStoreRequest extends FormRequest
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
        return [
            'pay_account' => 'required|numeric',
            'pay_value' => 'required|numeric',
            'pay_date' => 'required|date',
            'cycle' => 'required',
            'campaign_id' => 'required|exists:campaigns,id',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'El campo :attribute es requerido.',
            'numeric' => 'El campo :attribute debe ser numerico.',
            'date' => 'El campo :attribute debe ser una fecha.',
            'exists' => 'El campo :attribute no existe en la base de datos.',
        ];
    }

    public function attributes()
    {
        return [
            'pay_account' => 'numero de cuenta del pago',
            'pay_value' => 'valor del pago',
            'pay_date' => 'fecha de pago',
            'cycle' => 'identificador del ciclo',
            'campaign_id' => 'identificador de la campaña',
        ];
    }
}
