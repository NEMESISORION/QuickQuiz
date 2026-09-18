@props(['as' => 'div'])

@if ($as === 'article')
    <article {{ $attributes->merge(['class' => 'rounded-2xl border border-line bg-white shadow-panel']) }}>
        {{ $slot }}
    </article>
@else
    <div {{ $attributes->merge(['class' => 'rounded-2xl border border-line bg-white shadow-panel']) }}>
        {{ $slot }}
    </div>
@endif
