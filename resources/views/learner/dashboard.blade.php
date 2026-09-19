<x-layouts.workspace title="Learner workspace" description="A calm place to take assessments and understand results.">
    <section class="max-w-4xl">
        <x-ui.badge tone="accent">Learner workspace</x-ui.badge>
        <h1 class="mt-5 text-4xl font-black tracking-tight sm:text-5xl">Focus on the question in front of you.</h1>
        <p class="mt-4 max-w-2xl text-lg leading-8 text-muted">Your learner workspace is ready. Available assessments and attempt history will appear here once the quiz domain is introduced.</p>

        <div class="mt-10 grid gap-5 md:grid-cols-3">
            <x-ui.panel class="p-6"><p class="text-sm font-black uppercase tracking-wider text-accent-700">Discover</p><p class="mt-3 font-bold">Find available assessments</p></x-ui.panel>
            <x-ui.panel class="p-6"><p class="text-sm font-black uppercase tracking-wider text-brand-700">Focus</p><p class="mt-3 font-bold">Take distraction-free attempts</p></x-ui.panel>
            <x-ui.panel class="p-6"><p class="text-sm font-black uppercase tracking-wider text-warning">Learn</p><p class="mt-3 font-bold">Understand your next step</p></x-ui.panel>
        </div>

        <div class="mt-8 rounded-2xl border border-dashed border-line bg-white/70 p-8 text-center">
            <h2 class="text-xl font-black">No assessments available yet</h2>
            <p class="mt-2 text-muted">When an educator publishes or assigns a quiz, it will appear here.</p>
        </div>
    </section>
</x-layouts.workspace>
