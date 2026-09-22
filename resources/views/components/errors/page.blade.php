@props([
    'code',
    'title',
    'message',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#4f46e5">
        <title>{{ $code }} · {{ $title }} · {{ config('app.name') }}</title>
        <link rel="stylesheet" href="{{ asset('error.css') }}">
    </head>
    <body>
        <main class="error-shell">
            <section class="error-card">
                <a class="error-brand" href="{{ route('landing') }}" aria-label="QuickQuiz home">
                    <span class="error-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M13.7 2.75 5.5 13.1h5.35l-.7 8.15 8.35-11.1h-5.45l.65-7.4Z" fill="currentColor" /></svg>
                    </span>
                    <span>QuickQuiz</span>
                </a>
                <p class="error-code">Error {{ $code }}</p>
                <h1>{{ $title }}</h1>
                <p class="error-message">{{ $message }}</p>
                <div class="error-actions">
                    <a class="error-button error-button-primary" href="{{ route('landing') }}">Go home</a>
                    @auth<a class="error-button error-button-secondary" href="{{ route('dashboard') }}">Open workspace</a>@endauth
                </div>
            </section>
        </main>
    </body>
</html>
