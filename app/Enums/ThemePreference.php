<?php

namespace App\Enums;

enum ThemePreference: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    public function label(): string
    {
        return match ($this) {
            self::System => 'Use device setting',
            self::Light => 'Light',
            self::Dark => 'Dark',
        };
    }

    public function htmlClass(): string
    {
        return match ($this) {
            self::System => 'system-theme',
            self::Light => 'light',
            self::Dark => 'dark',
        };
    }
}
