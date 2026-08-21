{{-- Signature "phase rail" component.
     Usage: @include('partials.phase-rail', ['currentIndex' => $project->currentPhaseIndex(), 'mini' => true, 'project' => $project]) --}}
@php
    $mini = $mini ?? false;
    $projectPhases = isset($project) && $project->phases && $project->phases->count() > 0 ? $project->phases : null;
@endphp

@if ($projectPhases)
  <div class="phase-rail {{ $mini ? 'mini' : '' }}">
    @foreach ($projectPhases as $i => $phase)
      @php
        $status = $phase->status;
        $cls = in_array($status, ['Done', 'Completed', 'Closed']) ? 'done' : ($status === 'In Progress' ? 'active' : '');
      @endphp
      <div class="phase-step {{ $cls }}" title="{{ $phase->phase_name }} ({{ $phase->status }})">
        <div class="connector"></div>
        <div class="node">{{ in_array($status, ['Done', 'Completed', 'Closed']) ? '✓' : ($i + 1) }}</div>
        <div class="lbl">{{ $phase->phase_name }}</div>
      </div>
    @endforeach
  </div>
@else
  @php
    $phaseLabels = ['Initiation', 'Planning', 'Execution', 'Monitoring', 'Closure'];
    $currentIndex = $currentIndex ?? 0;
  @endphp
  <div class="phase-rail {{ $mini ? 'mini' : '' }}">
    @foreach ($phaseLabels as $i => $label)
      @php
        $cls = $i < $currentIndex ? 'done' : ($i === $currentIndex ? 'active' : '');
      @endphp
      <div class="phase-step {{ $cls }}">
        <div class="connector"></div>
        <div class="node">{{ $i < $currentIndex ? '✓' : ($i + 1) }}</div>
        <div class="lbl">{{ $label }}</div>
      </div>
    @endforeach
  </div>
@endif
