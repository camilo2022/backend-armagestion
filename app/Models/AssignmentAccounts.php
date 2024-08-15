<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentAccounts extends Model
{
    use HasFactory;

    protected $table = 'assignments_accounts';

    public function alli() : BelongsTo
    {
        return $this->belongsTo(Ally::class, 'alli_id');
    }

    public function assi() : BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assi_id');
    }

    public function camp() : BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'camp_id');
    }

    public function foal() : BelongsTo
    {
        return $this->belongsTo(Focus::class, 'foal_id');
    }
}
