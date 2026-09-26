<x-layouts.workspace title="Live quiz" description="Take part in a synchronized QuickQuiz live session.">
    <section class="mx-auto max-w-3xl" data-live-session-sync data-state-url="{{ route('live-sessions.state', $liveSession) }}" data-initial-version="{{ $syncVersion }}">
        @if (session('status'))
            <p class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 font-semibold text-success" role="status">{{ session('status') }}</p>
        @endif
        @error('answer_option_id')
            <p class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 font-semibold text-danger" role="alert">{{ $message }}</p>
        @enderror
        <p data-live-sync-message hidden class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 font-semibold text-warning" role="status" aria-live="polite"></p>

        <div class="text-center">
            <x-ui.badge tone="accent">{{ $liveSession->status->label() }}</x-ui.badge>
            <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">{{ $liveSession->quiz->title }}</h1>
            <p class="mt-3 text-muted">Hosted by {{ $liveSession->host->name }} · Room {{ $liveSession->code }}</p>
        </div>

        @if ($liveSession->status === \App\Enums\LiveSessionStatus::Active)
            <div class="mt-6 rounded-xl border border-line bg-white px-4 py-3 text-center">
                <p class="text-xs font-black uppercase tracking-[0.14em] text-muted">Time remaining</p>
                <p data-live-time data-remaining-seconds="{{ $remainingSeconds }}" class="mt-1 font-mono text-xl font-black text-ink">{{ $remainingSeconds === null ? 'No time limit' : '—' }}</p>
            </div>
        @endif

        @if ($liveSession->status === \App\Enums\LiveSessionStatus::Waiting)
            <x-ui.panel class="mt-8 p-8 text-center sm:p-12">
                <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-accent-100 text-2xl font-black text-accent-700" aria-hidden="true">✓</span>
                <h2 class="mt-5 text-2xl font-black">Ready in the lobby</h2>
                <p class="mt-2 text-muted">Keep this page open. The first question appears automatically when the host starts.</p>
            </x-ui.panel>
        @elseif ($liveSession->status === \App\Enums\LiveSessionStatus::Active && $liveSession->currentQuestion)
            <x-ui.panel class="mt-8 p-6 sm:p-8">
                <div class="flex flex-wrap items-center justify-between gap-3"><p class="text-xs font-black uppercase tracking-[0.16em] text-brand-700">Question {{ $questionNumber }} of {{ $liveSession->quiz->questions->count() }}</p><p class="text-sm font-black">{{ $currentScore }} points</p></div>
                <h2 class="mt-4 text-2xl font-black leading-tight sm:text-3xl">{{ $liveSession->currentQuestion->prompt }}</h2>

                @if ($currentResponse)
                    <div class="mt-8 rounded-2xl border border-green-200 bg-green-50 p-6 text-center"><p class="text-xl font-black text-success">Answer locked</p><p class="mt-2 text-muted">Waiting for the host to reveal the next question.</p></div>
                @else
                    <form method="POST" action="{{ route('learner.live-sessions.answer', $liveSession) }}" data-live-answer-form class="mt-7 flex flex-col gap-3">
                        @csrf
                        @foreach ($liveSession->currentQuestion->answerOptions as $option)
                            <label class="flex min-h-16 cursor-pointer items-center gap-4 rounded-2xl border border-line bg-white px-5 py-4 font-bold transition hover:border-brand-300 hover:bg-brand-50 has-checked:border-brand-500 has-checked:bg-brand-50">
                                <input type="radio" name="answer_option_id" value="{{ $option->id }}" required class="size-5 accent-brand-600">
                                <span>{{ $option->content }}</span>
                            </label>
                        @endforeach
                        <x-ui.button type="submit" class="mt-3 w-full">Lock answer</x-ui.button>
                    </form>
                @endif
            </x-ui.panel>
        @else
            <x-ui.panel class="mt-8 p-6 sm:p-8">
                <div class="text-center"><p class="text-xs font-black uppercase tracking-[0.16em] text-accent-700">Final leaderboard</p><h2 class="mt-2 text-3xl font-black">{{ $currentScore }} / {{ $maxScore }} points</h2></div>
                <ol class="mt-7 flex flex-col gap-3">
                    @foreach ($leaderboard as $index => $entry)
                        <li class="flex items-center justify-between gap-4 rounded-2xl border {{ $entry->learner_id === $participant->learner_id ? 'border-brand-300 bg-brand-50' : 'border-line bg-canvas' }} px-5 py-4">
                            <div class="flex min-w-0 items-center gap-4"><span class="font-black text-brand-700">#{{ $index + 1 }}</span><span class="truncate font-bold">{{ $entry->learner->name }}</span></div>
                            <span class="shrink-0 font-black">{{ (int) $entry->responses_sum_points_awarded }} pts</span>
                        </li>
                    @endforeach
                </ol>
            </x-ui.panel>
        @endif
    </section>
</x-layouts.workspace>
