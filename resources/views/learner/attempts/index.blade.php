<x-layouts.workspace title="Attempt history" description="Your QuickQuiz assessment attempts and results.">
    <section class="mx-auto max-w-5xl">
        <x-ui.badge tone="accent">Your progress</x-ui.badge><h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Attempt history</h1><p class="mt-4 max-w-2xl text-lg leading-8 text-muted">Resume active work or revisit results from completed assessments.</p>

        <div class="mt-8 flex flex-wrap gap-2" aria-label="Filter attempt history">
            @foreach ($filters as $availableFilter)
                <a href="{{ route('learner.attempts.index', ['status' => $availableFilter->value]) }}" class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-bold transition {{ $filter === $availableFilter ? 'border-brand-600 bg-brand-600 text-white' : 'border-line bg-white text-ink hover:border-brand-300' }}" @if ($filter === $availableFilter) aria-current="page" @endif>{{ $availableFilter->label() }}</a>
            @endforeach
        </div>

        <div class="mt-6 grid gap-4">
            @forelse ($attempts as $attempt)
                @php
                    $isActive = $attempt->status === \App\Enums\QuizAttemptStatus::InProgress;
                    $statusLabel = $isActive ? 'In progress' : ($attempt->status === \App\Enums\QuizAttemptStatus::Expired ? 'Time expired' : ($attempt->passed ? 'Passed' : 'Not passed'));
                    $statusTone = $isActive ? 'brand' : ($attempt->passed ? 'success' : 'warning');
                @endphp
                <article class="grid gap-5 rounded-2xl border border-line bg-white p-5 shadow-sm sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                    <div><div class="flex flex-wrap items-center gap-3"><x-ui.badge :tone="$statusTone">{{ $statusLabel }}</x-ui.badge><span class="text-sm font-bold text-muted">Attempt {{ $attempt->attempt_number }}</span></div><h2 class="mt-3 text-xl font-black">{{ $attempt->quiz->title }}</h2><p class="mt-2 text-sm font-semibold text-muted">Started {{ $attempt->started_at->format('M j, Y · g:i A') }} @if (! $isActive) · {{ $attempt->score }} / {{ $attempt->max_score }} points @endif</p></div>
                    <x-ui.button href="{{ $isActive ? route('learner.attempts.show', $attempt) : route('learner.attempts.result', $attempt) }}" :variant="$isActive ? 'primary' : 'secondary'">{{ $isActive ? 'Resume attempt' : 'View result' }}</x-ui.button>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-line bg-white/70 p-10 text-center"><h2 class="text-xl font-black">No matching attempts</h2><p class="mt-2 text-muted">Start with an available assessment when you are ready.</p><div class="mt-5"><x-ui.button href="{{ route('learner.quizzes.index') }}">Discover assessments</x-ui.button></div></div>
            @endforelse
        </div>
        @if ($attempts->hasPages())<div class="mt-6">{{ $attempts->links() }}</div>@endif
    </section>
</x-layouts.workspace>
