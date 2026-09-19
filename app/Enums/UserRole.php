<?php

namespace App\Enums;

enum UserRole: string
{
    case Educator = 'educator';
    case Learner = 'learner';

    public function label(): string
    {
        return match ($this) {
            self::Educator => 'Educator',
            self::Learner => 'Learner',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Educator => 'Create assessments, guide learners, and review meaningful results.',
            self::Learner => 'Take focused assessments and turn feedback into your next step.',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Educator => 'educator.dashboard',
            self::Learner => 'learner.dashboard',
        };
    }
}
