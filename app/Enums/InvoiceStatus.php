<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Due = 'due';
    case Partial = 'partial';
    case Paid = 'paid';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Due => 'Due',
            self::Partial => 'Part paid',
            self::Paid => 'Paid',
            self::Waived => 'Waived',
        };
    }
}
