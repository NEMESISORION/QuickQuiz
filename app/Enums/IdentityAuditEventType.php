<?php

namespace App\Enums;

enum IdentityAuditEventType: string
{
    case Registered = 'identity.registered';
    case LoginSucceeded = 'identity.login_succeeded';
    case LoginFailed = 'identity.login_failed';
    case LoggedOut = 'identity.logged_out';
    case PasswordReset = 'identity.password_reset';
    case PasswordChanged = 'identity.password_changed';
    case EmailVerified = 'identity.email_verified';
    case RoleSelected = 'identity.role_selected';
    case ProfileUpdated = 'identity.profile_updated';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Account created',
            self::LoginSucceeded => 'Signed in',
            self::LoginFailed => 'Failed sign-in attempt',
            self::LoggedOut => 'Signed out',
            self::PasswordReset => 'Password reset',
            self::PasswordChanged => 'Password changed',
            self::EmailVerified => 'Email verified',
            self::RoleSelected => 'Workspace selected',
            self::ProfileUpdated => 'Profile updated',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Registered => 'Your QuickQuiz account was created.',
            self::LoginSucceeded => 'Your account was accessed with valid credentials.',
            self::LoginFailed => 'A sign-in attempt was rejected.',
            self::LoggedOut => 'A signed-in session ended.',
            self::PasswordReset => 'Your account password was changed through recovery.',
            self::PasswordChanged => 'Your account password was changed from profile settings.',
            self::EmailVerified => 'Your email ownership was confirmed.',
            self::RoleSelected => 'Your permanent workspace role was selected.',
            self::ProfileUpdated => 'Your account profile was changed.',
        };
    }
}
