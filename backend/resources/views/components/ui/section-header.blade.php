@props(['eyebrow' => null, 'title'])

<div {{ $attributes->merge(['class' => 'spread']) }}>
    <div>
        @if($eyebrow)
            <p class="eyebrow">{{ $eyebrow }}</p>
        @endif
        <h2>{{ $title }}</h2>
    </div>
    @isset($action)
        <div>{{ $action }}</div>
    @endisset
</div>
