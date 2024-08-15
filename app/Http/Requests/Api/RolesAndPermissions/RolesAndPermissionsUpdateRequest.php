<?php

namespace App\Http\Requests\Api\RolesAndPermissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RolesAndPermissionsUpdateRequest extends FormRequest
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
            'role_name' => 'nullable|string|max:50',
            'permissions.*' => 'required|max:50',
        ];
    }

    public function messages()
    {
        return [
            'required' => 'El campo :attribute es requerido.',
            'string' => 'El campo :attribute debe ser una cadena de caracteres.',
            'max' => 'El campo :attribute no debe exceder los :max caracteres.',
            'array' => 'El campo :attribute debe ser un arreglo.',
            'permissions.*.exists' => 'El :attribute seleccionado no existe en la base de datos.',
        ];
    }

    public function attributes()
    {
        return [
            'role_name' => 'nombre del rol',
            'permissions' => 'permisos',
            'permissions.*' => 'permiso',
        ];
    }
}
