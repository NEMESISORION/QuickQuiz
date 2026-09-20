<x-layouts.workspace title="Create quiz" description="Create a new QuickQuiz assessment draft.">
    <div class="mx-auto max-w-4xl">
        <nav aria-label="Breadcrumb" class="text-sm font-bold text-muted">
            <a href="{{ route('educator.quizzes.index') }}" class="hover:text-brand-700">Quiz library</a>
            <span aria-hidden="true" class="px-2">/</span>
            <span class="text-ink">New quiz</span>
        </nav>
        <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Create a focused quiz</h1>
        <p class="mt-3 max-w-2xl text-lg leading-8 text-muted">Start with the assessment contract. You can shape the question experience after this draft is saved.</p>

        @include('educator.quizzes.partials.form', [
            'action' => route('educator.quizzes.store'),
            'method' => 'POST',
            'submitLabel' => 'Create draft',
            'cancelUrl' => route('educator.quizzes.index'),
        ])
    </div>
</x-layouts.workspace>
