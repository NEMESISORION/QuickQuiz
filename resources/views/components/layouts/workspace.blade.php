@props([
    'description' => 'Your QuickQuiz workspace.',
    'title' => 'Workspace',
])

@php
    $currentUser = request()->user();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="{{ $description }}">
        <meta name="theme-color" content="#4f46e5">
        <title>{{ $title }} · {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <a href="#main-content" class="sr-only fixed left-4 top-4 z-50 rounded-lg bg-ink px-4 py-3 font-semibold text-white focus:not-sr-only">Skip to main content</a>

        <header class="border-b border-line bg-white/95 backdrop-blur">
            <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8" aria-label="Workspace navigation">
                <a href="{{ route('dashboard') }}" aria-label="QuickQuiz workspace"><x-brand.mark /></a>

                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="hidden max-w-48 truncate text-sm font-bold text-ink md:inline" title="{{ $currentUser->name }}">{{ $currentUser->name }}</span>
                    <x-ui.badge tone="{{ $currentUser->role->value === 'educator' ? 'brand' : 'accent' }}">
                        {{ $currentUser->role->label() }}
                    </x-ui.badge>
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
