<?php

namespace App\Listeners;

use App\Domain\Identity\IdentityAuditLogger;
use App\Enums\IdentityAuditEventType;
use App\Events\Identity\ProfileUpdated;
use App\Events\Identity\RoleSelected;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;

class RecordIdentityActivity
{
    public function __construct(private IdentityAuditLogger $logger) {}

    public function handleRegistered(Registered $event): void
    {
        $this->logger->record(IdentityAuditEventType::Registered, $this->user($event->user));
    }

    public function handleLogin(Login $event): void
    {
        $this->logger->record(IdentityAuditEventType::LoginSucceeded, $this->user($event->user));
    }

    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;
        $metadata = is_string($email)
            ? ['email_fingerprint' => hash_hmac('sha256', Str::lower(trim($email)), (string) config('app.key'))]
            : [];

        $this->logger->record(IdentityAuditEventType::LoginFailed, $this->user($event->user), $metadata);
    }

    public function handleLogout(Logout $event): void
    {
        $this->logger->record(IdentityAuditEventType::LoggedOut, $this->user($event->user));
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        $this->logger->record(IdentityAuditEventType::PasswordReset, $this->user($event->user));
    }

    public function handleVerified(Verified $event): void
    {
        $this->logger->record(IdentityAuditEventType::EmailVerified, $this->user($event->user));
    }

    public function handleRoleSelected(RoleSelected $event): void
    {
        $this->logger->record(
            IdentityAuditEventType::RoleSelected,
            $event->user,
            ['role' => $event->role->value],
        );
    }

    public function handleProfileUpdated(ProfileUpdated $event): void
    {
        $this->logger->record(
            IdentityAuditEventType::ProfileUpdated,
            $event->user,
            ['email_changed' => $event->emailChanged],
        );
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Registered::class => 'handleRegistered',
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
            PasswordReset::class => 'handlePasswordReset',
            Verified::class => 'handleVerified',
            RoleSelected::class => 'handleRoleSelected',
            ProfileUpdated::class => 'handleProfileUpdated',
        ];
    }

    private function user(mixed $user): ?User
    {
        return $user instanceof User ? $user : null;
    }
}
