<?php

namespace App\Enums;

enum QuizStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Closed => 'Closed',
            self::Archived => 'Archived',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::Draft => in_array($status, [self::Scheduled, self::Published, self::Archived], true),
            self::Scheduled => in_array($status, [self::Draft, self::Published, self::Closed, self::Archived], true),
            self::Published => in_array($status, [self::Closed, self::Archived], true),
            self::Closed => $status === self::Archived,
            self::Archived => false,
        };
    }
}
