@props([
    'disabled' => false,
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $variantClasses = match ($variant) {
        'secondary' => 'border border-line bg-white text-ink shadow-sm hover:border-brand-200 hover:bg-brand-50',
        'dark' => 'bg-ink text-white shadow-sm hover:bg-slate-800',
        default => 'bg-brand-600 text-white shadow-lg shadow-brand-600/20 hover:bg-brand-700',
    };

    $classes = 'inline-flex min-h-11 items-center justify-center rounded-xl px-5 py-2.5 text-sm font-bold transition disabled:cursor-not-allowed disabled:opacity-55 '.$variantClasses;
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
