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
                    <x-ui.button href="{{ route('educator.quizzes.questions.create', $quiz) }}" variant="secondary">Add question</x-ui.button>
                @endcan
                <x-ui.button href="{{ route('educator.quizzes.preview', $quiz) }}" variant="secondary">Learner preview</x-ui.button>
                <x-ui.button href="{{ route('educator.quizzes.results.index', $quiz) }}" variant="secondary">View results</x-ui.button>
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
                        <div class="flex flex-col items-end gap-2">
                            <p class="text-sm font-black text-ink">{{ $question->points }} {{ Str::plural('pt', $question->points) }}</p>
                            @can('update', $question)
                                <a href="{{ route('educator.quizzes.questions.edit', [$quiz, $question]) }}" class="text-sm font-bold text-brand-700 underline underline-offset-4">Edit</a>
                                <div class="flex gap-1">
                                    @foreach (['up' => 'Move up', 'down' => 'Move down'] as $direction => $label)
                                        <form method="POST" action="{{ route('educator.quizzes.questions.position', [$quiz, $question]) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="direction" value="{{ $direction }}">
                                            <button type="submit" class="min-h-9 rounded-lg border border-line px-2 text-xs font-bold hover:bg-brand-50" aria-label="{{ $label }} question {{ $question->position }}">{{ $direction === 'up' ? '↑' : '↓' }}</button>
                                        </form>
                                    @endforeach
                                </div>
                            @endcan
                        </div>
                    </article>
                @empty
                    <x-ui.panel class="mt-6 border-dashed p-7 text-center sm:p-10">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-muted">No questions yet</p>
                        <h3 class="mt-3 text-2xl font-black">The assessment contract is ready.</h3>
                        <p class="mx-auto mt-2 max-w-lg leading-7 text-muted">Add a multiple-choice or true-or-false question, then preview the complete learner experience.</p>
                        @can('update', $quiz)<div class="mt-5"><x-ui.button href="{{ route('educator.quizzes.questions.create', $quiz) }}">Add first question</x-ui.button></div>@endcan
                    </x-ui.panel>
                @endforelse
            </section>

            @can('publish', $quiz)
                <x-ui.panel class="mt-10 p-6 sm:p-8">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-accent-700">Final delivery step</p>
                    <h2 class="mt-2 text-2xl font-black">Publish or schedule</h2>
                    <p class="mt-2 leading-7 text-muted">Publishing locks the quiz structure so every learner receives a stable assessment.</p>
                    @error('quiz')<p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-danger">{{ $message }}</p>@enderror
                    <form method="POST" action="{{ route('educator.quizzes.publication.store', $quiz) }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div class="flex flex-col gap-2 sm:col-span-2">
                            <label for="mode" class="text-sm font-bold">Release timing</label>
                            <select id="mode" name="mode" class="min-h-12 rounded-xl border border-line bg-white px-4 py-3">
                                <option value="immediate">Publish now</option>
                                <option value="scheduled" @selected(old('mode') === 'scheduled')>Schedule for later</option>
                            </select>
                            @error('mode')<p class="text-sm font-semibold text-danger">{{ $message }}</p>@enderror
                        </div>
                        <x-ui.input label="Opens at (for scheduled release)" name="opens_at" type="datetime-local" :value="old('opens_at')" />
                        <x-ui.input label="Closes at (optional)" name="closes_at" type="datetime-local" :value="old('closes_at')" />
                        <div class="sm:col-span-2"><x-ui.button type="submit">Confirm release</x-ui.button></div>
                    </form>
                </x-ui.panel>
            @endcan
        </div>

        <aside class="lg:sticky lg:top-6">
            @if ($quiz->status === \App\Enums\QuizStatus::Published)
                <x-ui.panel class="mb-5 p-6">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-accent-700">Live delivery</p>
                    <h2 class="mt-2 text-xl font-black">Bring learners together</h2>
                    <p class="mt-2 text-sm leading-6 text-muted">Open a controlled lobby and share a short join code.</p>
                    <form method="POST" action="{{ route('educator.quizzes.live-sessions.store', $quiz) }}" class="mt-5">
                        @csrf
                        <x-ui.button type="submit" class="w-full">Open live lobby</x-ui.button>
                    </form>
                </x-ui.panel>
            @endif
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
