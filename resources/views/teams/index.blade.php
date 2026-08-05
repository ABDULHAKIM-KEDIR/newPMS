@extends('layouts.app')
@section('title', 'Teams')
@section('crumb', '<b>Teams</b>')

@section('content')
<div class="page-head">
  <div><h1>Teams</h1><div class="page-sub">Directorate teams and their current staffing</div></div>
  <button class="btn btn-accent">+ New Team</button>
</div>

<div class="grid" style="grid-template-columns:repeat(3,1fr);">
  @foreach ($teams as $t)
    <div class="card card-pad">
      <div class="card-title-row"><h3>{{ $t->team_name }}</h3><span class="link-small">Manage →</span></div>
      <div style="font-size:12.6px; color:var(--ink-soft); margin-bottom:14px; line-height:1.5;">{{ $t->description }}</div>
      <div class="list-row"><span class="k" style="color:var(--ink-soft)">Team leader</span><span style="font-weight:600;">{{ optional($t->leader)->full_name }}</span></div>
      <div class="list-row"><span class="k" style="color:var(--ink-soft)">Members</span><span style="font-weight:600;">{{ $t->members->count() }}</span></div>
      <div class="list-row"><span class="k" style="color:var(--ink-soft)">Active projects</span><span style="font-weight:600;">{{ $t->projects->count() }}</span></div>
    </div>
  @endforeach
</div>
@endsection
