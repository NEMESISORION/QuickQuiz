<x-layouts.workspace :title="$quiz->title" :description="$quiz->description ?: 'QuickQuiz assessment overview.'">
    <nav aria-label="Breadcrumb" class="text-sm font-bold text-muted"><a href="{{ route('learner.quizzes.index') }}" class="hover:text-brand-700">Discover</a><span aria-hidden="true" class="px-2">/</span><span class="text-ink">{{ $quiz->title }}</span></nav>

    <section class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div>
            <x-ui.badge :tone="$isOpen ? 'success' : 'warning'">{{ $isOpen ? 'Open now' : 'Upcoming' }}</x-ui.badge>
            <h1 class="mt-4 max-w-4xl text-4xl font-black tracking-tight sm:text-5xl">{{ $quiz->title }}</h1>
            <p class="mt-4 max-w-3xl text-lg leading-8 text-muted">{{ $quiz->description ?: 'Review the assessment rules before you begin.' }}</p>

            @error('quiz')<div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-danger" role="alert">{{ $message }}</div>@enderror

            <x-ui.panel class="mt-8 p-6 sm:p-8">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-700">Before you begin</p>
                @if ($activeAttempt)
                    <h2 class="mt-2 text-2xl font-black">Your attempt is already in progress.</h2><p class="mt-2 leading-7 text-muted">Continue the same secure attempt without using another one.</p><div class="mt-6"><x-ui.button href="{{ route('learner.attempts.show', $activeAttempt) }}">Resume attempt</x-ui.button></div>
                @elseif ($isOpen && $attemptsRemaining > 0)
                    <h2 class="mt-2 text-2xl font-black">Ready when you are.</h2><p class="mt-2 leading-7 text-muted">The timer starts only after you confirm. Closing the browser will not reset it.</p>
                    <form method="POST" action="{{ route('learner.quizzes.attempts.store', $quiz) }}" class="mt-6">@csrf<x-ui.button type="submit">Start attempt</x-ui.button></form>
                @elseif ($isUpcoming)
                    <h2 class="mt-2 text-2xl font-black">This assessment is not open yet.</h2><p class="mt-2 leading-7 text-muted">It opens {{ $quiz->opens_at->format('M j, Y · g:i A') }}.</p>
                @else
                    <h2 class="mt-2 text-2xl font-black">No attempts remaining.</h2><p class="mt-2 leading-7 text-muted">You have reached the attempt limit for this assessment.</p>
                @endif
            </x-ui.panel>

            @if ($latestResult)
                <div class="mt-5 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-line bg-white px-5 py-4">
                    <div><p class="font-black">Latest result</p><p class="mt-1 text-sm font-semibold text-muted">Attempt {{ $latestResult->attempt_number }} · {{ $latestResult->submitted_at?->diffForHumans() }}</p></div>
                    <x-ui.button href="{{ route('learner.attempts.result', $latestResult) }}" variant="secondary">View result</x-ui.button>
                </div>
            @endif
        </div>
        <aside class="lg:sticky lg:top-6"><x-ui.panel class="p-6"><p class="text-xs font-black uppercase tracking-[0.16em] text-muted">Assessment rules</p><dl class="mt-5 flex flex-col gap-4 text-sm"><div class="flex justify-between gap-4"><dt class="text-muted">Questions</dt><dd class="font-bold">{{ $quiz->questions_count }}</dd></div><div class="flex justify-between gap-4"><dt class="text-muted">Time limit</dt><dd class="font-bold">{{ $quiz->duration_minutes ? $quiz->duration_minutes.' min' : 'None' }}</dd></div><div class="flex justify-between gap-4"><dt class="text-muted">Passing score</dt><dd class="font-bold">{{ $quiz->pass_percentage }}%</dd></div><div class="flex justify-between gap-4"><dt class="text-muted">Attempts left</dt><dd class="font-bold">{{ $attemptsRemaining }}</dd></div><div class="flex justify-between gap-4"><dt class="text-muted">Review</dt><dd class="max-w-40 text-right font-bold">{{ $quiz->review_policy->label() }}</dd></div></dl></x-ui.panel></aside>
    </section>
</x-layouts.workspace>
