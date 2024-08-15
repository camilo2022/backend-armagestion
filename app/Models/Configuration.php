<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Configuration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'configurations';

    protected $fillable = [
        'model_id',
        'model_type',
        'cycle_code',
        'focus_id',
        'user_count',
        'user_interactions_min_count',
        'user_interactions_max_count',
        'effectiveness_percentage',
        'payment_agreement_percentage',
        'user_id',
        'confirmation_block_fija',
        'confirmation_block_movil',
    ];

    protected $casts = [
        'cycle_code' => 'json',
        'user_id' => 'json',
    ];

    public function models() : HasMany
    {
        return $this->hasMany(ConfigurationModel::class, 'configuration_id');
    }

    public function focus()
    {
        return $this->hasMany(ConfigurationModel::class, 'configuration_id')
                    ->where('model_type', '=', Focus::class);
    }

    public function assignments()
    {
        return $this->hasMany(ConfigurationModel::class, 'configuration_id')
                    ->where('model_type', '=', Assignment::class);
    }

    public function campaign()
    {
        return $this->hasOne(ConfigurationModel::class, 'configuration_id')
                    ->where('model_type', '=', Campaign::class);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users() : MorphToMany
    {
        return $this->morphToMany(User::class, 'model', 'users_has_models')
            ->where(function ($query) {
                $query->whereNull('users_has_models.deleted_at');
            }
        );
    }

    public function time_patterns(){

        return $this->hasMany(TimePattern::class, 'id_configurations');

    }

    public function scopeSearch($query, $search)
    {
        return $query->whereHas('campaign.campaign',
            function ($campaignQuery) use ($search) {
                $campaignQuery->where('camp_name', 'like', '%' . $search . '%');
            }
        )
        ->orWhereHas('focus.focus',
            function ($focusQuery) use ($search) {
                $focusQuery->where('foal_name', 'like', '%' . $search . '%');
            }
        )
        ->orWhereHas('assignments.assignment',
            function ($focusQuery) use ($search) {
                $focusQuery->where('assi_name', 'like', '%' . $search . '%');
            }
        );
    }

    public function scopeFilterByDate($query, $start_date, $end_date)
    {
        return $query->whereBetween('created_at', [$start_date, $end_date]);
    }
}
