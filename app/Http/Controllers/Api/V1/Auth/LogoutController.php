<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LogoutRequest;

class LogoutController extends Controller
{
    /**
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\ResponseJson
     */
    public function logout(LogoutRequest $request)
    {
        try {
            // Obtener el usuario autenticado
            $user = User::findOrFail($request->user_id);

            // Revocar todos los tokens de acceso del usuario
            $user->tokens()->delete();

            // Devolver una respuesta exitosa
            return response()->json([ 'message' => 'Seccion cerrada exitosomente', 'success' => true,], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Algo salió mal', 'error' => $e->getMessage()]);
        }
    }

}
