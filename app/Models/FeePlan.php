<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeePlan extends Model
{
    protected $fillable = ['name', 'type', 'amount', 'classes_count', 'notes', 'active'];

    public const TYPES = [
        'monthly' => 'Monthly',
        'term' => 'Per term',
        'pack' => 'Class pack',
        'one_time' => 'One-time',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
