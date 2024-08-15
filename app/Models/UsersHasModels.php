<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersHasModels extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'users_has_models';

    protected $fillable = [
        'user_id',
        'model_type',
        'model_id',
        'campaign_id'
    ];

    public function model() : MorphTo
    {
        return $this->morphTo();
    }
}
