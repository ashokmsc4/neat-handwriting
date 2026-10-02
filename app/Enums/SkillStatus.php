<?php

namespace App\Enums;

enum SkillStatus: string
{
    case NotStarted = 'not_started';
    case Practising = 'practising';
    case Mastered = 'mastered';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::Practising => 'Practising',
            self::Mastered => 'Mastered',
        };
    }
}
