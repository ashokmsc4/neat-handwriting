<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Left = 'left';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Paused => 'Paused',
            self::Left => 'Left',
        };
    }
}
