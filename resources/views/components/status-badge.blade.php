@props(['status', 'label' => null, 'dot' => true])

@php
$map = [
    // Projects / generic lifecycle
    'active'      => ['b-active', 'Active'],
    'planning'    => ['b-planning', 'Planning'],
    'risk'        => ['b-risk', 'At Risk'],
    'closed'      => ['b-closed', 'Closed'],
    'on_hold'     => ['b-risk', 'On Hold'],
    'on hold'     => ['b-risk', 'On Hold'],
    'pending'     => ['b-planning', 'Pending'],
    'in_progress' => ['b-active', 'In Progress'],
    'in progress' => ['b-planning', 'In Progress'],
    'completed'   => ['b-active', 'Completed'],
    'done'        => ['b-active', 'Done'],
    'to do'       => ['b-planning', 'To Do'],
    'todo'        => ['b-planning', 'To Do'],
    'in review'   => ['b-review', 'In Review'],
    'in_review'   => ['b-review', 'In Review'],
    'blocked'     => ['b-blocked', 'Blocked'],
    'not started' => ['b-planning', 'Not started'],
    'delivered'   => ['b-active', 'Delivered'],
    'approved'    => ['b-active', 'Approved'],
    'rejected'    => ['b-blocked', 'Rejected'],
];
$key = strtolower(trim((string) $status));
[$class, $defaultLabel] = $map[$key] ?? ['b-planning', $status];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$class]) }}>
    @if ($dot)<span class="badge-dot"></span>@endif
    {{ $label ?? $defaultLabel }}
</span>
