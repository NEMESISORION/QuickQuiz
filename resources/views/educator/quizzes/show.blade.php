<x-layouts.workspace :title="$quiz->title" :description="$quiz->description ?: 'QuickQuiz assessment overview.'">
    @php
        $statusTone = match ($quiz->status) {
            \App\Enums\QuizStatus::Published => 'success',
            \App\Enums\QuizStatus::Scheduled => 'warning',
            \App\Enums\QuizStatus::Closed => 'accent',
            default => 'neutral',
        };
        $totalPoints = $quiz->questions->sum('points');
    @endphp

    <nav aria-label="Breadcrumb" class="text-sm font-bold text-muted">
        <a href="{{ route('educator.quizzes.index') }}" class="hover:text-brand-700">Quiz library</a>
        <span aria-hidden="true" class="px-2">/</span>
        <span class="text-ink">{{ $quiz->title }}</span>
    </nav>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-success" role="status">{{ session('status') }}</div>
    @endif

    <section class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-start">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.badge :tone="$statusTone">{{ $quiz->status->label() }}</x-ui.badge>
                <span class="text-sm font-bold text-muted">Private educator view</span>
            </div>
            <h1 class="mt-4 max-w-4xl text-4xl font-black tracking-tight sm:text-5xl">{{ $quiz->title }}</h1>
            <p class="mt-4 max-w-3xl text-lg leading-8 text-muted">{{ $quiz->description ?: 'Add a description to explain the learning objective and set expectations for learners.' }}</p>

            <div class="mt-7 flex flex-wrap gap-3">
                @can('update', $quiz)
                    <x-ui.button href="{{ route('educator.quizzes.edit', $quiz) }}">Edit settings</x-ui.button>
                @endcan
                <x-ui.button href="{{ route('educator.quizzes.index') }}" variant="secondary">Back to library</x-ui.button>
            </div>

            <section class="mt-10" aria-labelledby="questions-title">
                <div class="flex items-end justify-between gap-4 border-b border-line pb-4">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.16em] text-brand-700">Assessment structure</p>
                        <h2 id="questions-title" class="mt-2 text-2xl font-black">Questions</h2>
                    </div>
                    <p class="text-sm font-bold text-muted">{{ $quiz->questions->count() }} total · {{ $totalPoints }} {{ Str::plural('point', $totalPoints) }}</p>
                </div>

                @forelse ($quiz->questions as $question)
                    <article class="grid gap-4 border-b border-line py-5 sm:grid-cols-[3rem_minmax(0,1fr)_auto] sm:items-start">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-sm font-black text-brand-700">{{ $question->position }}</div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge tone="neutral">{{ $question->type->label() }}</x-ui.badge>
                                <span class="text-xs font-bold text-muted">{{ $question->answer_options_count }} {{ Str::plural('option', $question->answer_options_count) }}</span>
                            </div>
                            <h3 class="mt-3 font-bold leading-7">{{ $question->prompt }}</h3>
                        </div>
                        <p class="text-sm font-black text-ink">{{ $question->points }} {{ Str::plural('pt', $question->points) }}</p>
                    </article>
                @empty
                    <x-ui.panel class="mt-6 border-dashed p-7 text-center sm:p-10">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-muted">No questions yet</p>
                        <h3 class="mt-3 text-2xl font-black">The assessment contract is ready.</h3>
                        <p class="mx-auto mt-2 max-w-lg leading-7 text-muted">Question authoring is the next stage. For now, refine the quiz settings so the builder starts from clear rules.</p>
                    </x-ui.panel>
                @endforelse
            </section>
        </div>

        <aside class="lg:sticky lg:top-6">
            <x-ui.panel class="p-6">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-muted">Delivery contract</p>
                <dl class="mt-5 flex flex-col gap-4 text-sm">
                    <div class="flex items-start justify-between gap-4"><dt class="text-muted">Time limit</dt><dd class="text-right font-bold">{{ $quiz->duration_minutes ? $quiz->duration_minutes.' min' : 'No limit' }}</dd></div>
                    <div class="flex items-start justify-between gap-4"><dt class="text-muted">Passing score</dt><dd class="text-right font-bold">{{ $quiz->pass_percentage }}%</dd></div>
                    <div class="flex items-start justify-between gap-4"><dt class="text-muted">Attempts</dt><dd class="text-right font-bold">{{ $quiz->max_attempts }}</dd></div>
                    <div class="flex items-start justify-between gap-4"><dt class="text-muted">Answer review</dt><dd class="max-w-40 text-right font-bold">{{ $quiz->review_policy->label() }}</dd></div>
                    <div class="flex items-start justify-between gap-4"><dt class="text-muted">Question order</dt><dd class="text-right font-bold">{{ $quiz->shuffle_questions ? 'Shuffled' : 'Fixed' }}</dd></div>
                    <div class="flex items-start justify-between gap-4"><dt class="text-muted">Answer order</dt><dd class="text-right font-bold">{{ $quiz->shuffle_answers ? 'Shuffled' : 'Fixed' }}</dd></div>
                </dl>
            </x-ui.panel>
        </aside>
    </section>
</x-layouts.workspace>
