<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\LiveSession;
use App\Models\User;

class LiveSessionPolicy
{
    public function viewHostLobby(User $user, LiveSession $liveSession): bool
    {
        return $user->hasRole(UserRole::Educator)
            && $liveSession->host_id === $user->getKey();
    }

    public function viewLearnerLobby(User $user, LiveSession $liveSession): bool
    {
        return $user->hasRole(UserRole::Learner)
            && $liveSession->participants()->whereBelongsTo($user, 'learner')->exists();
    }

    public function control(User $user, LiveSession $liveSession): bool
    {
        return $this->viewHostLobby($user, $liveSession);
    }

    public function answer(User $user, LiveSession $liveSession): bool
    {
        return $this->viewLearnerLobby($user, $liveSession);
    }

    public function viewState(User $user, LiveSession $liveSession): bool
    {
        return $this->viewHostLobby($user, $liveSession)
            || $this->viewLearnerLobby($user, $liveSession);
    }
}
