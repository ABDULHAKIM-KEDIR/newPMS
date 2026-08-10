@extends('layouts.app')
@section('title', 'Search')
@section('crumb', '<b>Search</b>')

@section('content')
<div class="page-head">
  <div>
    <h1>Search</h1>
    <div class="page-sub">
      @if ($q !== '')
        Results for "{{ $q }}"
      @else
        Search projects, tasks, and people
      @endif
    </div>
  </div>
</div>

@if ($q === '')
  <div class="card"><div class="empty"><h4>Type something to search</h4>Try a project name, a task, or a person's name.</div></div>
@else
  <div style="display:flex; flex-direction:column; gap:18px;">
    <div class="card">
      <div class="card-title-row" style="padding:16px 20px 0;"><h3>Projects ({{ $projects->count() }})</h3></div>
      @if ($projects->isEmpty())
        <div class="empty">No matching projects.</div>
      @else
        <table>
          <tbody>
            @foreach ($projects as $p)
              <tr onclick="window.location='{{ route('projects.show', $p) }}'">
                <td class="cell-primary">{{ $p->project_name }}<div class="cell-sub">{{ optional($p->team)->team_name }}</div></td>
                <td style="text-align:right;" class="cell-sub">{{ $p->project_type }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>

    <div class="card">
      <div class="card-title-row" style="padding:16px 20px 0;"><h3>Tasks ({{ $tasks->count() }})</h3></div>
      @if ($tasks->isEmpty())
        <div class="empty">No matching tasks.</div>
      @else
        <table>
          <tbody>
            @foreach ($tasks as $t)
              <tr onclick="openTask({{ $t->task_id }})">
                <td class="cell-primary">{{ $t->task_name }}<div class="cell-sub">{{ optional(optional($t->phase)->project)->project_name }}</div></td>
                <td style="text-align:right;" class="cell-sub">{{ optional($t->assignee)->full_name }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>

    <div class="card">
      <div class="card-title-row" style="padding:16px 20px 0;"><h3>People ({{ $people->count() }})</h3></div>
      @if ($people->isEmpty())
        <div class="empty">No matching people.</div>
      @else
        <table>
          <tbody>
            @foreach ($people as $person)
              <tr>
                <td class="cell-primary">{{ $person->full_name }}<div class="cell-sub">{{ $person->email }}</div></td>
                <td style="text-align:right;" class="cell-sub">{{ optional($person->roles->first())->role_name }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>
@endif
@endsection
