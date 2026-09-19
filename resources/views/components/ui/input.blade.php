@props([
    'label',
    'name',
    'type' => 'text',
])

@php
    $hasError = $errors->has($name);
    $describedBy = trim(($attributes->get('aria-describedby') ?? '').' '.($hasError ? $name.'-error' : ''));
    $inputClasses = 'min-h-12 w-full rounded-xl border bg-white px-4 py-3 text-base text-ink shadow-sm transition placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-100 '.($hasError ? 'border-danger' : 'border-line');
@endphp

<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="text-sm font-bold text-ink">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->except('aria-describedby')->merge(['class' => $inputClasses]) }}
    >
    @error($name)
        <p id="{{ $name }}-error" class="text-sm font-semibold text-danger">{{ $message }}</p>
    @enderror
</div>
