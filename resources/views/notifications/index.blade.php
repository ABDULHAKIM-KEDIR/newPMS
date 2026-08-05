@extends('layouts.app')
@section('title', 'Notifications')
@section('crumb', '<b>Notifications</b>')

@section('content')
<div class="page-head">
  <div><h1>Notifications</h1><div class="page-sub">Task updates, approvals and mentions</div></div>
  <form method="POST" action="{{ route('notifications.markAllRead') }}">
    @csrf
    <button class="btn btn-ghost" type="submit">Mark all as read</button>
  </form>
</div>

<div class="card card-pad">
  @forelse ($notifications as $n)
    <div class="activity-row">
      <div class="activity-icon">🔔</div>
      <div style="flex:1;">
        <div class="activity-txt" style="{{ !$n->is_read ? 'font-weight:600;' : '' }}">{{ $n->message }}</div>
        <div class="activity-time">{{ $n->created_at->diffForHumans() }}</div>
      </div>
      @if (!$n->is_read)<span class="dot" style="position:static; background:var(--accent);"></span>@endif
    </div>
  @empty
    <div class="empty"><h4>You're all caught up</h4>New updates will show up here.</div>
  @endforelse
</div>
@endsection
