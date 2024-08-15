<?php

namespace App\Http\Requests\Api\Campaign;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CampaignStoreRequest extends FormRequest
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
           
            'camp_name' => 'required|string',
            'camp_description' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',

        ];
    }



    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */


    public function messages()
    {
        return [
            'required' => 'El campo :attribute es requerido.',
            'numeric' => 'El campo :attribute debe ser solo numero.',
            'string' => 'El campo :attribute debe ser una cadena de caracteres.',
            'max' => 'El campo :attribute no debe exceder los :max caracteres.',
            'exists' => 'El campo :attribute no existe en :exists.',
        ];
    }

    public function attributes()
    {
        return [
            //'csts_id' => 'codigo',
            'camp_name' => 'nombre de la campaña',
            'camp_description' => 'descripcion de la campaña',
            'camp_status' => 'estado de la capampaña',
            'user_id' => 'usuario coordinador de la campaña',
        ];
    }


}
