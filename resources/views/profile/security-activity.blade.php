<x-layouts.workspace title="Security activity" description="Review recent security events for your QuickQuiz account.">
    <section class="mx-auto max-w-4xl">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.18em] text-brand-700">Account security</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight">Recent activity</h1>
                <p class="mt-3 max-w-2xl leading-7 text-muted">Review the latest identity events recorded for your account. Passwords, reset tokens, and raw email addresses are never stored here.</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="inline-flex min-h-11 shrink-0 items-center font-bold text-brand-700 underline decoration-brand-200 underline-offset-4 hover:text-brand-950">Back to profile</a>
        </div>

        <x-ui.panel class="mt-8 overflow-hidden">
            @if ($events->isEmpty())
                <div class="p-8 text-center">
                    <h2 class="text-xl font-black">No activity recorded yet</h2>
                    <p class="mt-2 text-muted">New sign-ins and account changes will appear here.</p>
                </div>
            @else
                <ol class="divide-y divide-line">
                    @foreach ($events as $event)
                        <li class="grid gap-3 p-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start sm:p-6">
                            <div class="flex min-w-0 gap-4">
                                <span class="mt-1 grid size-10 shrink-0 place-items-center rounded-xl bg-brand-50 font-black text-brand-700" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <div class="min-w-0">
                                    <h2 class="font-black text-ink">{{ $event->event->label() }}</h2>
                                    <p class="mt-1 text-sm leading-6 text-muted">{{ $event->event->description() }}</p>
                                    <p class="mt-2 text-xs font-semibold text-muted">IP address: {{ $event->ip_address ?? 'Unavailable' }}</p>
                                </div>
                            </div>
                            <time datetime="{{ $event->created_at->toIso8601String() }}" title="{{ $event->created_at->toDayDateTimeString() }}" class="text-sm font-semibold text-muted sm:text-right">
                                {{ $event->created_at->diffForHumans() }}
                            </time>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-ui.panel>
    </section>
</x-layouts.workspace>
