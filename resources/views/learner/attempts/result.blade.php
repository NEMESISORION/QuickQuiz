<x-layouts.workspace :title="'Result · '.$attempt->quiz->title" description="Your QuickQuiz assessment result.">
    @if (session('status'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-success" role="status">{{ session('status') }}</div>
    @endif

    <section class="mx-auto max-w-5xl">
        <div class="rounded-3xl border border-line bg-white p-7 shadow-sm sm:p-10">
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.badge :tone="$attempt->passed ? 'success' : 'warning'">{{ $attempt->passed ? 'Passed' : 'Keep learning' }}</x-ui.badge>
                @if ($attempt->status === \App\Enums\QuizAttemptStatus::Expired)<x-ui.badge tone="neutral">Time expired</x-ui.badge>@endif
            </div>
            <div class="mt-6 grid gap-8 lg:grid-cols-[minmax(0,1fr)_14rem] lg:items-center">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.16em] text-brand-700">Attempt {{ $attempt->attempt_number }} complete</p>
                    <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-5xl">{{ $attempt->quiz->title }}</h1>
                    <p class="mt-4 max-w-2xl text-lg leading-8 text-muted">{{ $attempt->passed ? 'You reached the passing target. Nice work.' : 'Review the feedback available below, then use it to guide your next attempt.' }}</p>
                </div>
                <div class="rounded-3xl bg-brand-50 p-6 text-center"><p class="text-5xl font-black text-brand-700">{{ $percentage }}%</p><p class="mt-2 text-sm font-bold text-muted">{{ $attempt->score }} / {{ $attempt->max_score }} points</p></div>
            </div>

            <dl class="mt-8 grid gap-4 border-t border-line pt-6 sm:grid-cols-3">
                <div><dt class="text-sm font-bold text-muted">Passing target</dt><dd class="mt-1 text-lg font-black">{{ $attempt->pass_percentage_snapshot }}%</dd></div>
                <div><dt class="text-sm font-bold text-muted">Submitted</dt><dd class="mt-1 text-lg font-black">{{ $attempt->submitted_at?->format('M j · g:i A') }}</dd></div>
                <div><dt class="text-sm font-bold text-muted">Answer review</dt><dd class="mt-1 text-lg font-black">{{ $attempt->review_policy_snapshot->label() }}</dd></div>
            </dl>
        </div>

        @if ($answersAreVisible)
            <section class="mt-10" aria-labelledby="review-title">
                <p class="text-sm font-black uppercase tracking-[0.16em] text-accent-700">Learning review</p><h2 id="review-title" class="mt-2 text-3xl font-black">Review your answers</h2>
                <div class="mt-6 grid gap-5">
                    @foreach ($attempt->questions as $question)
                        @php
                            $answer = $attempt->answers->firstWhere('quiz_attempt_question_id', $question->id);
                            $selectedOptionId = $answer?->quiz_attempt_option_id;
                            $wasCorrect = $answer?->option?->is_correct ?? false;
                        @endphp
                        <article class="rounded-2xl border border-line bg-white p-6">
                            <div class="flex flex-wrap items-start justify-between gap-3"><h3 class="max-w-3xl text-lg font-black leading-7">{{ $question->position }}. {{ $question->prompt }}</h3><x-ui.badge :tone="$wasCorrect ? 'success' : 'warning'">{{ $wasCorrect ? 'Correct' : ($answer ? 'Incorrect' : 'Unanswered') }}</x-ui.badge></div>
                            <div class="mt-5 grid gap-2">
                                @foreach ($question->options as $option)
                                    <div class="rounded-xl border px-4 py-3 text-sm font-semibold {{ $option->is_correct ? 'border-green-200 bg-green-50 text-success' : ($selectedOptionId === $option->id ? 'border-red-200 bg-red-50 text-danger' : 'border-line text-muted') }}">
                                        {{ $option->content }} @if ($option->is_correct)<span class="font-black">· Correct answer</span>@elseif ($selectedOptionId === $option->id)<span class="font-black">· Your answer</span>@endif
                                    </div>
                                @endforeach
                            </div>
                            @if ($question->explanation)<p class="mt-4 rounded-xl bg-canvas px-4 py-3 text-sm leading-6 text-muted"><span class="font-black text-ink">Explanation:</span> {{ $question->explanation }}</p>@endif
                        </article>
                    @endforeach
                </div>
            </section>
        @else
            <div class="mt-8 rounded-2xl border border-dashed border-line bg-white/70 p-7 text-center"><h2 class="text-xl font-black">Answer review is locked</h2><p class="mt-2 text-muted">{{ $reviewMessage }}</p></div>
        @endif

        <div class="mt-8"><x-ui.button href="{{ route('learner.quizzes.show', $attempt->quiz) }}" variant="secondary">Back to assessment</x-ui.button></div>
    </section>
</x-layouts.workspace>
