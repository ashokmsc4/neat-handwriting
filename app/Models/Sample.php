<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** A photo of a student's handwriting on a given date. */
class Sample extends Model
{
    protected $fillable = ['student_id', 'skill_id', 'date', 'image_path', 'caption'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function thumbPath(): string
    {
        return preg_replace('/\.jpg$/', '_thumb.jpg', $this->image_path);
    }

    public function url(bool $thumb = false): string
    {
        return route('samples.image', array_filter([$this, 'size' => $thumb ? 'thumb' : null]));
    }

    protected static function booted(): void
    {
        static::deleted(fn (Sample $sample) => Storage::disk('local')->delete([$sample->image_path, $sample->thumbPath()]));
    }
}
