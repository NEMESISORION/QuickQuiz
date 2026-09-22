<x-layouts.workspace title="Notifications" description="Your latest QuickQuiz activity updates.">
    <section class="mx-auto max-w-4xl">
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div>
                <x-ui.badge tone="accent">Activity center</x-ui.badge>
                <h1 class="mt-4 text-4xl font-black tracking-tight sm:text-5xl">Notifications</h1>
                <p class="mt-3 max-w-2xl text-lg leading-8 text-muted">Important assessment completions and achievements, kept in one quiet place.</p>
            </div>
            @if ($notifications->contains(fn ($notification) => $notification->read_at === null))
                <form method="POST" action="{{ route('notifications.read.store') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Mark all as read</x-ui.button>
                </form>
            @endif
        </div>

        @if (session('status'))
            <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-success" role="status">{{ session('status') }}</div>
        @endif

        <div class="mt-8 flex flex-col gap-4">
            @forelse ($notifications as $notification)
                @php
                    $kind = data_get($notification->data, 'kind');
                    $actionUrl = match ($kind) {
                        'attempt_completed' => route('educator.quizzes.results.index', data_get($notification->data, 'quiz_id')),
                        'certificate_earned' => route('certificates.show', data_get($notification->data, 'verification_code')),
                        default => route('notifications.index'),
                    };
                @endphp
                <x-ui.panel as="article" @class(['p-5 sm:p-6', 'border-brand-200 bg-brand-50/60' => $notification->read_at === null])>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="font-black">{{ $kind === 'certificate_earned' ? 'Certificate earned' : 'Attempt completed' }}</h2>
                                @if ($notification->read_at === null)<x-ui.badge>New</x-ui.badge>@endif
                            </div>
                            <p class="mt-2 leading-7 text-muted">{{ data_get($notification->data, 'message', 'A QuickQuiz update is available.') }}</p>
                            <a href="{{ $actionUrl }}" class="mt-3 inline-flex min-h-11 items-center font-bold text-brand-700 underline decoration-brand-200 underline-offset-4 hover:text-brand-950">View details</a>
                        </div>
                        <time class="shrink-0 text-xs font-bold text-muted" datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                    </div>
                </x-ui.panel>
            @empty
                <x-ui.panel class="border-dashed p-8 text-center sm:p-12">
                    <p class="text-xs font-black uppercase tracking-[0.16em] text-muted">All quiet</p>
                    <h2 class="mt-3 text-2xl font-black">No notifications yet.</h2>
                    <p class="mx-auto mt-2 max-w-xl leading-7 text-muted">Assessment completions and earned certificates will appear here when they happen.</p>
                    <x-ui.button href="{{ route('dashboard') }}" class="mt-6" variant="secondary">Return to workspace</x-ui.button>
                </x-ui.panel>
            @endforelse
        </div>

        @if ($notifications->hasPages())<div class="mt-8">{{ $notifications->links() }}</div>@endif
    </section>
</x-layouts.workspace>
