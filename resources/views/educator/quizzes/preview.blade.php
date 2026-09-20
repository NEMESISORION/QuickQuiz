<x-layouts.workspace title="Preview {{ $quiz->title }}" description="Learner preview for {{ $quiz->title }}.">
    <div class="mx-auto max-w-4xl">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-bold text-warning" role="status">Educator preview · answers are not submitted or scored.</div>
        <div class="mt-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.badge tone="neutral">Learner preview</x-ui.badge>
                <h1 class="mt-4 text-4xl font-black tracking-tight">{{ $quiz->title }}</h1>
                <p class="mt-3 max-w-2xl leading-7 text-muted">{{ $quiz->description }}</p>
            </div>
            <x-ui.button href="{{ route('educator.quizzes.show', $quiz) }}" variant="secondary">Exit preview</x-ui.button>
        </div>

        <dl class="mt-7 grid gap-3 rounded-2xl border border-line bg-white p-5 text-sm sm:grid-cols-3">
            <div><dt class="text-muted">Questions</dt><dd class="mt-1 font-black">{{ $quiz->questions->count() }}</dd></div>
            <div><dt class="text-muted">Time limit</dt><dd class="mt-1 font-black">{{ $quiz->duration_minutes ? $quiz->duration_minutes.' minutes' : 'No limit' }}</dd></div>
            <div><dt class="text-muted">Passing score</dt><dd class="mt-1 font-black">{{ $quiz->pass_percentage }}%</dd></div>
        </dl>

        <div class="mt-8 flex flex-col gap-6">
            @forelse ($quiz->questions as $question)
                <x-ui.panel as="article" class="p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <p class="text-sm font-black text-brand-700">Question {{ $question->position }}</p>
                        <p class="text-sm font-bold text-muted">{{ $question->points }} {{ Str::plural('point', $question->points) }}</p>
                    </div>
                    <h2 class="mt-3 text-xl font-black leading-8">{{ $question->prompt }}</h2>
                    <fieldset class="mt-5 flex flex-col gap-3">
                        <legend class="sr-only">Answer choices</legend>
                        @foreach ($question->answerOptions as $option)
                            <label class="flex items-center gap-3 rounded-xl border border-line p-4">
                                <input type="radio" disabled class="size-5 text-brand-600">
                                <span class="font-semibold">{{ $option->content }}</span>
                            </label>
                        @endforeach
                    </fieldset>
                </x-ui.panel>
            @empty
                <x-ui.panel class="border-dashed p-10 text-center"><p class="font-black">No questions to preview yet.</p></x-ui.panel>
            @endforelse
        </div>
    </div>
</x-layouts.workspace>
