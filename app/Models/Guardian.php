<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'name', 'phone', 'whatsapp', 'email', 'address'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /** International digits-only number for wa.me links; falls back to the phone number. */
    public function whatsappNumber(): string
    {
        $digits = ltrim(preg_replace('/\D+/', '', $this->whatsapp ?: $this->phone), '0');

        return strlen($digits) === 10 ? config('school.phone_country_code').$digits : $digits;
    }
}
