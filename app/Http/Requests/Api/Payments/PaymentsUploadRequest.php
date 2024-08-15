<?php

namespace App\Http\Requests\Api\Payments;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PaymentsUploadRequest extends FormRequest
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
            'pays_file' => 'required|file|mimes:csv,txt,xls,xlsx',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'El campo :attribute es requerido.',
            'file' => 'El campo :attribute debe ser un archivo.',
            'pays_file.mimes' => 'El :attribute debe ser un archivo de tipo :values.',
            'exists' => 'El campo :attribute no existe en la base de datos.',
        ];
    }

    public function attributes()
    {
        return [
            'pays_file' => 'archivo de pagos',
        ];
    }
}
