<?php

namespace App\Http\Requests\Api\Cycle;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CycleUpdateRequest extends FormRequest
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
            'cycle_name' => 'required|string|max:255',
            'cycle_start_date' => 'required|date',
            'cycle_end_date' => 'required|date|after_or_equal:cycle_start_date',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'El campo :attribute es requerido.',
            'string' => 'El campo :attribute debe ser una cadena de caracteres.',
            'max' => 'El campo :attribute no debe exceder los :max caracteres.',
            'after_or_equal' => 'La fecha inicial del ciclo debe ser mayor o igual que la fecha final del ciclo'
        ];
    }

    public function attributes()
    {
        return [
            'cycle_name' => 'nombre del ciclo',
            'cycle_start_date' => 'fecha inicial del ciclo',
            'cycle_end_date' => 'fecha final del ciclo'
        ];
    }
}
