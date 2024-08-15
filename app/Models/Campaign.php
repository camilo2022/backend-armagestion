<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    use HasFactory;

    protected $table = 'campaigns';

    protected $fillable = [
        'camp_id',
        'csts_id',
        'camp_name',
        'user_id',
        'camp_description',
        'camp_status'
    ];

    // Convertir peop_status en un valor booleano
    protected $casts = [
        'camp_status' => 'boolean',
    ];

    /**
     * The function returns a relationship between the current object and a User object based on the
     * user_id attribute.
     */

    public function ally() : BelongsTo
    {
        return $this->belongsTo(Ally::class, 'alli_id');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('camp_name', 'like', '%' . $search . '%')
            ->orWhere('camp_description', 'like', '%' . $search . '%');
    }

    public function scopeFilterByDate($query, $start_date, $end_date)
    {
        // Filtro por rango de fechas entre 'start_date' y 'end_date' en el campo 'created_at'
        return $query->whereBetween('created_at', [$start_date, $end_date]);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
