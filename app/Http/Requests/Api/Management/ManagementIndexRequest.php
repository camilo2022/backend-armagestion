<?php

namespace App\Http\Requests\Api\Management;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ManagementIndexRequest extends FormRequest
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
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'perPage' => 'required|integer|min:1',
            'fields' => 'nullable|array|min:1',
            'fields.*' => 'exists:management',
        ];
    }

    public function messages()
    {
        return [
            'start_date.required' => 'La :attribute es requerida.',
            'end_date.required' => 'La :attribute es requerida.',
            'end_date.after_or_equal' => 'La :attribute debe ser igual o posterior a la fecha de inicio.',
            'perPage.required' => 'El :attribute es requerido.',
            'perPage.integer' => 'El :attribute debe ser un número entero.',
            'perPage.min' => 'El :attribute debe ser al menos :min.',
            'fields.array' => 'El campo :attribute debe ser una arreglo.',
            'fields.min' => 'El campo :attribute debe debe tener al menos :min atributo.',
            'fields.*.exists' => 'La columna fields.* no existe en la tabla management',
            // Agrega mensajes adicionales para otros campos si es necesario
        ];
    }

    public function attributes()
    {
        return [
            'start_date' => 'fecha inicial',
            'end_date' => 'fecha final',
            'perPage' => 'número de elementos por página',
            'fields' => 'columna tabla management'
        ];
    }
}
