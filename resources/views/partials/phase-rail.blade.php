{{-- Signature "phase rail" component.
     Usage: @include('partials.phase-rail', ['currentIndex' => $project->currentPhaseIndex(), 'mini' => true]) --}}
@php
    $phaseLabels = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];
    $mini = $mini ?? false;
@endphp
<div class="phase-rail {{ $mini ? 'mini' : '' }}">
  @foreach ($phaseLabels as $i => $label)
    @php
        $cls = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'active' : '');
    @endphp
    <div class="phase-step {{ $cls }}">
      <div class="connector"></div>
      <div class="node">{{ $i + 1 }}</div>
      <div class="lbl">{{ $label }}</div>
    </div>
  @endforeach
</div>
