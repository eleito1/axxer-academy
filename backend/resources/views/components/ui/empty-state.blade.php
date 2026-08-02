@props(['icon' => 'AX', 'title'])

<section {{ $attributes->merge(['class' => 'empty-state']) }}>
    <div class="empty-icon" aria-hidden="true">{{ $icon }}</div>
    <h2>{{ $title }}</h2>
    <div>{{ $slot }}</div>
</section>
