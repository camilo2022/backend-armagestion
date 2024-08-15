<?php

namespace App\Http\Resources\Api\TimePattern;

use App\Models\Cycle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TimePatternIndexCollection extends ResourceCollection
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
            'timePattern' => $this->collection->map(function ($timePatterns) {
                return [
                    'id' => $timePatterns->id_time_patterns,
                    'id_function' => $timePatterns->id_function,
                    'objects_8_in_10' => $timePatterns->objects_8_in_10,
                    'objects_16_in_17' => $timePatterns->objects_16_in_17,
                    'objects_12_in_13' => $timePatterns->objects_12_in_13,
                    'objects_15_in_16' => $timePatterns->objects_15_in_16,
                    'objects_11_in_13' => $timePatterns->objects_11_in_13,
                    'objects_08_in_14' => $timePatterns->objects_08_in_14,
                    'objects_15_in_18' => $timePatterns->objects_15_in_18,
                    'objects_13_in_17' => $timePatterns->objects_13_in_17,
                    'objects_08_in_13' => $timePatterns->objects_08_in_13,
                    'objects_16_in_17_50' => $timePatterns->objects_16_in_17_50,
                    'id_configurations' => $timePatterns->id_configurations,
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
