@props([
    'label',
    'name',
    'type' => 'text',
    'revealable' => false,
])

@php
    $hasError = $errors->has($name);
    $describedBy = trim(($attributes->get('aria-describedby') ?? '').' '.($hasError ? $name.'-error' : ''));
    $inputClasses = 'min-h-12 w-full rounded-xl border bg-white px-4 py-3 text-base text-ink shadow-sm transition placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-100 '.($hasError ? 'border-danger' : 'border-line').($revealable ? ' pr-20' : '');
@endphp

<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="text-sm font-bold text-ink">{{ $label }}</label>
    <div @if ($revealable) class="relative" @endif>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->except('aria-describedby')->merge(['class' => $inputClasses]) }}
    >
    @if ($revealable && $type === 'password')
        <button type="button" data-password-toggle="{{ $name }}" aria-label="Show {{ strtolower($label) }}" aria-pressed="false" class="absolute inset-y-0 right-3 min-w-12 text-sm font-bold text-brand-700 hover:text-brand-950 focus-visible:rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">Show</button>
    @endif
    </div>
    @error($name)
        <p id="{{ $name }}-error" class="text-sm font-semibold text-danger">{{ $message }}</p>
    @enderror
</div>
