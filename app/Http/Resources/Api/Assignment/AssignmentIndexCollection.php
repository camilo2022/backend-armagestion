<?php

namespace App\Http\Resources\Api\Assignment;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AssignmentIndexCollection extends ResourceCollection
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
            'assignments' => $this->collection->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'assi_name' => $assignment->assi_name,
                    'campaign' => $assignment->campaign,
                    'created_at' => Carbon::parse($assignment->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($assignment->updated_at)->format('Y-m-d H:i:s'),

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
