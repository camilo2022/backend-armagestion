<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Api\Auth\RegisterRequest;

class RegisterController extends Controller
{
    /**
     * Method register
     *
     * @param RegisterRequest $request [explicite description]
     *
     * @return void
     */
    public function register(RegisterRequest $request)
    {
        try {
            //Create user
            $user = User::create([
                'name' => $request->input('name'),
                'document_number' => $request->input('document_number'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
            ]);

            // Crear un token de acceso para el usuario utilizando Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

             // Devolver una respuesta con el token de acceso y el usuario creado
            return response()->json([ 'user' => $user, 'token' => $token, 'token_type' => 'Bearer', ], 201);

        } catch (Exception $e) {
            // Devolver una respuesta de error en caso de excepción
            return response()->json(['message' => 'Error al registrar al usuario: ' . $e->getMessage()], 500);
        }
    }
}
