<x-layouts.marketing title="Modern assessment, thoughtfully delivered">
    <section class="relative isolate overflow-hidden">
        <div class="absolute inset-x-0 top-0 -z-10 h-[34rem] bg-[radial-gradient(circle_at_72%_18%,rgba(99,102,241,0.18),transparent_34%),radial-gradient(circle_at_88%_45%,rgba(20,184,166,0.14),transparent_24%)]" aria-hidden="true"></div>

        <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[1.02fr_0.98fr] lg:py-28">
            <div class="flex min-w-0 flex-col items-start gap-7">
                <p class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white px-3.5 py-2 text-xs font-bold uppercase tracking-[0.16em] text-brand-700 shadow-sm">
                    <span class="size-2 rounded-full bg-accent-500" aria-hidden="true"></span>
                    Built around better assessment
                </p>

                <div class="flex max-w-3xl flex-col gap-5">
                    <h1 class="text-balance text-4xl font-black tracking-[-0.04em] text-ink sm:text-5xl lg:text-6xl lg:leading-[1.06]">
                        Make every quiz feel clear, fair, and worth taking.
                    </h1>
                    <p class="max-w-2xl text-pretty text-lg leading-8 text-muted sm:text-xl">
                        QuickQuiz gives educators a deliberate authoring workflow and gives learners a calm place to focus, respond, and understand what comes next.
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <x-ui.button href="{{ route('register') }}" class="min-h-12 px-6 py-3 text-base">
                        Create your account
                    </x-ui.button>
                    <x-ui.button href="#principles" variant="secondary" class="min-h-12 px-6 py-3 text-base">
                        See the product principles
                    </x-ui.button>
                </div>

                <dl class="grid w-full max-w-xl grid-cols-1 gap-4 border-t border-line pt-6 sm:grid-cols-3 sm:gap-3">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">For learners</dt>
                        <dd class="mt-1 font-bold text-ink">Focused attempts</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">For educators</dt>
                        <dd class="mt-1 font-bold text-ink">Clear insights</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-muted">For everyone</dt>
                        <dd class="mt-1 font-bold text-ink">Accessible UX</dd>
                    </div>
                </dl>
            </div>

            <div id="experience" class="relative min-w-0 max-w-full scroll-mt-24" aria-label="Static assessment interface preview">
                <div class="absolute -inset-5 -z-10 rounded-[2.5rem] bg-gradient-to-br from-brand-100 via-white to-accent-100 blur-2xl" aria-hidden="true"></div>
                <div class="w-full max-w-full overflow-hidden rounded-3xl border border-white/70 bg-white shadow-soft ring-1 ring-slate-900/5">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <x-brand.mark compact />
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-muted">Interface preview</p>
                                <p class="font-bold text-ink">Design Foundations</p>
                            </div>
                        </div>
                        <x-ui.badge tone="accent">Preview only</x-ui.badge>
                    </div>

                    <div class="grid gap-6 p-5 sm:p-7">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-sm font-bold text-ink">Question 3 of 8</span>
                                <span class="text-sm text-muted">Core accessibility</span>
                            </div>
                            <div class="rounded-xl border border-line bg-canvas px-3 py-2 text-right">
                                <span class="block text-xs font-bold uppercase tracking-wider text-muted">Time left</span>
                                <span class="font-mono text-lg font-bold text-ink">08:42</span>
                            </div>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-slate-100" aria-label="Quiz progress: 38 percent">
                            <div class="h-full w-[38%] rounded-full bg-brand-600"></div>
                        </div>

                        <fieldset class="flex flex-col gap-3" disabled>
                            <legend class="mb-2 text-lg font-bold leading-7 text-ink">What makes assessment feedback genuinely useful?</legend>
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line p-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                                <input class="mt-1 size-4 accent-brand-600" type="radio" name="preview-answer">
                                <span>
                                    <span class="block font-semibold text-ink">It explains the next learning step</span>
                                    <span class="mt-1 block text-sm leading-6 text-muted">Feedback connects the result to an action the learner can take.</span>
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line p-4 transition hover:border-brand-200 hover:bg-brand-50/50">
                                <input class="mt-1 size-4 accent-brand-600" type="radio" name="preview-answer">
                                <span class="font-semibold text-ink">It only displays the final percentage</span>
                            </label>
                        </fieldset>

                        <div class="flex items-center justify-between gap-4 border-t border-line pt-5">
                            <span class="text-sm font-medium text-muted">Answers save automatically</span>
                            <x-ui.button variant="dark" disabled>Next question</x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="foundation" class="scroll-mt-20 border-y border-line bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-7xl px-5 sm:px-8">
            <div class="max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-brand-700">One system, two focused workspaces</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-ink sm:text-4xl">The interface changes with the job at hand.</h2>
                <p class="mt-4 text-lg leading-8 text-muted">Educators get efficient controls and real evidence. Learners get a quiet, predictable environment with no decorative distractions.</p>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <x-ui.panel as="article" class="bg-canvas p-6">
                    <span class="grid size-11 place-items-center rounded-xl bg-brand-100 font-black text-brand-700" aria-hidden="true">01</span>
                    <h3 class="mt-6 text-xl font-bold text-ink">Deliberate authoring</h3>
                    <p class="mt-3 leading-7 text-muted">Build in clear stages, preview the learner experience, then publish with confidence.</p>
                </x-ui.panel>
                <x-ui.panel as="article" class="bg-canvas p-6">
                    <span class="grid size-11 place-items-center rounded-xl bg-accent-100 font-black text-accent-700" aria-hidden="true">02</span>
                    <h3 class="mt-6 text-xl font-bold text-ink">Calm assessment</h3>
                    <p class="mt-3 leading-7 text-muted">See progress, timing, and save state without competing with the question itself.</p>
                </x-ui.panel>
                <x-ui.panel as="article" class="bg-canvas p-6">
                    <span class="grid size-11 place-items-center rounded-xl bg-amber-100 font-black text-warning" aria-hidden="true">03</span>
                    <h3 class="mt-6 text-xl font-bold text-ink">Evidence that helps</h3>
                    <p class="mt-3 leading-7 text-muted">Turn attempts into useful feedback and question-level insight—not vanity metrics.</p>
                </x-ui.panel>
            </div>
        </div>
    </section>

    <section id="principles" class="scroll-mt-20 py-16 sm:py-20">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-accent-700">Product principles</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-ink">Every screen answers a real assessment need.</h2>
                <p class="mt-4 max-w-xl text-lg leading-8 text-muted">QuickQuiz separates discovery, management, and focused test-taking so each person sees the right information at the right moment.</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['Discover', 'Understand availability, expectations, and the next useful action.'],
                    ['Manage', 'Author and review assessments with clear status and evidence.'],
                    ['Focus', 'Answer without distraction while progress and save state stay visible.'],
                    ['Learn', 'Turn results into feedback that explains what to do next.'],
                ] as [$mode, $description])
                    <x-ui.panel class="p-5">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-brand-700">{{ $mode }}</p>
                        <p class="mt-2 font-semibold leading-6 text-ink">{{ $description }}</p>
                    </x-ui.panel>
                @endforeach
            </div>
        </div>
    </section>
</x-layouts.marketing>
