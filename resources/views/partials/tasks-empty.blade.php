@php
    // A filtered view (status / project / assignee / search) that returns nothing
    // is a "no match" state; an unfiltered My Tasks with nothing assigned is the
    // "all caught up" state.
    $hasFilters = $status || $projectId || $search || ($filter === 'all' && $assigneeId);
@endphp
<div class="empty" style="padding:56px 24px; text-align:center;">
    @if ($hasFilters)
        <div style="font-size:38px; margin-bottom:12px;">🔍</div>
        <h4 style="font-size:16px; margin-bottom:6px;">No tasks match your filters</h4>
        <p style="font-size:13px; color:var(--ink-soft); max-width:420px; margin:0 auto 18px;">
            Try clearing the status, project, or assignee filter — or create a new task in a project.
        </p>
        <div style="display:flex; gap:8px; justify-content:center;">
            <a href="{{ route('tasks.index', ['filter' => $filter]) }}" class="btn btn-ghost">Clear All Filters</a>
            @can('create_tasks')
                <a href="{{ route('projects.index') }}" class="btn btn-accent">Go to Projects</a>
            @endcan
        </div>
    @else
        <div style="font-size:38px; margin-bottom:12px;">🎉</div>
        <h4 style="font-size:16px; margin-bottom:6px;">You're all caught up</h4>
        <p style="font-size:13px; color:var(--ink-soft); max-width:420px; margin:0 auto 18px;">
            Tasks assigned to you will appear here automatically. Right now nothing is assigned to you.
        </p>
        <div style="display:flex; gap:8px; justify-content:center;">
            @can('view_projects')
                <a href="{{ route('tasks.index', ['filter' => 'all']) }}" class="btn btn-ghost">Browse All Tasks</a>
            @endcan
            <a href="{{ route('projects.index') }}" class="btn btn-accent">View Projects</a>
        </div>
    @endif
</div>
