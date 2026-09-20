<x-layouts.workspace title="Add question" description="Add a question to {{ $quiz->title }}.">
    <div class="mx-auto max-w-4xl">
        <nav aria-label="Breadcrumb" class="text-sm font-bold text-muted">
            <a href="{{ route('educator.quizzes.show', $quiz) }}" class="hover:text-brand-700">{{ $quiz->title }}</a>
            <span aria-hidden="true" class="px-2">/</span><span class="text-ink">Add question</span>
        </nav>
        <h1 class="mt-5 text-4xl font-black tracking-tight">Add a purposeful question</h1>
        <p class="mt-3 text-lg leading-8 text-muted">Keep the prompt focused and make every answer option plausible.</p>
        @include('educator.questions.partials.form', [
            'action' => route('educator.quizzes.questions.store', $quiz),
            'method' => 'POST',
            'submitLabel' => 'Add question',
        ])
    </div>
</x-layouts.workspace>
