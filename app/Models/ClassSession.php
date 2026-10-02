<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One held (or planned) class of a batch on a given date. */
class ClassSession extends Model
{
    protected $fillable = ['batch_id', 'date', 'start_time', 'status', 'topic_note'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
