<x-layouts.workspace title="Educator workspace" description="Create and manage thoughtful assessments in QuickQuiz.">
    <section class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
        <div>
            <x-ui.badge>Educator workspace</x-ui.badge>
            <h1 class="mt-5 max-w-3xl text-4xl font-black tracking-tight sm:text-5xl">Build assessment experiences with purpose.</h1>
            <p class="mt-4 max-w-2xl text-lg leading-8 text-muted">Your identity and permissions are ready. Quiz authoring arrives next with deliberate stages for drafting, previewing, and publishing.</p>

            <div class="mt-9 grid gap-4 sm:grid-cols-2">
                <x-ui.panel class="p-6">
                    <p class="text-sm font-black uppercase tracking-wider text-brand-700">Authoring</p>
                    <h2 class="mt-3 text-xl font-bold">Create with confidence</h2>
                    <p class="mt-2 leading-7 text-muted">Structured quiz creation and validation are planned for Round 3.</p>
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
            <h2 class="mt-3 text-xl font-black">Ready for authoring</h2>
            <dl class="mt-6 flex flex-col gap-4 text-sm">
                <div class="flex items-center justify-between gap-4"><dt class="text-muted">Identity</dt><dd class="font-bold text-success">Verified locally</dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-muted">Role</dt><dd class="font-bold">Educator</dd></div>
                <div class="flex items-center justify-between gap-4"><dt class="text-muted">Access</dt><dd class="font-bold">Policy protected</dd></div>
            </dl>
        </x-ui.panel>
    </section>
</x-layouts.workspace>
