<x-layouts.workspace title="Live-session lobby" description="Wait for the QuickQuiz host to begin the live session.">
    <section class="mx-auto max-w-3xl text-center" x-data x-init="setTimeout(() => window.location.reload(), 5000)">
        @if (session('status'))
            <p class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 font-semibold text-success" role="status">{{ session('status') }}</p>
        @endif
        <x-ui.badge tone="accent">You are in</x-ui.badge>
        <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">{{ $liveSession->quiz->title }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-lg leading-8 text-muted">{{ $liveSession->host->name }} is preparing the room. Keep this page open; it refreshes automatically.</p>

        <x-ui.panel class="mt-8 p-8 sm:p-12">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-accent-100 text-2xl font-black text-accent-700" aria-hidden="true">✓</span>
            <h2 class="mt-5 text-2xl font-black">Ready in the lobby</h2>
            <p class="mt-2 text-muted">The first question will appear here when the host starts Round 5C gameplay.</p>
            <p class="mt-6 text-sm font-bold text-brand-700">Room {{ $liveSession->code }}</p>
        </x-ui.panel>
    </section>
</x-layouts.workspace>
