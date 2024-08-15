<?php

namespace App\Http\Requests\Api\Configuration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigurationDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */

    protected function failedValidation(Validator $validator)
    {
        // Lanzar una excepción de validación con los errores de validación obtenidos
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación',
            'errors' => $validator->errors()
        ], 422));
    }

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
            'id' => 'required|exists:configuration_models,id',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'El campo :attribute es requerido.',
            'id.exists' => 'El :attribute proporcionado no es válido.',
        ];
    }

    public function attributes()
    {
        return [
            'id' => 'Identificador unico',
        ];
    }

}
