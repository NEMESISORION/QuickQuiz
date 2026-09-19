@props([
    'eyebrow',
    'heading',
    'intro',
    'title',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Secure access to your QuickQuiz assessment workspace.">
        <meta name="theme-color" content="#4f46e5">

        <title>{{ $title }} · {{ config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas text-ink antialiased">
        <a href="#auth-form" class="sr-only fixed left-4 top-4 z-50 rounded-lg bg-ink px-4 py-3 font-semibold text-white focus:not-sr-only">
            Skip to form
        </a>

        <main class="grid min-h-screen lg:grid-cols-[minmax(0,0.9fr)_minmax(32rem,1.1fr)]">
            <section class="relative hidden overflow-hidden bg-brand-950 p-12 text-white lg:flex lg:flex-col lg:justify-between xl:p-16" aria-label="QuickQuiz product context">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_15%,rgba(99,102,241,0.5),transparent_30%),radial-gradient(circle_at_85%_80%,rgba(20,184,166,0.3),transparent_34%)]" aria-hidden="true"></div>

                <a href="{{ route('landing') }}" class="relative z-10 w-fit rounded-xl bg-white px-3 py-2">
                    <x-brand.mark />
                </a>

                <div class="relative z-10 max-w-lg">
                    <p class="text-sm font-black uppercase tracking-[0.2em] text-brand-200">Designed for thoughtful assessment</p>
                    <p class="mt-5 text-4xl font-black leading-tight tracking-[-0.03em]">A calm workspace for building, taking, and understanding quizzes.</p>
                    <div class="mt-8 grid grid-cols-3 gap-3 text-sm">
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur"><strong class="block">Create</strong><span class="mt-1 block text-brand-100">Clear authoring</span></div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur"><strong class="block">Focus</strong><span class="mt-1 block text-brand-100">Quiet attempts</span></div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur"><strong class="block">Learn</strong><span class="mt-1 block text-brand-100">Useful results</span></div>
                    </div>
                </div>

                <p class="relative z-10 text-sm text-brand-200">QuickQuiz v2 · Secure, accessible, deliberate.</p>
            </section>

            <section class="flex min-w-0 items-center justify-center px-5 py-10 sm:px-8 lg:px-14">
                <div id="auth-form" class="w-full max-w-md">
                    <a href="{{ route('landing') }}" class="mb-10 inline-flex lg:hidden" aria-label="QuickQuiz home">
                        <x-brand.mark />
                    </a>

                    <header class="flex flex-col gap-3">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-brand-700">{{ $eyebrow }}</p>
                        <h1 class="text-3xl font-black tracking-tight text-ink sm:text-4xl">{{ $heading }}</h1>
                        <p class="text-base leading-7 text-muted">{{ $intro }}</p>
                    </header>

                    @if (session('status'))
                        <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-success" role="status">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="mt-8">
                        {{ $slot }}
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
