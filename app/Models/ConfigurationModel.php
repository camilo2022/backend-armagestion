<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ConfigurationModel extends Model
{
    use HasFactory;

    protected $table = 'configuration_models';

    protected $fillable = [
        'configuration_id',
        'model_id',
        'model_type',
    ];

    public function model() : MorphTo
    {
        return $this->morphTo();
    }

    public function assignment() : BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'model_id');
    }

    public function focus() : BelongsTo
    {
        return $this->belongsTo(Focus::class, 'model_id');
    }

    public function campaign() : BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'model_id');
    }
}
