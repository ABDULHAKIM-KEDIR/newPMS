@extends('layouts.app')
@section('title', 'Calendar')
@section('crumb', 'Calendar')

@section('content')
@php
    $firstDayOfWeek = $currentDate->copy()->startOfMonth()->dayOfWeek;
    $daysInMonth = $currentDate->daysInMonth;
    $daysInPrevMonth = $prevMonth->daysInMonth;
@endphp

<div class="page-head">
    <div>
        <h1>Project &amp; Task Calendar</h1>
        <div class="page-sub">Upcoming delivery milestones, task deadlines, and team schedules for
            {{ $currentDate->format('F Y') }}</div>
    </div>

    <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <!-- Navigation Buttons -->
        <a href="{{ route('calendar.index', ['year' => $prevMonth->year, 'month' => $prevMonth->month, 'project_id' => $projectId, 'team_id' => $teamId]) }}"
            class="btn btn-ghost" style="padding:6px 12px; font-size:12.5px;">
            ← {{ $prevMonth->format('M') }}
        </a>
        <span
            style="font-weight:700; font-size:15px; padding:0 6px; color:var(--ink);">{{ $currentDate->format('F Y') }}</span>
        <a href="{{ route('calendar.index', ['year' => $nextMonth->year, 'month' => $nextMonth->month, 'project_id' => $projectId, 'team_id' => $teamId]) }}"
            class="btn btn-ghost" style="padding:6px 12px; font-size:12.5px;">
            {{ $nextMonth->format('M') }} →
        </a>

        <!-- Filters -->
        <form method="GET" action="{{ route('calendar.index') }}"
            style="display:inline-flex; gap:6px; margin-left:8px;">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">

            <select name="project_id" onchange="this.form.submit()"
                style="border:1px solid var(--line); border-radius:6px; padding:5px 10px; font-size:12px; font-family:inherit; background:var(--surface);">
                <option value="">All Projects</option>
                @foreach ($allProjects as $p)
                    <option value="{{ $p->project_id }}" {{ (string) $projectId === (string) $p->project_id ? 'selected' : '' }}>{{ $p->project_name }}</option>
                @endforeach
            </select>

            <select name="team_id" onchange="this.form.submit()"
                style="border:1px solid var(--line); border-radius:6px; padding:5px 10px; font-size:12px; font-family:inherit; background:var(--surface);">
                <option value="">All Teams</option>
                @foreach ($allTeams as $t)
                    <option value="{{ $t->team_id }}" {{ (string) $teamId === (string) $t->team_id ? 'selected' : '' }}>
                        {{ $t->team_name }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<!-- Monthly Calendar Grid -->
<div class="card card-pad" style="padding:0; overflow:hidden;">
  <div style="display:grid; grid-template-columns:repeat(7, 1fr); background:var(--bg-subtle); border-bottom:1px solid var(--line); text-align:center; font-weight:700; font-size:12px; color:var(--ink-soft); text-transform:uppercase; padding:10px 0;">
    <div>Sun</div>
    <div>Mon</div>
    <div>Tue</div>
    <div>Wed</div>
    <div>Thu</div>
    <div>Fri</div>
    <div>Sat</div>
  </div>

  <div style="display:grid; grid-template-columns:repeat(7, 1fr); grid-auto-rows:minmax(110px, auto); gap:1px; background:var(--line);">
    <!-- Previous Month Days -->
    @for ($i = 0; $i < $firstDayOfWeek; $i++)
      @php $prevDayNum = $daysInPrevMonth - $firstDayOfWeek + $i + 1; @endphp
      <div style="background:var(--bg-subtle); padding:8px; opacity:0.4;">
        <span style="font-size:12px; font-weight:600; color:var(--ink-muted);">{{ $prevDayNum }}</span>
      </div>
    @endfor

    <!-- Current Month Days -->
    @for ($day = 1; $day <= $daysInMonth; $day++)
      @php
        $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $isToday = $dateStr === now()->toDateString();
        $dayTasks = $tasksByDate[$dateStr] ?? [];
        $dayProjects = $projectsByDate[$dateStr] ?? [];
      @endphp
      <div style="background:var(--bg-card); padding:8px; display:flex; flex-direction:column; justify-content:space-between; position:relative; {{ $isToday ? 'border:2px solid var(--accent);' : '' }}">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
            <span style="font-size:12.5px; font-weight:{{ $isToday ? '800' : '600' }}; color:{{ $isToday ? 'var(--accent)' : 'var(--ink)' }};">
              {{ $day }}
              @if ($isToday)
                <span class="badge b-active" style="font-size:9px; padding:1px 4px; margin-left:2px;">Today</span>
              @endif
            </span>
          </div>

          <!-- Project Milestones -->
          @foreach ($dayProjects as $pMilestone)
            <div style="background:var(--primary-soft); color:var(--primary-dark); font-size:10.5px; font-weight:700; padding:2px 6px; border-radius:4px; margin-bottom:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $pMilestone->project_name }}">
              🚀 {{ $pMilestone->project_name }}
            </div>
          @endforeach

          <!-- Tasks Due -->
          @foreach (array_slice($dayTasks, 0, 3) as $cTask)
            @php $isDone = in_array($cTask->status, ['Done', 'Completed']); @endphp
            <div
              onclick="openTask({{ $cTask->task_id }})"
              style="background:var(--bg-subtle); border-left:3px solid {{ $isDone ? 'var(--success)' : ($cTask->priority === 'High' ? 'var(--danger)' : 'var(--accent)') }}; font-size:11px; padding:3px 6px; border-radius:3px; margin-bottom:3px; cursor:pointer; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
              title="{{ $cTask->task_name }} ({{ $cTask->status }})"
            >
              <span style="{{ $isDone ? 'text-decoration:line-through; color:var(--ink-muted);' : 'color:var(--ink); font-weight:600;' }}">
                {{ $cTask->task_name }}
              </span>
            </div>
          @endforeach

          @if (count($dayTasks) > 3)
            <div style="font-size:10px; color:var(--ink-muted); font-weight:600; margin-top:2px;">
              +{{ count($dayTasks) - 3 }} more
            </div>
          @endif
        </div>
      </div>
    @endfor

    <!-- Next Month Fill Days -->
    @php
      $totalCells = $firstDayOfWeek + $daysInMonth;
      $remainingCells = (7 - ($totalCells % 7)) % 7;
    @endphp
    @for ($j = 1; $j <= $remainingCells; $j++)
      <div style="background:var(--bg-subtle); padding:8px; opacity:0.4;">
        <span style="font-size:12px; font-weight:600; color:var(--ink-muted);">{{ $j }}</span>
      </div>
    @endfor
  </div>
</div>
@endsection
