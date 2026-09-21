<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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

        RateLimiter::for('live-session-join', function (Request $request): Limit {
            $identity = $request->user()?->getAuthIdentifier() ?? 'guest';

            return Limit::perMinute(10)->by(Str::lower((string) $identity).'|'.$request->ip());
        });
    }
}
