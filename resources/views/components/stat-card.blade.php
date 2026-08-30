@props(['title', 'value', 'icon' => null, 'delta' => null, 'deltaClass' => null, 'color' => null])

<div {{ $attributes->merge(['class' => 'card stat-card']) }}>
    <div class="stat-label">
        {{ $title }}
        @if ($icon)<span aria-hidden="true">{{ $icon }}</span>@endif
    </div>
    <div class="stat-value" @if ($color) style="color:{{ $color }};" @endif>{{ $value }}</div>
    @if ($delta !== null && $delta !== '')
        <div class="stat-delta {{ $deltaClass }}">{{ $delta }}</div>
    @endif
    {{ $slot }}
</div>
