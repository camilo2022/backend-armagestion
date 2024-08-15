<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payments extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'model_type',
        'model_id',
        'pay_account',
        'pay_value',
        'pay_discount_rate',
        'pay_date',
        'cycle_id',
        'focus_id'
    ];

     /**
     * The function "campaign" returns the relationship between the current object and a Campaign
     * object.
     */
    public function model() : MorphTo
    {
        return $this->morphTo();
    }

    public function cycle() : BelongsTo
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    public function focus() : BelongsTo
    {
        return $this->belongsTo(Focus::class, 'focus_id');
    }

    public function campaign() : BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'model_id');
    }

    public function assignment() : BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'model_id');
    }

    /**
     * The function is a scope in a PHP class that allows searching for records based on matching
     * values in multiple columns and related models.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(
            function ($paymentQuery) use ($search) {
                // Busco en la base de datos por coincidencias en "focus_name" y "focus_description".
                $paymentQuery->where('pay_account', '=', $search)
                    ->orWhere('pay_value', '=', $search);
            }
        )
        // Filtra por nombre de la camapana asignada
        ->orWhereHas('campaign',
            function ($campaignQuery) use ($search) {
                $campaignQuery->where('camp_name', 'like', '%' . $search . '%');
            }
        )
        ->orWhereHas('focus',
            function ($focusQuery) use ($search) {
                $focusQuery->where('focus_name', 'like', '%' . $search . '%');
            }
        )
        ->orWhereHas('assignment',
            function ($assignmentQuery) use ($search) {
                $assignmentQuery->where('assi_name', 'like', '%' . $search . '%');
            }
        );
    }

    public function scopeFilterByDate($query, $start_date, $end_date)
    {
        // Filtro por rango de fechas entre 'start_date' y 'end_date' en el campo 'pay_date' o 'created_at'
        return $query->whereBetween('pay_date', [$start_date, $end_date])
            ->orWhereBetween('created_at', [$start_date, $end_date]);
    }
}
