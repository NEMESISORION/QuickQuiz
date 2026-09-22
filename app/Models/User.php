<?php

namespace App\Models;

use App\Enums\ThemePreference;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property UserRole|null $role
 * @property Carbon|null $role_selected_at
 */
#[Fillable(['name', 'email', 'password', 'theme_preference', 'notifications_enabled'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'role_selected_at' => 'datetime',
            'theme_preference' => ThemePreference::class,
            'notifications_enabled' => 'boolean',
        ];
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * @return HasMany<IdentityAuditEvent, $this>
     */
    public function identityAuditEvents(): HasMany
    {
        return $this->hasMany(IdentityAuditEvent::class);
    }

    /**
     * @return HasMany<Quiz, $this>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'educator_id');
    }

    /** @return HasMany<QuizAttempt, $this> */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'learner_id');
    }

    /** @return HasMany<LiveSession, $this> */
    public function hostedLiveSessions(): HasMany
    {
        return $this->hasMany(LiveSession::class, 'host_id');
    }

    /** @return HasMany<LiveSessionParticipant, $this> */
    public function liveSessionParticipations(): HasMany
    {
        return $this->hasMany(LiveSessionParticipant::class, 'learner_id');
    }
}
