<?php

namespace App\Http\Resources\Api\Payments;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PaymentsIndexCollection extends ResourceCollection
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
            'payments' => $this->collection->map(function ($payments) {
                return [
                    'id' => $payments->id,
                    'pay_account' => $payments->pay_account,
                    'pay_value' => $payments->pay_value,
                    'pay_date' => Carbon::parse($payments->pay_date)->format('Y-m-d'),
                    'cycle_id' => $payments->cycle_id,
                    'cycle' => $payments->cycle,
                    'campaign_id' => $payments->campaign_id,
                    'campaign' => $payments->campaign,
                    'focus' => $payments->focus,
                    'assignment' => $payments->assignment,
                    'created_at' => Carbon::parse($payments->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($payments->updated_at)->format('Y-m-d H:i:s'),
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
