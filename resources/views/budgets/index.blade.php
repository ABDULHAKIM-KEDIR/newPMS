@extends('layouts.app')
@section('title', 'Budgets')
@section('crumb', '<b>Budgets</b>')

@section('content')
<div class="page-head">
  <div><h1>Budgets</h1><div class="page-sub">Allocated vs. spent across all active projects</div></div>
</div>

<div class="card">
  <table>
    <thead><tr><th style="width:28%">Project</th><th>Allocated</th><th>Spent</th><th>Utilisation</th><th>Currency</th></tr></thead>
    <tbody>
      @foreach ($projects as $p)
        @php $b = $p->budget; $util = $b->utilisationPercent(); @endphp
        <tr onclick="window.location='{{ route('projects.show', $p) }}'">
          <td class="cell-primary">{{ $p->project_name }}</td>
          <td>ETB {{ number_format($b->allocated_amount) }}</td>
          <td>ETB {{ number_format($b->spent_amount) }}</td>
          <td style="width:180px;">
            <div class="cell-sub" style="margin-bottom:4px;">{{ $util }}%</div>
            <div class="progressbar {{ $util>85?'danger':($util>65?'warn':'') }}"><div style="width:{{ $util }}%"></div></div>
          </td>
          <td>{{ $b->currency }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
