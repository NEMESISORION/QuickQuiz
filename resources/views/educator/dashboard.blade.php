<x-layouts.workspace title="Educator workspace" description="Create and manage thoughtful assessments in QuickQuiz.">
    <section class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
        <div>
            <x-ui.badge>Educator workspace</x-ui.badge>
            <h1 class="mt-5 max-w-3xl text-4xl font-black tracking-tight sm:text-5xl">Build assessment experiences with purpose.</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-muted">Turn a learning goal into a focused assessment. Start with the quiz rules, then shape the questions before publishing.</p>

            <div class="mt-7 flex flex-wrap gap-3">
                <x-ui.button href="{{ route('educator.quizzes.create') }}">Create a quiz</x-ui.button>
                <x-ui.button href="{{ route('educator.quizzes.index') }}" variant="secondary">Manage quizzes</x-ui.button>
                <x-ui.button href="{{ route('educator.analytics.index') }}" variant="secondary">View analytics</x-ui.button>
            </div>

            <div class="mt-9 grid gap-4 sm:grid-cols-2">
                <x-ui.panel class="p-6">
                    <p class="text-sm font-black uppercase tracking-wider text-brand-700">Authoring</p>
                    <h2 class="mt-3 text-xl font-bold">Create with confidence</h2>
                    <p class="mt-2 leading-7 text-muted">Set the purpose, time limit, passing score, attempts, and feedback policy from one clear workflow.</p>
                </x-ui.panel>
                <x-ui.panel class="p-6">
                    <p class="text-sm font-black uppercase tracking-wider text-accent-700">Evidence</p>
                    <h2 class="mt-3 text-xl font-bold">Review what matters</h2>
                    <p class="mt-2 leading-7 text-muted">Results will be derived from real attempts, never placeholder metrics.</p>
                </x-ui.panel>
            </div>
        </div>

        <x-ui.panel as="article" class="p-6">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-muted">Workspace status</p>
            <h2 class="mt-3 text-xl font-black">Authoring is open</h2>
            <dl class="mt-6 flex flex-col gap-4 text-sm">
                <div class="flex items-center justify-between gap-4"><dt class="text-muted">Identity</dt><dd class="font-bold text-success">Verified locally</dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-muted">Role</dt><dd class="font-bold">Educator</dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-muted">Quizzes</dt><dd><a href="{{ route('educator.quizzes.index') }}" class="font-bold text-brand-700 underline decoration-brand-200 underline-offset-4">Open library</a></dd></div>
            </dl>
        </x-ui.panel>
    </section>
</x-layouts.workspace>
