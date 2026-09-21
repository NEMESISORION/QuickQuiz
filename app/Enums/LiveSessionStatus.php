<?php

namespace App\Enums;

enum LiveSessionStatus: string
{
    case Waiting = 'waiting';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Waiting room',
            self::Active => 'Live',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function acceptsParticipants(): bool
    {
        return in_array($this, [self::Waiting, self::Active], true);
    }
}
