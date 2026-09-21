<x-layouts.workspace title="Live session" description="Control a synchronized QuickQuiz live session.">
    <section class="mx-auto max-w-6xl" x-data="liveSessionSync({ stateUrl: {{ Js::from(route('live-sessions.state', $liveSession)) }}, initialVersion: {{ Js::from($syncVersion) }} })">
        @if (session('status'))
            <p class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 font-semibold text-success" role="status">{{ session('status') }}</p>
        @endif
        @error('session')
            <p class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 font-semibold text-danger" role="alert">{{ $message }}</p>
        @enderror

        <div class="flex flex-wrap items-start justify-between gap-5">
            <div>
                <x-ui.badge tone="accent">{{ $liveSession->status->label() }}</x-ui.badge>
                <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">{{ $liveSession->quiz->title }}</h1>
                <p class="mt-3 text-muted">Room <span class="font-mono font-black tracking-wider text-brand-700">{{ $liveSession->code }}</span> · {{ $participants->count() }} learners</p>
            </div>
            @if ($liveSession->status === \App\Enums\LiveSessionStatus::Waiting)
                <form method="POST" action="{{ route('educator.live-sessions.start', $liveSession) }}">
                    @csrf
                    <x-ui.button type="submit" :disabled="$participants->isEmpty()">Start live quiz</x-ui.button>
                </form>
            @elseif ($liveSession->status === \App\Enums\LiveSessionStatus::Active)
                <form method="POST" action="{{ route('educator.live-sessions.advance', $liveSession) }}">
                    @csrf
                    <x-ui.button type="submit">{{ $questionNumber === $liveSession->quiz->questions->count() ? 'Finish session' : 'Next question' }}</x-ui.button>
                </form>
            @endif
        </div>

        @if ($liveSession->status === \App\Enums\LiveSessionStatus::Waiting)
            <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_20rem]">
                <x-ui.panel class="p-6 sm:p-8">
                    <h2 class="text-2xl font-black">Waiting room</h2>
                    <p class="mt-2 text-muted">The session begins when you choose start. New learners appear automatically.</p>
                    <ul class="mt-6 grid gap-3 sm:grid-cols-2" aria-label="Joined learners">
                        @forelse ($participants as $participant)
                            <li class="flex items-center gap-3 rounded-2xl border border-line bg-canvas px-4 py-4">
                                <span class="flex size-10 items-center justify-center rounded-full bg-accent-100 font-black text-accent-700">{{ strtoupper(substr($participant->learner->name, 0, 1)) }}</span>
                                <p class="truncate font-bold">{{ $participant->learner->name }}</p>
                            </li>
                        @empty
                            <li class="rounded-2xl border border-dashed border-line p-6 text-center text-muted sm:col-span-2">Share the room code to invite learners.</li>
                        @endforelse
                    </ul>
                </x-ui.panel>
                <x-ui.panel class="border-brand-200 bg-brand-50 p-6 text-center">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-700">Join code</p>
                    <p class="mt-4 font-mono text-5xl font-black tracking-[0.16em] text-brand-950">{{ $liveSession->code }}</p>
                </x-ui.panel>
            </div>
        @elseif ($liveSession->status === \App\Enums\LiveSessionStatus::Active && $liveSession->currentQuestion)
            @php
                $responseTotal = (int) $distribution->sum();
            @endphp
            <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <x-ui.panel class="p-6 sm:p-8">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-700">Question {{ $questionNumber }} of {{ $liveSession->quiz->questions->count() }}</p>
                    <h2 class="mt-3 text-2xl font-black leading-tight sm:text-3xl">{{ $liveSession->currentQuestion->prompt }}</h2>
                    <div class="mt-7 flex flex-col gap-4">
                        @foreach ($liveSession->currentQuestion->answerOptions as $option)
                            @php
                                $count = (int) ($distribution[$option->id] ?? 0);
                                $percentage = $responseTotal === 0 ? 0 : (int) round(($count / $responseTotal) * 100);
                            @endphp
                            <div class="rounded-2xl border {{ $option->is_correct ? 'border-green-200 bg-green-50' : 'border-line bg-white' }} p-4">
                                <div class="flex items-center justify-between gap-4"><p class="font-bold">{{ $option->content }}</p><p class="shrink-0 text-sm font-black">{{ $count }} · {{ $percentage }}%</p></div>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full {{ $option->is_correct ? 'bg-success' : 'bg-brand-500' }}" style="width: {{ $percentage }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                </x-ui.panel>
                <aside class="flex flex-col gap-5">
                    <x-ui.panel class="p-6 text-center"><p class="text-xs font-black uppercase tracking-[0.16em] text-muted">Answers locked</p><p class="mt-3 text-4xl font-black">{{ $responseTotal }} / {{ $participants->count() }}</p></x-ui.panel>
                    <x-ui.panel class="p-6">
                        <h3 class="font-black">Live leaderboard</h3>
                        <ol class="mt-4 flex flex-col gap-3">
                            @foreach ($participants->take(5) as $participant)
                                <li class="flex items-center justify-between gap-3 text-sm"><span class="truncate font-semibold">{{ $participant->learner->name }}</span><span class="font-black">{{ (int) $participant->responses_sum_points_awarded }} pts</span></li>
                            @endforeach
                        </ol>
                    </x-ui.panel>
                </aside>
            </div>
        @else
            <x-ui.panel class="mt-8 p-6 sm:p-8">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-accent-700">Final standings</p>
                <h2 class="mt-2 text-3xl font-black">Session complete</h2>
                <ol class="mt-7 flex flex-col gap-3">
                    @foreach ($participants as $index => $participant)
                        @php
                            $score = (int) $participant->responses_sum_points_awarded;
                            $percentage = $maxScore === 0 ? 0 : (int) round(($score / $maxScore) * 100);
                        @endphp
                        <li class="flex items-center justify-between gap-4 rounded-2xl border border-line bg-canvas px-5 py-4">
                            <div class="flex min-w-0 items-center gap-4"><span class="font-black text-brand-700">#{{ $index + 1 }}</span><span class="truncate font-bold">{{ $participant->learner->name }}</span></div>
                            <span class="shrink-0 font-black">{{ $score }} / {{ $maxScore }} · {{ $percentage }}%</span>
                        </li>
                    @endforeach
                </ol>
            </x-ui.panel>
        @endif
    </section>
</x-layouts.workspace>
