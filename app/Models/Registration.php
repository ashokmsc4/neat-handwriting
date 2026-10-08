<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Registration extends Model
{
    protected $fillable = [
        'child_name', 'dob', 'school', 'grade', 'course_ids', 'preferred_time', 'notes',
        'guardian_name', 'phone', 'whatsapp', 'email', 'address', 'photo_consent', 'status', 'student_id',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'course_ids' => 'array',
            'photo_consent' => 'boolean',
        ];
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'new');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function courses(): Collection
    {
        return Course::whereIn('id', $this->course_ids ?? [])->orderBy('name')->get();
    }

    /** The secret part of the public link; changing it turns off the old link. */
    public static function token(): string
    {
        $token = Setting::get('registration_token');

        if (! $token) {
            $token = static::newToken();
        }

        return $token;
    }

    public static function newToken(): string
    {
        $token = Str::lower(Str::random(10));
        Setting::put(['registration_token' => $token]);

        return $token;
    }

    public static function isOpen(): bool
    {
        return Setting::get('registration_open') !== '0';
    }
}
