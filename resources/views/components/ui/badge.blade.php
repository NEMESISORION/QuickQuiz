@props(['tone' => 'brand'])

@php
    $toneClasses = match ($tone) {
        'accent' => 'bg-accent-50 text-accent-700 ring-accent-600/20',
        'neutral' => 'bg-slate-100 text-slate-700 ring-slate-600/15',
        default => 'bg-brand-50 text-brand-700 ring-brand-600/20',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset '.$toneClasses]) }}>
    {{ $slot }}
</span>
