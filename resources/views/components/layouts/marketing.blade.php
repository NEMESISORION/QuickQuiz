@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="QuickQuiz helps educators build thoughtful assessments and learners understand their progress.">
        <meta name="theme-color" content="#4f46e5">

        <title>{{ $title ? $title.' · '.config('app.name') : config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen overflow-x-clip bg-canvas text-ink antialiased">
        <a href="#main-content" class="sr-only fixed left-4 top-4 z-50 rounded-lg bg-ink px-4 py-3 font-semibold text-white focus:not-sr-only">
            Skip to main content
        </a>

        <header class="border-b border-line/80 bg-white/90 backdrop-blur">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 sm:px-8" aria-label="Primary navigation">
                <a class="shrink-0" href="{{ route('landing') }}" aria-label="QuickQuiz home">
                    <x-brand.mark />
                </a>

                <div class="hidden items-center gap-8 text-sm font-semibold text-muted md:flex">
                    <a class="transition hover:text-brand-700" href="#experience">Experience</a>
                    <a class="transition hover:text-brand-700" href="#foundation">Foundation</a>
                    <a class="transition hover:text-brand-700" href="#principles">Principles</a>
                </div>

                <span class="hidden shrink-0 sm:inline-flex">
                    <x-ui.badge class="uppercase tracking-[0.14em]">Assessment, clarified</x-ui.badge>
                </span>
            </nav>
        </header>

        <main id="main-content">
            {{ $slot }}
        </main>

        <footer class="border-t border-line bg-white">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-8 text-sm text-muted sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <p>QuickQuiz v2 · Assessment without the noise.</p>
                <p>Built for clear decisions, focused attempts, and useful feedback.</p>
            </div>
        </footer>
    </body>
</html>
