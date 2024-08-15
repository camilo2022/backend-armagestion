<?php

namespace App\Http\Requests\Api\People;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PeopleStoreRequest extends FormRequest
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
            'peop_name' => 'required|string|max:255',
            'peop_last_name' => 'required|string|max:255',
            'peop_dni' => 'required|max:40|unique:people', // Asegura que el DNI sea único en la tabla people
            'peop_status' => 'required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'El campo :attribute es requerido.',
            'string' => 'El campo :attribute debe ser una cadena de caracteres.',
            'max' => 'El campo :attribute no debe exceder los :max caracteres.',
            'unique' => 'El campo :attribute ya ha sido tomado.',
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ];
    }

    public function attributes()
    {
        return [
            'peop_name' => 'nombre',
            'peop_last_name' => 'apellido',
            'peop_dni' => 'número de documento',
            'peop_status' => 'estado',
        ];
    }
}
