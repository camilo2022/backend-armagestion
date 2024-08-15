<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ally extends Model
{
    use HasFactory;

    protected $table = 'allies';

    protected $fillable = [
        'alli_name'
    ];

    public function focus() : HasMany
    {
        return $this->hasMany(Focus::class, 'alli_id');
    }

    /**
     * The scopeSearch function searches the database for records that have a matching value in either
     * the "focus_name" or "focus_description" columns.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where('alli_name', 'like', '%' . $search . '%');
    }

    public function scopeFilterByDate($query, $start_date, $end_date)
    {
        // Filtro por rango de fechas entre 'start_date' y 'end_date' en el campo 'created_at'
        return $query->whereBetween('created_at', [$start_date, $end_date]);
    }
}
