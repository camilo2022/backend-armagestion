<?php

namespace App\Http\Resources\Api\Cycle;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CycleIndexCollection extends ResourceCollection
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
            'cycles' => $this->collection->map(function ($cycle) {
                return [
                    'id' => $cycle->id,
                    'cycle_name' => $cycle->cycle_name,
                    'cycle_start_date' => $cycle->cycle_start_date,
                    'cycle_end_date' => $cycle->cycle_end_date,
                    'created_at' => Carbon::parse($cycle->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($cycle->updated_at)->format('Y-m-d H:i:s'),
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
