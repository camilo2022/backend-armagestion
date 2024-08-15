<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Assignment extends Model
{
    use HasFactory;

    protected $table = 'assignments';

    protected $fillable = [
        'assi_name',
        'camp_id'
    ];

    public function campaign() : BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'camp_id');
    }

    public function users() : MorphToMany
    {
        return $this->morphToMany(User::class, 'model', 'users_has_models')
            ->where(function ($query) {
                $query->whereNull('users_has_models.deleted_at');
            }
        );
    }

    /**
     * The scopeSearch function searches the database for records that have a matching value in either
     * the "focus_name" or "focus_description" columns.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('assi_name', 'like', '%' . $search . '%')
            ->orWhereHas('campaign',
                function ($campaignQuery) use ($search) {
                    $campaignQuery->where('camp_name', 'like', '%' . $search . '%');
                }
            );
    }

    public function scopeFilterByDate($query, $start_date, $end_date)
    {
        // Filtro por rango de fechas entre 'start_date' y 'end_date' en el campo 'created_at'
        return $query->whereBetween('created_at', [$start_date, $end_date]);
    }
}
