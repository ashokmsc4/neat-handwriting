<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Skill extends Model
{
    protected $fillable = ['level_id', 'name', 'sort_order'];

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
}
