@php
    $contextOffice = null;
    $contextProject = null;
    $contextTeam = null;
    $route = request()->route();

    if ($route && ! request()->routeIs('admin.*')) {
        $resourceProject = $route->parameter('project');
        $resourceTeam = $route->parameter('team');
        $resourceTask = $route->parameter('task');
        $resourcePhase = $route->parameter('phase');

        if ($resourceProject instanceof \App\Models\Project) {
            $contextProject = $resourceProject;
            $contextOffice = $resourceProject->office;
        } elseif ($resourceTeam instanceof \App\Models\Team) {
            $contextTeam = $resourceTeam;
            $contextProject = $resourceTeam->allProjects()
                ->first(fn ($project) => auth()->user()?->can('view', $project));
            $contextOffice = $contextProject?->office ?? $resourceTeam->office;
        } elseif ($resourceTask instanceof \App\Models\Task) {
            $contextTaskProject = $resourceTask->project ?? optional($resourceTask->phase)->project;
            $contextProject = $contextTaskProject;
            $contextTeam = $resourceTask->team;
            $contextOffice = $contextTaskProject?->office ?? $contextTeam?->office;
        } elseif ($resourcePhase instanceof \App\Models\Phase) {
            $contextProject = $resourcePhase->project;
            $contextOffice = $contextProject?->office;
        }

        if (! $contextProject && ! $contextTeam) {
            $contextOffice ??= auth()->user()?->office;
        }
    }
@endphp

@if ($contextOffice || $contextProject || $contextTeam)
    <div class="context-header" aria-label="Current context">
        @if ($contextOffice)
            <span>{{ $contextOffice->office_name }}</span>
        @endif
        @if ($contextProject)
            <span aria-hidden="true">→</span>
            <span>{{ $contextProject->project_name }}</span>
        @endif
        @if ($contextTeam)
            <span aria-hidden="true">→</span>
            <span>{{ $contextTeam->team_name }}</span>
        @endif
    </div>
@endif