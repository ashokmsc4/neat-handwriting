<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'course_id', 'level_id', 'fee_plan_id', 'name', 'mode', 'meeting_link', 'capacity', 'start_date', 'end_date', 'active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function feePlan(): BelongsTo
    {
        return $this->belongsTo(FeePlan::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(BatchSchedule::class)->orderBy('weekday')->orderBy('start_time');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'enrollments')
            ->wherePivot('status', 'active')
            ->withPivot(['status', 'start_date', 'end_date', 'fee_plan_id'])
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }
}
