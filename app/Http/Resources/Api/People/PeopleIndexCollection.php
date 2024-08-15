<?php

namespace App\Http\Resources\Api\People;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PeopleIndexCollection extends ResourceCollection
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
            'peoples' => $this->collection->map(function ($person) {
                return [
                    'id' => $person->id,
                    'peop_name' => $person->peop_name,
                    'peop_last_name' => $person->peop_last_name,
                    'peop_dni' => $person->peop_dni,
                    'peop_status' => $person->peop_status,
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
