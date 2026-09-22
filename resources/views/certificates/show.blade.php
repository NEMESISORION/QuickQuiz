<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Verify a QuickQuiz completion certificate.">
        <meta name="robots" content="noindex, nofollow">
        <title>Certificate · {{ $attempt->quiz->title }} · {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-canvas px-5 py-8 text-ink antialiased print:bg-white print:p-0">
        <main class="mx-auto max-w-5xl">
            <div class="mb-5 flex items-center justify-between gap-4 print:hidden">
                <a href="{{ route('landing') }}" aria-label="QuickQuiz home"><x-brand.mark /></a>
                <button type="button" data-print-page class="inline-flex min-h-11 items-center justify-center rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">Print or save PDF</button>
            </div>

            <article class="relative overflow-hidden rounded-3xl border-2 border-brand-200 bg-white p-8 shadow-xl print:min-h-[96vh] print:rounded-none print:border-4 print:p-16 print:shadow-none sm:p-14">
                <div class="absolute -right-16 -top-16 size-52 rounded-full bg-brand-100/70"></div>
                <div class="absolute -bottom-20 -left-20 size-64 rounded-full bg-accent-100/60"></div>
                <div class="relative text-center">
                    <x-ui.badge tone="success">Verified achievement</x-ui.badge>
                    <p class="mt-8 text-sm font-black uppercase tracking-[0.28em] text-brand-700">Certificate of completion</p>
                    <h1 class="mt-6 text-4xl font-black tracking-tight sm:text-6xl">{{ $attempt->learner->name }}</h1>
                    <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-muted">successfully completed the QuickQuiz assessment</p>
                    <h2 class="mx-auto mt-3 max-w-3xl text-3xl font-black sm:text-4xl">{{ $attempt->quiz->title }}</h2>

                    <div class="mx-auto mt-10 grid max-w-2xl gap-4 sm:grid-cols-3">
                        <div class="rounded-2xl bg-brand-50 p-5"><p class="text-xs font-black uppercase tracking-wider text-muted">Score</p><p class="mt-2 text-2xl font-black text-brand-700">{{ $percentage }}%</p></div>
                        <div class="rounded-2xl bg-brand-50 p-5"><p class="text-xs font-black uppercase tracking-wider text-muted">Points</p><p class="mt-2 text-2xl font-black text-brand-700">{{ $attempt->score }} / {{ $attempt->max_score }}</p></div>
                        <div class="rounded-2xl bg-brand-50 p-5"><p class="text-xs font-black uppercase tracking-wider text-muted">Issued</p><p class="mt-2 text-lg font-black text-brand-700">{{ $certificate->issued_at->format('M j, Y') }}</p></div>
                    </div>

                    <div class="mt-12 border-t border-line pt-7">
                        <p class="text-xs font-bold uppercase tracking-wider text-muted">Public verification code</p>
                        <p class="mt-2 break-all font-mono text-sm font-bold">{{ $certificate->verification_code }}</p>
                        <p class="mt-3 text-sm text-muted">This page is the live verification record for this certificate.</p>
                    </div>
                </div>
            </article>
        </main>
    </body>
</html>
