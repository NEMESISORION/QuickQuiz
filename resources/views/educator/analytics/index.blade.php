<x-layouts.workspace title="Analytics" description="Real learner performance analytics for your QuickQuiz assessments.">
    <section>
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div>
                <x-ui.badge tone="accent">Evidence workspace</x-ui.badge>
                <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">Turn attempts into insight.</h1>
                <p class="mt-3 max-w-2xl text-lg leading-8 text-muted">Every metric comes from stored learner submissions and their original question snapshots.</p>
            </div>
            <x-ui.button href="{{ route('educator.quizzes.index') }}" variant="secondary">Open quiz library</x-ui.button>
        </div>

        <x-ui.panel class="mt-8 p-5 sm:p-6">
            <form method="GET" action="{{ route('educator.analytics.index') }}" class="grid gap-4 lg:grid-cols-[minmax(14rem,1fr)_12rem_12rem_auto] lg:items-end">
                <div class="flex flex-col gap-2">
                    <label for="quiz_id" class="text-sm font-bold">Assessment</label>
                    <select id="quiz_id" name="quiz_id" class="min-h-12 rounded-xl border border-line bg-white px-4 py-3">
                        <option value="">All assessments</option>
                        @foreach ($quizzes as $quiz)
                            <option value="{{ $quiz->id }}" @selected($selectedQuizId === $quiz->id)>{{ $quiz->title }}</option>
                        @endforeach
                    </select>
                    @error('quiz_id')<p class="text-sm font-semibold text-danger">{{ $message }}</p>@enderror
                </div>
                <x-ui.input label="From" name="from" type="date" :value="$from" />
                <x-ui.input label="To" name="to" type="date" :value="$to" />
                <div class="flex gap-2"><x-ui.button type="submit">Apply filters</x-ui.button><x-ui.button href="{{ route('educator.analytics.index') }}" variant="secondary">Reset</x-ui.button></div>
            </form>
        </x-ui.panel>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Attempts', $summary['attempts'], 'Started in this view'],
                ['Completion', $summary['completion_rate'].'%', $summary['completed'].' completed'],
                ['Average score', $summary['average_score'].'%', 'Best '.$summary['best_score'].'%'],
                ['Pass rate', $summary['pass_rate'].'%', 'Average '.$summary['average_duration_minutes'].' min'],
            ] as [$label, $value, $support])
                <x-ui.panel class="p-5"><p class="text-sm font-bold text-muted">{{ $label }}</p><p class="mt-2 text-3xl font-black">{{ $value }}</p><p class="mt-2 text-xs font-semibold text-muted">{{ $support }}</p></x-ui.panel>
            @endforeach
        </div>

        @if ($summary['attempts'] === 0)
            <x-ui.panel class="mt-8 border-dashed p-8 text-center sm:p-12">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-muted">No evidence yet</p>
                <h2 class="mt-3 text-2xl font-black">No attempts match these filters.</h2>
                <p class="mx-auto mt-2 max-w-xl leading-7 text-muted">Publish an assessment or broaden the selected date range. Analytics appear automatically after learners participate.</p>
            </x-ui.panel>
        @else
            <div class="mt-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                <x-ui.panel class="p-6 sm:p-8">
                    <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[0.16em] text-brand-700">Score trend</p><h2 class="mt-2 text-2xl font-black">Recent performance</h2></div><p class="text-sm text-muted">Latest 14 active days</p></div>
                    <div class="mt-7 flex flex-col gap-4">
                        @forelse ($trend as $day)
                            <div class="grid grid-cols-[3.5rem_minmax(0,1fr)_3rem] items-center gap-3">
                                <p class="text-sm font-bold text-muted">{{ $day['label'] }}</p>
                                <div class="h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-500" style="width: {{ $day['average_score'] }}%"></div></div>
                                <p class="text-right text-sm font-black">{{ $day['average_score'] }}%</p>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-line p-6 text-center text-muted">Completed attempts will create a score trend.</p>
                        @endforelse
                    </div>
                </x-ui.panel>
                <x-ui.panel class="p-6">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-accent-700">Reading the data</p>
                    <h2 class="mt-2 text-xl font-black">Focus where learners struggle.</h2>
                    <p class="mt-3 text-sm leading-6 text-muted">Question cards are ordered from lowest to highest correctness. Distractor selection reveals which wrong answer is most convincing.</p>
                </x-ui.panel>
            </div>

            <section class="mt-10" aria-labelledby="question-analysis-heading">
                <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-black uppercase tracking-[0.16em] text-brand-700">Question analysis</p><h2 id="question-analysis-heading" class="mt-2 text-3xl font-black">Correctness and distractors</h2></div><p class="text-sm text-muted">{{ $questions->count() }} questions with response evidence</p></div>
                <div class="mt-6 grid gap-5 lg:grid-cols-2">
                    @forelse ($questions as $question)
                        <x-ui.panel class="p-6">
                            <div class="flex items-start justify-between gap-4"><div class="min-w-0"><p class="truncate text-xs font-black uppercase tracking-[0.14em] text-muted">{{ $question['quiz_title'] }}</p><h3 class="mt-2 text-lg font-black leading-7">{{ $question['prompt'] }}</h3></div><span class="shrink-0 rounded-xl px-3 py-2 text-sm font-black {{ $question['correctness'] >= 70 ? 'bg-green-50 text-success' : ($question['correctness'] >= 40 ? 'bg-amber-50 text-warning' : 'bg-red-50 text-danger') }}">{{ $question['correctness'] }}%</span></div>
                            <p class="mt-2 text-sm text-muted">{{ $question['responses'] }} responses from {{ $question['presentations'] }} presentations</p>
                            <div class="mt-5 flex flex-col gap-3">
                                @foreach ($question['options'] as $option)
                                    <div>
                                        <div class="flex items-center justify-between gap-3 text-sm"><p class="truncate font-semibold">{{ $option['content'] }} @if ($option['is_correct'])<span class="text-success">· correct</span>@endif</p><p class="shrink-0 font-black">{{ $option['selected'] }} · {{ $option['percentage'] }}%</p></div>
                                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $option['is_correct'] ? 'bg-success' : 'bg-brand-500' }}" style="width: {{ $option['percentage'] }}%"></div></div>
                                    </div>
                                @endforeach
                            </div>
                        </x-ui.panel>
                    @empty
                        <x-ui.panel class="border-dashed p-8 text-center text-muted lg:col-span-2">No answered question snapshots match this view yet.</x-ui.panel>
                    @endforelse
                </div>
            </section>
        @endif
    </section>
</x-layouts.workspace>
