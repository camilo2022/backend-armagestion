<?php

namespace App\Http\Resources\Api\Configuration;

use App\Models\Cycle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ConfigurationIndexCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $userRol = User::whereHas("roles", function($q){ $q->where("name", "Coordinador"); })->get();

        return [
            'configurations' => $this->collection->map(function ($configuration) use ($userRol) {
                return [
                    'id' => $configuration->id,
                    'focus' => $configuration->focus->pluck('focus'),
                    'assignments' => $configuration->assignments->pluck('assignment'),
                    'campaign' => isset($configuration->campaign->campaign) ? $configuration->campaign->campaign : null,
                    'users' => $configuration->users,
                    'usergetcoodin' => $userRol,
                    'cycle_code' => $configuration->cycle_code,
                    'cycles' => Cycle::whereIn('id', $configuration->cycle_code)->get(),
                    'user_interactions_min_count' => $configuration->user_interactions_min_count,
                    'user_interactions_max_count' => $configuration->user_interactions_max_count,
                    'user_count' => $configuration->users->count(),
                    'effectiveness_percentage' => $configuration->effectiveness_percentage,
                    'payment_agreement_percentage' => $configuration->payment_agreement_percentage,
                    'payment_agreement_true_percentage' => $configuration->payment_agreement_true_percentage,
                    'type_service_percentage' => $configuration->type_service_percentage,
                    'userCoordinador_id' => is_array( $configuration->user_id) ? User::whereIn('id', $configuration->user_id)->get() : [],
                    'created_at' => Carbon::parse($configuration->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($configuration->updated_at)->format('Y-m-d H:i:s'),
                    'confirmation_block_fija' => $configuration->confirmation_block_fija,
                    'confirmation_block_movil' => $configuration->confirmation_block_movil,
                    'timePatterns_1' => array_values($configuration->time_patterns->where('id_function', 1)->toArray()),
                    'timePatterns_2' => array_values($configuration->time_patterns->where('id_function', 2)->toArray()),
                    'timePatterns_3' => array_values($configuration->time_patterns->where('id_function', 3)->toArray()),
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
