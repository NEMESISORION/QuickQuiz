<?php

namespace App\Enums;

enum QuizReviewPolicy: string
{
    case Immediately = 'immediately';
    case AfterClose = 'after_close';
    case Never = 'never';

    public function label(): string
    {
        return match ($this) {
            self::Immediately => 'Immediately after submission',
            self::AfterClose => 'After the quiz closes',
            self::Never => 'Do not reveal answers',
        };
    }
}
