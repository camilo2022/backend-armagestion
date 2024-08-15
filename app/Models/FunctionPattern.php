<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FunctionPattern extends Model
{
    use HasFactory;

    protected $table = 'function';

    protected $fillable = [
        'descripcion',
    ];

    public function time_patterns()
    {
        return $this->hasMany(TimePattern::class);
    }
}
