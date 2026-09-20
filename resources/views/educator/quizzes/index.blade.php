<x-layouts.workspace title="Quiz library" description="Create and manage your QuickQuiz assessments.">
    <section>
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <x-ui.badge>Quiz authoring</x-ui.badge>
                <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">Your quiz library</h1>
                <p class="mt-3 max-w-2xl text-lg leading-8 text-muted">Keep every assessment intentional, from the first draft through delivery and review.</p>
            </div>
            <x-ui.button href="{{ route('educator.quizzes.create') }}">Create a quiz</x-ui.button>
        </div>

        @if (session('status'))
            <div class="mt-7 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-success" role="status">{{ session('status') }}</div>
        @endif

        @if ($quizzes->isEmpty())
            <x-ui.panel class="mt-9 overflow-hidden">
                <div class="grid gap-8 p-7 sm:p-10 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-center">
                    <div>
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-brand-700">Clean slate</p>
                        <h2 class="mt-3 text-3xl font-black tracking-tight">Build your first assessment.</h2>
                        <p class="mt-3 max-w-xl leading-7 text-muted">Define the goal and rules now. You will add and refine questions in the next authoring step.</p>
                        <div class="mt-6"><x-ui.button href="{{ route('educator.quizzes.create') }}">Start a draft</x-ui.button></div>
                    </div>
                    <div class="rounded-2xl border border-brand-100 bg-brand-50 p-6">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-700">A strong draft starts with</p>
                        <ul class="mt-4 flex flex-col gap-3 text-sm font-semibold text-brand-950">
                            <li>One clear learning objective</li>
                            <li>A realistic time limit</li>
                            <li>A deliberate feedback policy</li>
                        </ul>
                    </div>
                </div>
            </x-ui.panel>
        @else
            <div class="mt-9 flex items-center justify-between gap-4 border-b border-line pb-4">
                <p class="text-sm font-bold text-muted">{{ $quizzes->total() }} {{ Str::plural('quiz', $quizzes->total()) }}</p>
                <p class="text-sm text-muted">Most recently updated first</p>
            </div>

            <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($quizzes as $quiz)
                    @php
                        $statusTone = match ($quiz->status) {
                            \App\Enums\QuizStatus::Published => 'success',
                            \App\Enums\QuizStatus::Scheduled => 'warning',
                            \App\Enums\QuizStatus::Closed => 'accent',
                            default => 'neutral',
                        };
                    @endphp
                    <x-ui.panel as="article" class="flex h-full flex-col p-6 transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-soft">
                        <div class="flex items-start justify-between gap-4">
                            <x-ui.badge :tone="$statusTone">{{ $quiz->status->label() }}</x-ui.badge>
                            <span class="text-xs font-bold text-muted">{{ $quiz->questions_count }} {{ Str::plural('question', $quiz->questions_count) }}</span>
                        </div>
                        <h2 class="mt-5 text-xl font-black leading-snug">
                            <a href="{{ route('educator.quizzes.show', $quiz) }}" class="hover:text-brand-700">{{ $quiz->title }}</a>
                        </h2>
                        <p class="mt-3 line-clamp-3 flex-1 leading-7 text-muted">{{ $quiz->description ?: 'No description yet. Add one to clarify the assessment goal.' }}</p>
                        <div class="mt-6 flex items-center justify-between gap-3 border-t border-line pt-4 text-sm">
                            <span class="font-semibold text-muted">Updated {{ $quiz->updated_at->diffForHumans() }}</span>
                            <a href="{{ route('educator.quizzes.show', $quiz) }}" class="font-black text-brand-700 underline decoration-brand-200 underline-offset-4">Open</a>
                        </div>
                    </x-ui.panel>
                @endforeach
            </div>

            <div class="mt-8">{{ $quizzes->links() }}</div>
        @endif
    </section>
</x-layouts.workspace>
