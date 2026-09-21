<x-layouts.workspace :title="$attempt->quiz->title" description="Focused QuickQuiz assessment experience.">
    @php
        $answerUrls = $attempt->questions->mapWithKeys(fn ($question) => [
            (string) $question->id => route('learner.attempts.answers.update', [$attempt, $question]),
        ]);
        $initialAnswers = $attempt->answers->mapWithKeys(fn ($answer) => [
            (string) $answer->quiz_attempt_question_id => (string) $answer->quiz_attempt_option_id,
        ]);
        $remainingSeconds = $attempt->expires_at
            ? max(0, $attempt->expires_at->timestamp - now()->timestamp)
            : null;
    @endphp

    <section
        x-data="quizAttempt({{ Js::from([
            'answerUrls' => $answerUrls,
            'initialAnswers' => $initialAnswers,
            'remainingSeconds' => $remainingSeconds,
            'resultUrl' => route('learner.attempts.result', $attempt),
        ]) }})"
        class="mx-auto max-w-6xl"
    >
        <header class="grid gap-5 border-b border-line pb-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div>
                <div class="flex flex-wrap items-center gap-3"><x-ui.badge tone="success">Attempt {{ $attempt->attempt_number }}</x-ui.badge><span class="text-sm font-bold text-muted">Answers save automatically</span></div>
                <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">{{ $attempt->quiz->title }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                <div class="rounded-xl border border-line bg-white px-4 py-3 text-right"><p class="text-xs font-black uppercase tracking-[0.14em] text-muted">Time remaining</p><p class="mt-1 font-mono text-lg font-black" :class="expired || (remainingSeconds !== null && remainingSeconds < 60) ? 'text-danger' : 'text-ink'" x-text="formattedTime"></p></div>
                <div class="min-w-36 rounded-xl border border-line bg-white px-4 py-3"><p class="text-xs font-black uppercase tracking-[0.14em] text-muted">Progress</p><p class="mt-1 font-black"><span x-text="answeredCount"></span> / {{ $attempt->questions->count() }} answered</p></div>
            </div>
        </header>

        <div class="mt-4 h-2 overflow-hidden rounded-full bg-brand-100" role="progressbar" :aria-valuenow="progressPercent" aria-valuemin="0" aria-valuemax="100" aria-label="Assessment completion"><div class="h-full rounded-full bg-brand-600 transition-[width] duration-300" :style="`width: ${progressPercent}%`"></div></div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_16rem] lg:items-start">
            <main>
                @foreach ($attempt->questions as $index => $question)
                    <article x-show="current === {{ $index }}" x-cloak class="rounded-3xl border border-line bg-white p-6 shadow-sm sm:p-9">
                        <div class="flex flex-wrap items-center justify-between gap-3"><p class="text-sm font-black uppercase tracking-[0.16em] text-brand-700">Question {{ $index + 1 }} of {{ $attempt->questions->count() }}</p><p class="text-sm font-bold text-muted">{{ $question->points }} {{ Str::plural('point', $question->points) }}</p></div>
                        <fieldset class="mt-6" :disabled="expired">
                            <legend class="text-xl font-black leading-8 sm:text-2xl">{{ $question->prompt }}</legend>
                            <div class="mt-7 grid gap-3">
                                @foreach ($question->options as $option)
                                    <label class="group flex min-h-16 cursor-pointer items-center gap-4 rounded-2xl border border-line px-4 py-3 transition hover:border-brand-300 hover:bg-brand-50 has-checked:border-brand-500 has-checked:bg-brand-50">
                                        <input type="radio" name="question_{{ $question->id }}" value="{{ $option->id }}" x-model="answers['{{ $question->id }}']" @change="saveAnswer('{{ $question->id }}', {{ $option->id }})" class="size-5 shrink-0 accent-brand-600">
                                        <span class="font-bold leading-6">{{ $option->content }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    </article>
                @endforeach

                <nav class="mt-6 flex items-center justify-between gap-4" aria-label="Question navigation">
                    <x-ui.button type="button" variant="secondary" x-show="current > 0" @click="current--">Previous</x-ui.button><span x-show="current === 0" aria-hidden="true"></span>
                    <x-ui.button type="button" x-show="current < {{ $attempt->questions->count() - 1 }}" @click="current++">Next question</x-ui.button>
                    <form x-show="current === {{ $attempt->questions->count() - 1 }}" method="POST" action="{{ route('learner.attempts.submission.store', $attempt) }}" @submit="submitAttempt($event)">
                        @csrf
                        <x-ui.button type="submit">Submit attempt</x-ui.button>
                    </form>
                </nav>
                <p class="mt-5 min-h-6 text-center text-sm font-bold" :class="saveState === 'error' || expired ? 'text-danger' : 'text-muted'" aria-live="polite" x-text="message"></p>
                <p x-show="current === {{ $attempt->questions->count() - 1 }} && answeredCount < {{ $attempt->questions->count() }}" class="mt-2 text-center text-sm font-semibold text-warning"><span x-text="{{ $attempt->questions->count() }} - answeredCount"></span> unanswered. You may still submit.</p>
            </main>

            <aside class="lg:sticky lg:top-6"><x-ui.panel class="p-5"><p class="text-xs font-black uppercase tracking-[0.16em] text-muted">Question map</p><div class="mt-4 grid grid-cols-5 gap-2 lg:grid-cols-4">
                @foreach ($attempt->questions as $index => $question)
                    <button type="button" @click="current = {{ $index }}" class="flex aspect-square min-h-11 items-center justify-center rounded-xl border text-sm font-black transition" :class="current === {{ $index }} ? 'border-brand-600 bg-brand-600 text-white' : (answers['{{ $question->id }}'] ? 'border-green-300 bg-green-50 text-success' : 'border-line bg-white text-muted hover:border-brand-300')" aria-label="Go to question {{ $index + 1 }}">{{ $index + 1 }}</button>
                @endforeach
            </div><p class="mt-5 text-sm leading-6 text-muted">Move freely between questions. Saved answers remain available after refresh.</p></x-ui.panel></aside>
        </div>
    </section>
</x-layouts.workspace>
