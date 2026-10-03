<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Teacher-editable settings (class name, receipt details, fee due day).
 * Falls back to config/school.php when a value hasn't been saved yet.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'school_name' => 'school.name',
        'school_phone' => null,
        'school_address' => null,
        'fee_due_day' => 10,
        'receipt_prefix' => 'NH',
    ];

    public static function get(string $key): mixed
    {
        $all = Cache::rememberForever('settings', fn () => static::pluck('value', 'key')->all());

        if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '') {
            return $all[$key];
        }

        $default = self::DEFAULTS[$key] ?? null;

        return is_string($default) && str_contains($default, '.') ? config($default) : $default;
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings');
    }
}
