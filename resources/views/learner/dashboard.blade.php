<x-layouts.workspace title="Learner workspace" description="A calm place to take assessments and understand results.">
    <section class="max-w-4xl">
        <x-ui.badge tone="accent">Learner workspace</x-ui.badge>
        <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Focus on the question in front of you.</h1>
        <p class="mt-4 max-w-2xl text-lg leading-8 text-muted">Find released assessments, review the rules, and begin when you are ready.</p>

        <div class="mt-7"><x-ui.button href="{{ route('learner.quizzes.index') }}">Discover assessments</x-ui.button></div>

        <div class="mt-10 grid gap-5 md:grid-cols-3">
            <x-ui.panel class="p-6"><p class="text-sm font-black uppercase tracking-wider text-accent-700">Discover</p><p class="mt-3 font-bold">Find available assessments</p></x-ui.panel>
            <x-ui.panel class="p-6"><p class="text-sm font-black uppercase tracking-wider text-brand-700">Focus</p><p class="mt-3 font-bold">Take distraction-free attempts</p></x-ui.panel>
            <x-ui.panel class="p-6"><p class="text-sm font-black uppercase tracking-wider text-warning">Learn</p><p class="mt-3 font-bold">Understand your next step</p></x-ui.panel>
        </div>

    </section>
</x-layouts.workspace>
