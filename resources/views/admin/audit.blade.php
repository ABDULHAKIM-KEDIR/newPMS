@extends('layouts.app')
@section('title', 'Audit Log')
@section('crumb', '<b>Audit Log</b>')

@section('content')
<div class="page-head">
  <div><h1>Audit Log</h1><div class="page-sub">System-wide record of who did what, and when</div></div>
  <a href="{{ route('admin.audit.export') }}" class="btn btn-ghost">Export CSV</a>
</div>

<div class="card">
  <table>
    <thead><tr><th style="width:15%">Time</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th style="width:11%">IP address</th></tr></thead>
    <tbody>
      @foreach ($logs as $log)
        <tr>
          <td class="mono cell-sub">{{ $log->timestamp->diffForHumans() }}</td>
          <td class="cell-primary">{{ optional($log->user)->full_name ?? 'System' }}</td>
          <td>{{ $log->action }}</td>
          <td class="mono">{{ $log->entity_type }}{{ $log->entity_id ? '-'.$log->entity_id : '' }}</td>
          <td class="cell-sub">{{ $log->details }}</td>
          <td class="mono cell-sub">{{ $log->ip_address ?? '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

<div style="margin-top:16px;">{{ $logs->links() }}</div>
@endsection
