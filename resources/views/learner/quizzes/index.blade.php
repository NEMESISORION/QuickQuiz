<x-layouts.workspace title="Discover assessments" description="Available and upcoming QuickQuiz assessments.">
    <section class="max-w-5xl">
        <x-ui.badge tone="accent">Assessment library</x-ui.badge>
        <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Choose your next challenge.</h1>
        <p class="mt-4 max-w-2xl text-lg leading-8 text-muted">Every assessment shows its timing and attempt rules before you begin.</p>

        <section class="mt-10" aria-labelledby="available-title">
            <div class="flex items-end justify-between gap-4 border-b border-line pb-4">
                <div><p class="text-sm font-black uppercase tracking-[0.16em] text-brand-700">Ready now</p><h2 id="available-title" class="mt-2 text-2xl font-black">Available assessments</h2></div>
                <p class="text-sm font-bold text-muted">{{ $availableQuizzes->count() }} available</p>
            </div>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                @forelse ($availableQuizzes as $quiz)
                    @php $usedAttempts = ($attempts->get($quiz->id) ?? collect())->count(); @endphp
                    <x-ui.panel class="flex h-full flex-col p-6">
                        <div class="flex items-center justify-between gap-3"><x-ui.badge tone="success">Open</x-ui.badge><span class="text-sm font-bold text-muted">{{ $quiz->questions_count }} {{ Str::plural('question', $quiz->questions_count) }}</span></div>
                        <h3 class="mt-4 text-xl font-black">{{ $quiz->title }}</h3>
                        <p class="mt-2 line-clamp-3 flex-1 leading-7 text-muted">{{ $quiz->description ?: 'Open the assessment to review its instructions and delivery rules.' }}</p>
                        <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm font-bold text-muted">
                            <span>{{ $quiz->duration_minutes ? $quiz->duration_minutes.' min' : 'No time limit' }}</span>
                            <span>{{ max(0, $quiz->max_attempts - $usedAttempts) }} attempts left</span>
                        </div>
                        <div class="mt-6"><x-ui.button href="{{ route('learner.quizzes.show', $quiz) }}">View assessment</x-ui.button></div>
                    </x-ui.panel>
                @empty
                    <div class="rounded-2xl border border-dashed border-line bg-white/70 p-8 text-center md:col-span-2"><h3 class="text-xl font-black">Nothing open right now</h3><p class="mt-2 text-muted">Upcoming assessments will appear below.</p></div>
                @endforelse
            </div>
        </section>

        @if ($upcomingQuizzes->isNotEmpty())
            <section class="mt-12" aria-labelledby="upcoming-title">
                <p class="text-sm font-black uppercase tracking-[0.16em] text-accent-700">Plan ahead</p><h2 id="upcoming-title" class="mt-2 text-2xl font-black">Upcoming</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach ($upcomingQuizzes as $quiz)
                        <a href="{{ route('learner.quizzes.show', $quiz) }}" class="rounded-2xl border border-line bg-white p-5 transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-sm">
                            <x-ui.badge tone="warning">Opens {{ $quiz->opens_at->diffForHumans() }}</x-ui.badge><h3 class="mt-3 text-lg font-black">{{ $quiz->title }}</h3><p class="mt-2 text-sm font-semibold text-muted">{{ $quiz->opens_at->format('M j, Y · g:i A') }}</p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </section>
</x-layouts.workspace>
