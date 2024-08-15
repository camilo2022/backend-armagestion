<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class exclusionManagerFija extends Model
{
    use HasFactory;

    protected $table = 'exclusions_managers_fija';

    protected $fillable = [
        'id_exclusion',
        'status',
        'document_id'
    ];
}
