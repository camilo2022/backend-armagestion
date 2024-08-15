<?php

namespace App\Http\Requests\Api\Configuration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfigurationStoreRequest extends FormRequest
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
            'campaign_id' => 'required|exists:campaigns,id',
            'focus_ids' => 'required|array',
            'focus_ids.*' => 'exists:focus,id',
            'assignment_id' => 'required|array',
            'assignment_id.*' => 'exists:assignments,id',
            'cycle_code' => 'required|array',
            'cycle_code.*' => 'integer|exists:cycles,id',
            'user_interactions_min_count' => 'required|integer',
            'user_interactions_max_count' => 'required|integer|gt:user_interactions_min_count',
            'effectiveness_percentage' => 'required|numeric|between:0,100',
            'payment_agreement_percentage' => 'required|numeric|between:0,100',
            'payment_agreement_true_percentage' => 'required|numeric|between:0,100',
            'type_service_percentage' => 'nullable|numeric|between:0,100',
            'users_assigned' => 'required|array',
            'users_assigned.*' => 'integer|exists:users,id',
            'user_id' => 'required|array',
            'user_id.*' => 'integer|exists:users,id',

        ];
    }

    public function messages()
    {
        return [
            'in' => 'El campo ::attribute no es valido.',
            'exists' => 'El campo :attribute no exite.',
            'required' => 'El campo :attribute de campaña es obligatorio.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'between' => 'El :attribute debe estar entre :min y :max.',
            'array' => 'El campo :attribute debe ser un arreglo.',
            'gt' => 'El campo :attribute debe ser mayor a :gt.',
            'users_assigned.*.unique' => 'El :attribute esta asignado a otra configuracion de Campaña.',
        ];
    }

    public function attributes()
    {
        return [
            'model_type' => 'Modelo a configurar',
            'model_id' => 'Código de Campaña / Asignacion a configurar',
            'cycle_code' => 'Código de Ciclo',
            'cycle_code.*' => 'Elemento en id de Ciclos',
            'focus_id' => 'Código del Foco',
            'user_interactions_min_count' => 'Cantidad de Gestiones por Usuario minima',
            'user_interactions_max_count' => 'Cantidad de Gestiones por Usuario maxima',
            'effectiveness_percentage' => 'Porcentaje de Efectividad',
            'payment_agreement_percentage' => 'Porcentaje de acuerdo al pago total de gestiones',
            'payment_agreement_percentage' => 'Porcentaje de acuerdos de pago gestiones reales',
            'assignments_id' => 'Códigos de Asignaciones',
            'assignments_id.*' => 'Elemento id de la asignacion',
            'users_assigned' => 'Código de Ejecutivo',
            'users_assigned.*' => 'Elemento en id de Ejecutivos',
        ];
    }

}
