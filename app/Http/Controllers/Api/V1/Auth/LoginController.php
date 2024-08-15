<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Api\Auth\LoginRequest;

class LoginController extends Controller
{

    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\ResponseJson
     */
    public function login(LoginRequest $request)
    {
        try {
             // Busca el usuario en la base de datos a través del correo electrónico
            $user = User::where('email', '=', $request->email)->firstOrFail();

            // Comprueba si la contraseña proporcionada coincide con la contraseña almacenada en la base de datos
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('user_token')->plainTextToken;

                // Crea un nuevo token para el usuario y lo devuelve como respuesta en formato JSON
                return response()->json([ 'user' => $user, 'token' => $token ], 200);
            }

            // Si las credenciales no son válidas, devuelve un mensaje de error en formato JSON
            return response()->json([ 'error' => 'Contraseña o correo invalido' , 'success' => false ]);

        } catch (\Exception $e) {
            // Si ocurre una excepción, devuelve un mensaje de error en formato JSON
            return response()->json([ 'message' => 'Algo salió mal', 'error' => $e->getMessage() ]);
        }
    }

}
