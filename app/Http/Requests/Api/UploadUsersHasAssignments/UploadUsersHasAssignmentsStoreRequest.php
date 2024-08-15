<?php

namespace App\Http\Requests\Api\UploadUsersHasAssignments;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadUsersHasAssignmentsStoreRequest extends FormRequest
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
            'users_assigs' => 'required|array',
            'users_assigs.*.user_id' => 'required|numeric|exists:users,id',
            'users_assigs.*.assi_id' => 'required|numeric|exists:assignments,id',
        ];
    }

    public function messages()
    {
        return [
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
            'users_assigs.*.user_id' => 'identificador del usuario',
            'users_assigs.*.assi_id' => 'identificador de la aignacion',
        ];
    }
}
