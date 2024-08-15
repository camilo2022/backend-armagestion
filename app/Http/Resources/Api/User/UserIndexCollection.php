<?php

namespace App\Http\Resources\Api\User;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserIndexCollection extends ResourceCollection
{
     /**
     * OJO SI VOY A ENVIAR UN ARRAY DE MUCHOS DATOS SE USAN LAS COLLECTIOSNES PARA DARLE FORMATO A LA RESPUESTA
     * usarla de esta manera permite darle un formato mas definido a las variables de respuesta
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'users' => $this->collection->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'last_name' => $user->last_name,
                    'document_number' => $user->document_number,
                    'phone_number' => $user->phone_number,
                    'address' => $user->address,
                    'email' => $user->email,
                    'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
                    'roles' => $user->roles,
                    'permissions' => $user->permissions,
                    'configurations' => $user->configurations,
                    'assignments' => $user->assignments
                ];
            }),
            'meta' => [
                'pagination' => [
                    'total' => $this->total(),
                    'count' => $this->count(),
                    'per_page' => $this->perPage(),
                    'current_page' => $this->currentPage(),
                    'total_pages' => $this->lastPage(),
                ],
            ],
        ];
    }
}
