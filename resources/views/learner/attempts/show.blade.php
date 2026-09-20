<x-layouts.workspace :title="$attempt->quiz->title" description="Secure assessment attempt prepared by QuickQuiz.">
    @if (session('status'))<div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-success" role="status">{{ session('status') }}</div>@endif
    <section class="mx-auto max-w-3xl text-center">
        <x-ui.badge tone="success">Attempt {{ $attempt->attempt_number }} ready</x-ui.badge>
        <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">{{ $attempt->quiz->title }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-lg leading-8 text-muted">Your secure attempt has been created with a fixed question snapshot. The focused answering experience arrives in Round 4B.</p>
        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <x-ui.panel class="p-5"><p class="text-sm font-bold text-muted">Questions</p><p class="mt-2 text-2xl font-black">{{ $attempt->questions->count() }}</p></x-ui.panel>
            <x-ui.panel class="p-5"><p class="text-sm font-bold text-muted">Maximum score</p><p class="mt-2 text-2xl font-black">{{ $attempt->max_score }}</p></x-ui.panel>
            <x-ui.panel class="p-5"><p class="text-sm font-bold text-muted">Time window</p><p class="mt-2 text-sm font-black">{{ $attempt->expires_at ? 'Until '.$attempt->expires_at->format('g:i A') : 'No limit' }}</p></x-ui.panel>
        </div>
        <p class="mt-8 text-sm font-semibold text-muted">Started {{ $attempt->started_at->format('M j, Y · g:i A') }}. The server controls this timing.</p>
    </section>
</x-layouts.workspace>
