<?php

namespace App\Http\Requests\Api\RolesAndPermissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RolesAndPermissionsDeleteRequest extends FormRequest
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
            'role_id' => 'required|array',
            'role_id.*' => 'integer|exists:roles,id', // Asegura que los id de roles existan en la tabla roles
            'permission_id' => 'required|array',
            'permission_id.*' => 'integer|exists:permissions,id', // Asegura que los id de permisos existan en la tabla permissions
        ];
    }

    public function messages()
    {
        return [
            'required' => 'El campo :attribute es requerido.',
            'array' => 'El campo :attribute debe ser un arreglo.',
            'integer' => 'Cada elemento en :attribute debe ser un número entero.',
            'exists' => 'El :attribute no existe en la base de datos.', // Mensaje para campos que deben existir
        ];
    }

    public function attributes()
    {
        return [
            'role_id' => 'id de Roles',
            'permission_id' => 'id de Permisos',
            'role_id.*' => 'Elemento en id de Roles',
            'permission_id.*' => 'Elemento en id de Permisos',
        ];
    }
}
