@props([
    'code',
    'title',
    'message',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="system-theme">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#4f46e5">
        <title>{{ $code }} · {{ $title }} · {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas px-5 py-10 text-ink antialiased">
        <main class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-3xl items-center justify-center">
            <section class="w-full rounded-3xl border border-line bg-white p-8 text-center shadow-soft sm:p-14">
                <a class="inline-flex" href="{{ route('landing') }}" aria-label="QuickQuiz home"><x-brand.mark /></a>
                <p class="mt-10 text-sm font-black uppercase tracking-[0.24em] text-brand-700">Error {{ $code }}</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">{{ $title }}</h1>
                <p class="mx-auto mt-4 max-w-xl text-lg leading-8 text-muted">{{ $message }}</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <x-ui.button href="{{ route('landing') }}">Go home</x-ui.button>
                    @auth<x-ui.button href="{{ route('dashboard') }}" variant="secondary">Open workspace</x-ui.button>@endauth
                </div>
            </section>
        </main>
    </body>
</html>
