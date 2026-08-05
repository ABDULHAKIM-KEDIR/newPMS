@extends('layouts.app')
@section('title', 'My Tasks')
@section('crumb', '<b>My Tasks</b>')

@section('content')
<div class="page-head">
  <div><h1>My Tasks</h1><div class="page-sub">Everything assigned to you, across every project</div></div>
</div>

<div class="card">
  <table>
    <thead><tr><th style="width:32%">Task</th><th>Project</th><th>Priority</th><th>Status</th><th>Due</th></tr></thead>
    <tbody>
      @forelse ($tasks as $t)
        <tr onclick="openTask({{ $t->task_id }})">
          <td class="cell-primary">{{ $t->task_name }}<div class="cell-sub mono">TASK-{{ str_pad($t->task_id, 4, '0', STR_PAD_LEFT) }}</div></td>
          <td>{{ optional(optional($t->phase)->project)->project_name }}</td>
          <td><span class="priority p-{{ strtolower($t->priority) }}">{{ $t->priority }}</span></td>
          <td>
            @php $cls = ['Done' => 'b-active', 'In Progress' => 'b-planning'][$t->status] ?? 'b-risk'; @endphp
            <span class="badge {{ $cls }}"><span class="badge-dot"></span>{{ $t->status }}</span>
          </td>
          <td>{{ optional($t->end_date)->format('d M') }}</td>
        </tr>
      @empty
        <tr><td colspan="5"><div class="empty"><h4>Nothing assigned to you</h4>Tasks assigned to you will show up here.</div></td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
