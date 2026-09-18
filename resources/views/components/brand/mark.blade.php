@props(['compact' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="grid size-10 place-items-center rounded-xl bg-brand-600 text-white shadow-lg shadow-brand-600/20" aria-hidden="true">
        <svg viewBox="0 0 24 24" class="size-5" fill="none">
            <path d="M13.7 2.75 5.5 13.1h5.35l-.7 8.15 8.35-11.1h-5.45l.65-7.4Z" fill="currentColor" />
        </svg>
    </span>
    @unless ($compact)
        <span class="text-lg font-bold tracking-tight text-ink">QuickQuiz</span>
    @endunless
</span>
