<?php

namespace App\Http\Resources\Api\Focus;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FocusIndexCollection extends ResourceCollection
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
            'focus' => $this->collection->map(function ($focus) {
                return [
                    'id' => $focus->id,
                    'focus_name' => $focus->foal_name,
                    'focus_description' => $focus->foal_description,
                    'created_at' => Carbon::parse($focus->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($focus->updated_at)->format('Y-m-d H:i:s'),
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
