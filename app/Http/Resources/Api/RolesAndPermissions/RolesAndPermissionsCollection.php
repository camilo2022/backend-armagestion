<?php

namespace App\Http\Resources\Api\RolesAndPermissions;

use Illuminate\Http\Resources\Json\ResourceCollection;

class RolesAndPermissionsCollection extends ResourceCollection
{
    
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
     
    public function toArray($request)
    {
        return [
            'roles' => $this->collection->map(function ($role) {
               
                /* return [
                    'role' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                ]; */
                return [
                    'id' => $role->id,
                    'role' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                    'permissions' => $role->permissions->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name
                        ];
                    })->toArray()
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