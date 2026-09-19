<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Your QuickQuiz workspace.">
        <meta name="theme-color" content="#4f46e5">
        <title>Workspace · {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <header class="border-b border-line bg-white">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-5 px-5 py-4 sm:px-8" aria-label="Workspace navigation">
                <a href="{{ route('dashboard') }}" aria-label="QuickQuiz workspace"><x-brand.mark /></a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Sign out</x-ui.button>
                </form>
            </nav>
        </header>

        <main class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16">
            <div class="max-w-3xl">
                <x-ui.badge tone="accent">Round 2A foundation</x-ui.badge>
                <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Welcome, {{ auth()->user()->name }}.</h1>
                <p class="mt-4 text-lg leading-8 text-muted">Your secure workspace is ready. Role-specific educator and learner tools arrive in the next round.</p>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <x-ui.panel class="p-6"><p class="text-sm font-black uppercase tracking-wider text-brand-700">Account</p><p class="mt-3 font-bold">Authenticated securely</p></x-ui.panel>
                <x-ui.panel class="p-6"><p class="text-sm font-black uppercase tracking-wider text-accent-700">Workspace</p><p class="mt-3 font-bold">Ready for role setup</p></x-ui.panel>
                <x-ui.panel class="p-6"><p class="text-sm font-black uppercase tracking-wider text-warning">Next</p><p class="mt-3 font-bold">Educator and learner paths</p></x-ui.panel>
            </div>
        </main>
    </body>
</html>
