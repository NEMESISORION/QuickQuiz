@props([
    'description' => 'Your QuickQuiz workspace.',
    'title' => 'Workspace',
])

@php
    $currentUser = request()->user();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $currentUser->theme_preference->htmlClass() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description }}">
        <meta name="theme-color" content="#4f46e5">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} · {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <a href="#main-content" class="sr-only fixed left-4 top-4 z-50 rounded-lg bg-ink px-4 py-3 font-semibold text-white focus:not-sr-only">Skip to main content</a>

        <header class="border-b border-line bg-white/95 backdrop-blur">
            <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8" aria-label="Workspace navigation">
                <a href="{{ route('dashboard') }}" aria-label="QuickQuiz workspace"><x-brand.mark /></a>

                <div class="flex w-full min-w-0 max-w-full flex-wrap items-center justify-start gap-2 sm:w-auto sm:justify-end sm:gap-3">
                    @if ($currentUser->role->value === 'educator')
                        <a href="{{ route('educator.quizzes.index') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-ink transition hover:bg-brand-50 hover:text-brand-700" @if (request()->routeIs('educator.quizzes.*')) aria-current="page" @endif>
                            Quizzes
                        </a>
                        <a href="{{ route('educator.analytics.index') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-ink transition hover:bg-brand-50 hover:text-brand-700" @if (request()->routeIs('educator.analytics.*')) aria-current="page" @endif>
                            Analytics
                        </a>
                    @else
                        <a href="{{ route('learner.live-sessions.create') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-ink transition hover:bg-brand-50 hover:text-brand-700" @if (request()->routeIs('learner.live-sessions.*')) aria-current="page" @endif>
                            Join live
                        </a>
                        <a href="{{ route('learner.quizzes.index') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-ink transition hover:bg-brand-50 hover:text-brand-700" @if (request()->routeIs('learner.quizzes.*')) aria-current="page" @endif>
                            Discover
                        </a>
                        <a href="{{ route('learner.attempts.index') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-ink transition hover:bg-brand-50 hover:text-brand-700" @if (request()->routeIs('learner.attempts.*')) aria-current="page" @endif>
                            History
                        </a>
                    @endif
                    <span class="hidden max-w-48 truncate text-sm font-bold text-ink md:inline" title="{{ $currentUser->name }}">{{ $currentUser->name }}</span>
                    <x-ui.badge tone="{{ $currentUser->role->value === 'educator' ? 'brand' : 'accent' }}">
                        {{ $currentUser->role->label() }}
                    </x-ui.badge>
                    <a href="{{ route('notifications.index') }}" class="relative inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-ink transition hover:bg-brand-50 hover:text-brand-700" @if (request()->routeIs('notifications.*')) aria-current="page" @endif>
                        Alerts
                        @if (($unreadNotificationCount = $currentUser->unreadNotifications()->count()) > 0)
                            <span class="ml-1.5 inline-flex min-w-5 items-center justify-center rounded-full bg-danger px-1.5 py-0.5 text-xs font-black text-white" aria-label="{{ $unreadNotificationCount }} unread notifications">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('profile.edit') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-bold text-ink transition hover:bg-brand-50 hover:text-brand-700" @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                        Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" class="px-3 sm:px-4">Sign out</x-ui.button>
                    </form>
                </div>
            </nav>
        </header>

        <main id="main-content" class="mx-auto max-w-7xl px-5 py-10 sm:px-8 sm:py-14">
            {{ $slot }}
        </main>
    </body>
</html>
