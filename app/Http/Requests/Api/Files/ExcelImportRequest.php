<?php

namespace App\Http\Requests\Api\Files;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorData;
use Illuminate\Http\Exceptions\HttpResponseException;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ImportExcel;
use Exception;

class ExcelImportRequest extends FormRequest
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
        // Aquí puedes agregar la lógica de autorización si es necesario
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file' => 'required|file|mimes:csv,txt',
        ];
    }

    // Mensajes de error personalizados para cada regla de validación
    public function messages()
    {
        return [
            'required' => 'El campo :attribute es requerido',
            'file' => 'El :attribute subido es de tipo inválido. El :attribute debe ser un archivo de tipo :values',
            'file.mimes' => 'El :attribute debe ser un archivo de tipo :values',
        ];
    }

    public function attributes()
    {
        // Nombres personalizados para cada campo de la solicitud
        return [
            'file' => 'archivo'
        ];
    }

    // public function validatedData()
    // {
    //     $file = $this->file('file');
    //     $arrayData = Excel::toArray(new ImportExcel, $file)[0];


    //     $arrayDataFiltered = array_filter($arrayData, function ($object) {
    //         return !in_array(null, $object, true) && !in_array('', $object, true);
    //     });
    //     if (count($arrayDataFiltered) !== count($arrayData)) {
    //         $emptyRows = array_reduce(array_keys(array_diff_key($arrayData, $arrayDataFiltered)), function ($carry, $position) {
    //             $carry[] = $position + 2;
    //             return $carry;
    //         }, []);
    //         throw new Exception('Se encontraron campos vacíos. Por favor verifique el archivo cargado.');
    //     }

    //     return $arrayData;
    // }
}
