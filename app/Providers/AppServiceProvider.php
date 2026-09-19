<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn (): Password => Password::min(10)
            ->letters()
            ->mixedCase()
            ->numbers());

        Gate::define(
            'access-educator-workspace',
            fn (User $user): bool => $user->hasRole(UserRole::Educator),
        );

        Gate::define(
            'access-learner-workspace',
            fn (User $user): bool => $user->hasRole(UserRole::Learner),
        );

    }
}
