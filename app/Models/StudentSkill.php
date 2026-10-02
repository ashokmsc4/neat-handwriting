<?php

namespace App\Models;

use App\Enums\SkillStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSkill extends Model
{
    protected $fillable = ['student_id', 'skill_id', 'status', 'updated_on'];

    protected function casts(): array
    {
        return [
            'status' => SkillStatus::class,
            'updated_on' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
