@props(['value' => 0, 'label' => null])

@php
    $safeValue = max(0, min(100, (int) $value));
@endphp

<div {{ $attributes->merge(['class' => 'progress-track', 'role' => 'progressbar', 'aria-valuemin' => '0', 'aria-valuemax' => '100', 'aria-valuenow' => $safeValue, 'aria-label' => $label ?? 'Progresso']) }}>
    <div class="progress-fill" style="width: {{ $safeValue }}%"></div>
</div>
