<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasFactory;

    protected $table = 'people'; 

    protected $fillable = [
        'peop_name',
        'peop_last_name',
        'peop_dni',
        'peop_status',
    ];

    // Convertir peop_status en un valor booleano
    protected $casts = [
        'peop_status' => 'boolean',
    ];
    
}
