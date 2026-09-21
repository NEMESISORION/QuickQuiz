<x-layouts.workspace title="Live lobby" description="Host a secure QuickQuiz live-session lobby.">
    <section class="mx-auto max-w-5xl" x-data x-init="setTimeout(() => window.location.reload(), 5000)">
        @if (session('status'))
            <p class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 font-semibold text-success" role="status">{{ session('status') }}</p>
        @endif
        <div class="grid gap-6 lg:grid-cols-[1fr_21rem]">
            <div>
                <x-ui.badge tone="accent">{{ $liveSession->status->label() }}</x-ui.badge>
                <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">{{ $liveSession->quiz->title }}</h1>
                <p class="mt-4 max-w-2xl text-lg leading-8 text-muted">Keep this screen open while learners join. The lobby refreshes automatically.</p>

                <x-ui.panel class="mt-8 p-6 sm:p-8">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-muted">Learners in lobby</p>
                            <p class="mt-2 text-3xl font-black">{{ $liveSession->participants->count() }}</p>
                        </div>
                        <p class="text-sm font-semibold text-muted" aria-live="polite">Updated {{ now()->format('H:i:s') }}</p>
                    </div>

                    <ul class="mt-6 grid gap-3 sm:grid-cols-2" aria-label="Joined learners">
                        @forelse ($liveSession->participants as $participant)
                            <li class="flex items-center gap-3 rounded-2xl border border-line bg-canvas px-4 py-4">
                                <span class="flex size-10 items-center justify-center rounded-full bg-accent-100 font-black text-accent-700">{{ strtoupper(substr($participant->learner->name, 0, 1)) }}</span>
                                <div class="min-w-0">
                                    <p class="truncate font-bold">{{ $participant->learner->name }}</p>
                                    <p class="text-sm text-muted">Ready</p>
                                </div>
                            </li>
                        @empty
                            <li class="rounded-2xl border border-dashed border-line p-6 text-center text-muted sm:col-span-2">No learners yet. Share the code to begin.</li>
                        @endforelse
                    </ul>
                </x-ui.panel>
            </div>

            <aside>
                <x-ui.panel class="border-brand-200 bg-brand-50 p-6 text-center lg:sticky lg:top-6">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-700">Join code</p>
                    <p class="mt-4 font-mono text-5xl font-black tracking-[0.16em] text-brand-950" aria-label="Join code {{ $liveSession->code }}">{{ $liveSession->code }}</p>
                    <p class="mt-4 text-sm leading-6 text-muted">Learners choose <strong>Join live</strong> and enter this code.</p>
                </x-ui.panel>
            </aside>
        </div>
    </section>
</x-layouts.workspace>
