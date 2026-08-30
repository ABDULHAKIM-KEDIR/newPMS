@extends('layouts.app')
@section('title', 'Budgets')
@section('crumb', 'Budgets')

@section('content')
<div x-data="{ editModal: false, selectedProject: null, allocated: 0, spent: 0 }">
  <div class="page-head">
    <div>
      <h1>Directorate Budgets</h1>
      <div class="page-sub">Financial allocation, expenditure tracking, and utilization across all ICT projects</div>
    </div>
  </div>

  <div class="grid grid-4" style="margin-bottom:20px;">
    <div class="card stat-card">
      <div class="stat-label">Total Allocated</div>
      <div class="stat-value" style="font-size:20px;">ETB {{ number_format($totalAllocated) }}</div>
      <div class="stat-delta">Across {{ $projects->count() }} budgeted projects</div>
    </div>
    <div class="card stat-card">
      <div class="stat-label">Total Spent</div>
      <div class="stat-value" style="font-size:20px;">ETB {{ number_format($totalSpent) }}</div>
      <div class="stat-delta">Actual expenditure</div>
    </div>
    <div class="card stat-card">
      <div class="stat-label">Remaining Balance</div>
      <div class="stat-value" style="font-size:20px; color:var(--success);">ETB {{ number_format($totalRemaining) }}</div>
      <div class="stat-delta">Available funds</div>
    </div>
    <div class="card stat-card">
      <div class="stat-label">Overall Utilisation</div>
      <div class="stat-value" style="font-size:20px;">{{ $overallUtilization }}%</div>
      <div class="progressbar {{ $overallUtilization > 85 ? 'danger' : ($overallUtilization > 65 ? 'warn' : '') }}" style="margin-top:6px;">
        <div style="width:{{ $overallUtilization }}%"></div>
      </div>
    </div>
  </div>

  <div class="card">
    <table>
      <thead>
        <tr>
          <th style="width:26%">Project</th>
          <th>Assigned Team</th>
          <th>Phase Breakdown</th>
          <th>Allocated</th>
          <th>Spent</th>
          <th>Utilisation</th>
          @if (auth()->user()->can('manage_budgets') || auth()->user()->isDirectorOrAdmin())
            <th style="text-align:right;">Action</th>
          @endif
        </tr>
      </thead>
      <tbody>
        @forelse ($projects as $p)
          @php
            $b = $p->budget;
            $util = $b ? $b->utilisationPercent() : 0;
          @endphp
          <tr onclick="window.location='{{ route('projects.show', $p) }}'" style="cursor:pointer;">
            <td>
              <div class="cell-primary">{{ $p->project_name }}</div>
              <div class="cell-sub mono">PRJ-{{ str_pad($p->project_id, 3, '0', STR_PAD_LEFT) }} · {{ $p->project_type }}</div>
            </td>
            <td>
              @if ($p->team)
                <a href="{{ route('teams.show', $p->team) }}" onclick="event.stopPropagation();" style="font-weight:600;">
                  {{ $p->team->team_name }}
                </a>
              @else
                <span style="color:var(--ink-faint);">No Team Assigned</span>
              @endif
            </td>
            <td>
              <div style="font-size:11.5px; color:var(--ink-soft);">
                {{ $p->phases->count() }} phases · {{ $p->phases->where('status', 'Done')->count() }} completed
              </div>
            </td>
            <td><strong>ETB {{ number_format($b->allocated_amount ?? 0) }}</strong></td>
            <td>ETB {{ number_format($b->spent_amount ?? 0) }}</td>
            <td style="width:160px;">
              <div class="cell-sub" style="margin-bottom:4px; font-weight:600;">{{ $util }}%</div>
              <div class="progressbar {{ $util > 85 ? 'danger' : ($util > 65 ? 'warn' : '') }}">
                <div style="width:{{ $util }}%"></div>
              </div>
            </td>
            @if (auth()->user()->can('manage_budgets') || auth()->user()->isDirectorOrAdmin())
              <td style="text-align:right;">
                <button
                  type="button"
                  class="btn btn-ghost"
                  style="padding:4px 9px; font-size:11.5px;"
                  onclick="event.stopPropagation();"
                  @click="selectedProject = {{ $p->project_id }}; allocated = {{ $b->allocated_amount ?? 0 }}; spent = {{ $b->spent_amount ?? 0 }}; editModal = true;"
                >
                  ⚙ Edit Budget
                </button>
              </td>
            @endif
          </tr>
        @empty
          <tr>
            <td colspan="7" style="text-align:center; padding:30px; color:var(--ink-faint);">No project budgets found.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Quick Budget Edit Modal --}}
  <template x-if="editModal && selectedProject">
    <div>
      <div class="overlay show" @click="editModal = false"></div>
      <div class="card card-pad" style="position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); z-index:1000; width:440px; box-shadow:0 15px 35px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
          <h3 style="margin:0; font-size:16px;">Update Project Budget</h3>
          <button type="button" @click="editModal = false" style="background:none; border:none; font-size:18px; cursor:pointer; color:var(--ink-faint);">&times;</button>
        </div>
        <form :action="'/budgets/projects/' + selectedProject" method="POST">
          @csrf
          <div class="form-field">
            <label>Allocated Amount (ETB)</label>
            <input type="number" step="0.01" min="0" name="allocated_amount" x-model="allocated" required>
          </div>
          <div class="form-field">
            <label>Spent Amount (ETB)</label>
            <input type="number" step="0.01" min="0" name="spent_amount" x-model="spent" required>
          </div>
          <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
            <button type="button" class="btn btn-ghost" @click="editModal = false">Cancel</button>
            <button type="submit" class="btn btn-accent">Save Budget</button>
          </div>
        </form>
      </div>
    </div>
  </template>
</div>
@endsection
