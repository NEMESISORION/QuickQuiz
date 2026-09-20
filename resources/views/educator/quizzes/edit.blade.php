<x-layouts.workspace title="Edit {{ $quiz->title }}" description="Update this QuickQuiz assessment draft.">
    <div class="mx-auto max-w-4xl">
        <nav aria-label="Breadcrumb" class="text-sm font-bold text-muted">
            <a href="{{ route('educator.quizzes.index') }}" class="hover:text-brand-700">Quiz library</a>
            <span aria-hidden="true" class="px-2">/</span>
            <a href="{{ route('educator.quizzes.show', $quiz) }}" class="hover:text-brand-700">{{ $quiz->title }}</a>
            <span aria-hidden="true" class="px-2">/</span>
            <span class="text-ink">Edit</span>
        </nav>
        <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Refine quiz settings</h1>
        <p class="mt-3 max-w-2xl text-lg leading-8 text-muted">Keep the assessment rules aligned with what you want learners to demonstrate.</p>

        @include('educator.quizzes.partials.form', [
            'action' => route('educator.quizzes.update', $quiz),
            'method' => 'PATCH',
            'submitLabel' => 'Save changes',
            'cancelUrl' => route('educator.quizzes.show', $quiz),
        ])

        <x-ui.panel class="mt-10 border-red-200 p-6">
            <h2 class="text-lg font-black">Archive this draft</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted">Archived quizzes disappear from your active library but remain recoverable in the database. Published quizzes cannot be archived through this draft action.</p>
            <details class="mt-4">
                <summary class="cursor-pointer font-bold text-danger">Show archive action</summary>
                <form method="POST" action="{{ route('educator.quizzes.destroy', $quiz) }}" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger">Move quiz to archive</x-ui.button>
                </form>
            </details>
        </x-ui.panel>
    </div>
</x-layouts.workspace>
