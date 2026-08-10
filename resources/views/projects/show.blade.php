@extends('layouts.app')
@section('title', $project->project_name)
@section('crumb')
  <a class="link-small" style="cursor:pointer;" href="{{ route('projects.index') }}">Projects</a> <b>/ {{ $project->project_name }}</b>
@endsection

@section('content')
@if (session('status'))
  <div class="status-alert">{{ session('status') }}</div>
@endif
<div x-data="{ showCR: false }">
  <div class="page-head">
    <div>
      <h1>{{ $project->project_name }}</h1>
      <div class="page-sub">{{ $project->project_type }} · {{ optional($project->team)->team_name }} Team · Started {{ optional($project->start_date)->format('d M Y') }}</div>
    </div>
    <div style="display:flex; gap:8px;">
      <button class="btn btn-ghost" @click="showCR = !showCR">Log Change Request</button>
      <a href="{{ route('projects.edit', $project) }}" class="btn btn-primary">Edit Project</a>
    </div>
  </div>

  <div class="card card-pad" x-show="showCR" x-cloak x-transition style="margin-bottom:18px;">
    <form method="POST" action="{{ route('projects.changeRequests.store', $project) }}">
      @csrf
      <div class="form-field" style="margin-bottom:12px;">
        <label for="cr_description">What's the change you're requesting?</label>
        <textarea id="cr_description" name="description" required placeholder="e.g. Extend go-live by two weeks to accommodate UAT feedback"></textarea>
      </div>
      <div style="display:flex; gap:10px;">
        <button type="submit" class="btn btn-accent">Submit request</button>
        <button type="button" class="btn btn-ghost" @click="showCR = false">Cancel</button>
      </div>
    </form>
  </div>
</div>

<div class="card card-pad" style="margin-bottom:18px;">
  @include('partials.phase-rail', ['currentIndex' => $project->currentPhaseIndex(), 'mini' => false])
</div>

<div x-data="{ tab: 'tasks' }">
  <div class="tabs">
    <div class="tab" :class="{ active: tab === 'tasks' }" @click="tab = 'tasks'">Tasks</div>
    <div class="tab" :class="{ active: tab === 'deliverables' }" @click="tab = 'deliverables'">Deliverables</div>
    <div class="tab" :class="{ active: tab === 'budget' }" @click="tab = 'budget'">Budget</div>
    <div class="tab" :class="{ active: tab === 'changes' }" @click="tab = 'changes'">Change Requests</div>
  </div>

  <div x-show="tab === 'tasks'">
    <div class="kanban">
      @foreach (['Pending' => 'Pending', 'In Progress' => 'In Progress', 'Done' => 'Done'] as $statusKey => $statusLabel)
        <div>
          <div class="kcol-head">
            <h4>{{ $statusLabel }}</h4>
            <span class="kcol-count">{{ $tasks->where('status', $statusKey)->count() }}</span>
          </div>
          <div class="kcol-body">
            @foreach ($tasks->where('status', $statusKey) as $t)
              @php $late = $t->status !== 'Done' && $t->end_date && $t->end_date->isPast(); @endphp
              <div class="tcard" onclick="openTask({{ $t->task_id }})">
                <div class="id mono">TASK-{{ str_pad($t->task_id, 4, '0', STR_PAD_LEFT) }}</div>
                <div class="name">{{ $t->task_name }}</div>
                <div class="tcard-foot">
                  <span class="priority p-{{ strtolower($t->priority) }}">{{ $t->priority }}</span>
                  <div style="display:flex; align-items:center; gap:8px;">
                    <span class="duedate {{ $late ? 'late' : '' }}">{{ $late ? '⚠ ' : '' }}{{ optional($t->end_date)->format('d M') }}</span>
                    <div class="avatar">{{ optional($t->assignee)->initials() }}</div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <div x-show="tab === 'deliverables'" x-cloak>
    <div class="card"><table>
      <thead><tr><th style="width:40%">Deliverable</th><th>Due date</th><th>Status</th></tr></thead>
      <tbody>
        @foreach ($project->deliverables as $d)
          <tr>
            <td class="cell-primary">{{ $d->deliverable_name }}</td>
            <td>{{ optional($d->due_date)->format('d M Y') }}</td>
            <td><span class="badge {{ $d->status === 'Delivered' ? 'b-active' : 'b-planning' }}"><span class="badge-dot"></span>{{ $d->status }}</span></td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
  </div>

  <div x-show="tab === 'budget'" x-cloak>
    @php $b = $project->budget; $util = $b ? $b->utilisationPercent() : 0; @endphp
    <div class="grid grid-3">
      <div class="card card-pad">
        <div class="card-title-row"><h3>Budget by phase</h3></div>
        @foreach ($project->phases as $ph)
          @php $pb = $ph->budget; $pct = $pb && $pb->allocated_amount > 0 ? round($pb->spent_amount / $pb->allocated_amount * 100) : 0; @endphp
          <div style="margin-bottom:13px;">
            <div style="display:flex; justify-content:space-between; font-size:12.6px; margin-bottom:5px;">
              <span>{{ $ph->phase_name }}</span><span class="mono" style="color:var(--ink-soft)">ETB {{ number_format($pb->allocated_amount ?? 0) }}</span>
            </div>
            <div class="progressbar {{ $pct>85?'danger':($pct>65?'warn':'') }}"><div style="width:{{ $pct }}%"></div></div>
          </div>
        @endforeach
      </div>
      <div class="card card-pad">
        <div class="stat-label">Total allocated</div>
        <div class="stat-value">ETB {{ number_format($b->allocated_amount ?? 0) }}</div>
        <div style="margin:14px 0 6px;" class="stat-label">Spent — {{ $util }}%</div>
        <div class="progressbar"><div style="width:{{ $util }}%"></div></div>
        <div class="stat-delta" style="margin-top:16px;">ETB {{ number_format($b->spent_amount ?? 0) }} spent</div>
      </div>
    </div>
  </div>

  <div x-show="tab === 'changes'" x-cloak>
    <div class="card"><table>
      <thead><tr><th style="width:34%">Request</th><th>Requested by</th><th>Date</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @foreach ($project->changeRequests as $c)
          @php $ccls = ['Approved' => 'b-active', 'Rejected' => 'b-blocked'][$c->status] ?? 'b-risk'; @endphp
          <tr>
            <td class="cell-primary">{{ $c->description }}</td>
            <td>{{ optional($c->requester)->full_name }}</td>
            <td>{{ optional($c->requested_date)->format('d M Y') }}</td>
            <td><span class="badge {{ $ccls }}"><span class="badge-dot"></span>{{ $c->status }}</span></td>
            <td style="text-align:right;">
              @if ($c->status === 'Pending' && auth()->user()->isDirectorOrAdmin())
                <form method="POST" action="{{ route('changeRequests.approve', $c) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="btn btn-ghost" style="padding:5px 11px; font-size:11.5px; color:var(--success); border-color:var(--success-soft);">Approve</button>
                </form>
                <form method="POST" action="{{ route('changeRequests.reject', $c) }}" style="display:inline;">
                  @csrf
                  <button type="submit" class="btn btn-ghost" style="padding:5px 11px; font-size:11.5px; color:var(--danger); border-color:var(--danger-soft);">Reject</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
  </div>
</div>
@endsection
