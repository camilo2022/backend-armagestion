<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimePattern extends Model
{
    use HasFactory;

    protected $table = 'time_patterns';

    protected $primaryKey = 'id_time_patterns';

    public $timestamps = false;

    protected $fillable = [
        'id_function',
        'objects_8_in_10',
        'objects_16_in_17',
        'objects_12_in_13',
        'objects_15_in_16',
        'objects_11_in_13',
        'objects_08_in_14',
        'objects_15_in_18',
        'objects_13_in_17',
        'objects_08_in_13',
        'objects_16_in_17_50',
        'id_configurations',
    ];

    protected $casts = [
        'objects_8_in_10' => 'array',
        'objects_16_in_17' => 'array',
        'objects_12_in_13' => 'array',
        'objects_15_in_16' => 'array',
        'objects_11_in_13' => 'array',
        'objects_08_in_14' => 'array',
        'objects_15_in_18' => 'array',
        'objects_13_in_17' => 'array',
        'objects_08_in_13' => 'array',
        'objects_16_in_17_50' => 'array',
    ];

    public function function()
    {
        return $this->belongsTo(FunctionPattern::class, 'id_function');
    }

    public function configuration()
    {
        return $this->belongsTo(Configuration::class, 'id_configurations');
    }
}
