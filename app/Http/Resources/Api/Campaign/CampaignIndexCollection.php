<?php

namespace App\Http\Resources\Api\Campaign;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CampaignIndexCollection extends ResourceCollection
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
            'campaigns' => $this->collection->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'camp_name' => $campaign->camp_name,
                    'camp_description' => $campaign->camp_description,
                    'camp_status' => $campaign->camp_status,
                    'alli_id' => $campaign->alli_id,
                    'user_id' => $campaign->user_id,
                    'users' => $campaign->user,
                    'created_at' => Carbon::parse($campaign->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($campaign->updated_at)->format('Y-m-d H:i:s'),

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
