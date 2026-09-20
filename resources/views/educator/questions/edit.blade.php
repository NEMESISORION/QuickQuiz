<x-layouts.workspace title="Edit question" description="Edit a question in {{ $quiz->title }}.">
    <div class="mx-auto max-w-4xl">
        <nav aria-label="Breadcrumb" class="text-sm font-bold text-muted">
            <a href="{{ route('educator.quizzes.show', $quiz) }}" class="hover:text-brand-700">{{ $quiz->title }}</a>
            <span aria-hidden="true" class="px-2">/</span><span class="text-ink">Question {{ $question->position }}</span>
        </nav>
        <h1 class="mt-5 text-4xl font-black tracking-tight">Refine question {{ $question->position }}</h1>
        @include('educator.questions.partials.form', [
            'action' => route('educator.quizzes.questions.update', [$quiz, $question]),
            'method' => 'PATCH',
            'submitLabel' => 'Save question',
        ])
        <details class="mt-9 rounded-xl border border-red-200 bg-white p-5">
            <summary class="cursor-pointer font-bold text-danger">Remove this question</summary>
            <form method="POST" action="{{ route('educator.quizzes.questions.destroy', [$quiz, $question]) }}" class="mt-4">
                @csrf @method('DELETE')
                <x-ui.button type="submit" variant="danger">Remove question</x-ui.button>
            </form>
        </details>
    </div>
</x-layouts.workspace>
