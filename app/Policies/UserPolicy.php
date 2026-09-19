<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function update(User $user, User $model): bool
    {
        return $user->is($model);
    }

    public function viewSecurityActivity(User $user, User $model): bool
    {
        return $user->is($model);
    }
}
