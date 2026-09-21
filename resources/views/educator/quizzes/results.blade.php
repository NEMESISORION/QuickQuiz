<x-layouts.workspace :title="'Results · '.$quiz->title" description="Learner results and performance summary for this assessment.">
    <nav aria-label="Breadcrumb" class="text-sm font-bold text-muted"><a href="{{ route('educator.quizzes.index') }}" class="hover:text-brand-700">Quiz library</a><span aria-hidden="true" class="px-2">/</span><a href="{{ route('educator.quizzes.show', $quiz) }}" class="hover:text-brand-700">{{ $quiz->title }}</a><span aria-hidden="true" class="px-2">/</span><span class="text-ink">Results</span></nav>

    <section class="mt-6">
        <p class="text-sm font-black uppercase tracking-[0.16em] text-brand-700">Educator results</p>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-5"><div><h1 class="text-3xl font-black tracking-tight sm:text-5xl">{{ $quiz->title }}</h1><p class="mt-3 text-lg text-muted">A secure overview of learner attempts and outcomes.</p></div><x-ui.button href="{{ route('educator.quizzes.show', $quiz) }}" variant="secondary">Back to quiz</x-ui.button></div>

        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Attempts', $summary['total']], ['Completed', $summary['completed']], ['Average score', $summary['average_percentage'].'%'], ['Pass rate', $summary['pass_rate'].'%']] as [$label, $value])
                <x-ui.panel class="p-5"><p class="text-sm font-bold text-muted">{{ $label }}</p><p class="mt-2 text-3xl font-black">{{ $value }}</p></x-ui.panel>
            @endforeach
        </div>

        <div class="mt-8 flex flex-wrap gap-2" aria-label="Filter results">
            @foreach ($filters as $availableFilter)
                <a href="{{ route('educator.quizzes.results.index', ['quiz' => $quiz, 'status' => $availableFilter->value]) }}" class="inline-flex min-h-11 items-center rounded-xl border px-4 text-sm font-bold transition {{ $filter === $availableFilter ? 'border-brand-600 bg-brand-600 text-white' : 'border-line bg-white text-ink hover:border-brand-300' }}" @if ($filter === $availableFilter) aria-current="page" @endif>{{ $availableFilter->label() }}</a>
            @endforeach
        </div>

        <x-ui.panel class="mt-5 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-line bg-canvas text-xs font-black uppercase tracking-wider text-muted"><tr><th class="px-5 py-4">Learner</th><th class="px-5 py-4">Attempt</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Score</th><th class="px-5 py-4">Duration</th><th class="px-5 py-4">Started</th></tr></thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($attempts as $attempt)
                            @php
                                $statusLabel = match (true) {
                                    $attempt->status === \App\Enums\QuizAttemptStatus::InProgress => 'In progress',
                                    $attempt->status === \App\Enums\QuizAttemptStatus::Expired => 'Time expired',
                                    $attempt->passed === true => 'Passed',
                                    default => 'Not passed',
                                };
                                $statusTone = match (true) {
                                    $attempt->status === \App\Enums\QuizAttemptStatus::InProgress => 'brand',
                                    $attempt->passed === true => 'success',
                                    default => 'warning',
                                };
                            @endphp
                            <tr><td class="px-5 py-4"><p class="font-black">{{ $attempt->learner->name }}</p><p class="mt-1 text-xs text-muted">{{ $attempt->learner->email }}</p></td><td class="px-5 py-4 font-bold">#{{ $attempt->attempt_number }}</td><td class="px-5 py-4"><x-ui.badge :tone="$statusTone">{{ $statusLabel }}</x-ui.badge></td><td class="px-5 py-4 font-black">{{ $attempt->score !== null ? $attempt->score.' / '.$attempt->max_score : '—' }}</td><td class="px-5 py-4 text-muted">{{ $attempt->submitted_at ? $attempt->submitted_at->diffForHumans($attempt->started_at, true) : 'Active' }}</td><td class="px-5 py-4 text-muted">{{ $attempt->started_at->format('M j, Y · g:i A') }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center"><p class="text-lg font-black">No matching attempts</p><p class="mt-2 text-muted">Results will appear here as learners participate.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.panel>
        @if ($attempts->hasPages())<div class="mt-6">{{ $attempts->links() }}</div>@endif
    </section>
</x-layouts.workspace>
