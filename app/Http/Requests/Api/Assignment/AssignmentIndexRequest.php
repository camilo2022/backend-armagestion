<?php

namespace App\Http\Requests\Api\Assignment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssignmentIndexRequest extends FormRequest
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
            'camp_id' => 'nullable|exists:campaigns,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'perPage' => 'required|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'camp_id.required' => 'El identificador de la campaña es requerido.',
            'camp_id.exists' => 'El identificador de la campaña no es valido.',
            'start_date.required' => 'La fecha de inicio es requerida.',
            'end_date.required' => 'La fecha de fin es requerida.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'perPage.required' => 'El número de elementos por página es requerido.',
            'perPage.integer' => 'El número de elementos por página debe ser un número entero.',
            'perPage.min' => 'El número de elementos por página debe ser al menos :min.'
        ];
    }

    public function attributes()
    {
        return [
            'camp_id' => 'identificador de la campaña',
            'start_date' => 'Fecha de inicio',
            'end_date' => 'Fecha de fin',
            'perPage' => 'Numero de página',
        ];
    }

}

