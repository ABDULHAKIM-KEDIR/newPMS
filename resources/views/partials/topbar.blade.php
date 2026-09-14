<div class="topbar">

    @include('partials.context-header')

    <div class="crumb" style="display:flex; align-items:center; gap:8px;">
        @if (! request()->routeIs('dashboard') && ! request()->routeIs('landing'))
            <button type="button" onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ route('dashboard') }}'" class="btn btn-ghost" style="padding:2px 7px; font-size:11.5px; border-radius:4px; display:inline-flex; align-items:center; gap:4px;" title="Go back to previous page">
                <span>←</span> Back
            </button>
        @endif


        @php
            $crumb = trim(html_entity_decode(strip_tags($__env->yieldContent('crumb', 'Dashboard')), ENT_QUOTES, 'UTF-8'));
            $routeProject = request()->route('project');
            $routeTeam = request()->route('team');
            $routeTask = request()->route('task');
            $breadcrumbProject = $routeProject instanceof \App\Models\Project ? $routeProject : null;
            $breadcrumbTeam = $routeTeam instanceof \App\Models\Team ? $routeTeam : null;

            if ($routeTask instanceof \App\Models\Task) {
                $routeTask->loadMissing(['project', 'team']);
                $breadcrumbProject = $routeTask->project;
                $breadcrumbTeam = $routeTask->team;
            } elseif ($breadcrumbTeam && ! $breadcrumbProject) {
                $requestedProject = request('project')
                    ? \App\Models\Project::find(request('project'))
                    : null;
                $breadcrumbProject = $requestedProject
                    && $breadcrumbTeam->allProjects()->contains('project_id', $requestedProject->project_id)
                    && auth()->user()->can('view', $requestedProject)
                    ? $requestedProject
                    : $breadcrumbTeam->allProjects()->first();
            }

            $breadcrumbOffice = $breadcrumbProject?->primaryOffice
                ?? $breadcrumbTeam?->office;
        @endphp

        @if ($breadcrumbProject || $breadcrumbTeam)
            @if ($breadcrumbOffice)
                <span>{{ $breadcrumbOffice->office_name }}</span>
                <span aria-hidden="true">/</span>
            @endif
            @if ($breadcrumbProject)
                <a class="link-small" href="{{ route('projects.show', $breadcrumbProject) }}">{{ $breadcrumbProject->project_name }}</a>
                <span aria-hidden="true">/</span>
            @endif
            @if ($breadcrumbTeam)
                <a class="link-small" href="{{ route('teams.show', $breadcrumbTeam) }}">{{ $breadcrumbTeam->team_name }}</a>
                @if ($routeTask instanceof \App\Models\Task)
                    <span aria-hidden="true">/</span>
                    <strong class="font-bold">{{ $routeTask->task_name }}</strong>
                @endif
            @elseif (request()->routeIs('projects.edit'))
                <strong class="font-bold">Edit</strong>
            @endif
        @else
            <strong class="font-bold">{{ $crumb ?: 'Dashboard' }}</strong>
        @endif
    </div>

    <form
        class="search"
        method="GET"
        action="{{ route('search') }}"
        style="cursor:text;"
    >

        <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <circle cx="11" cy="11" r="7" />
            <path d="M21 21l-4.3-4.3" />
        </svg>

        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search projects, tasks, people…"
            style="
                border:none;
                background:none;
                outline:none;
                font:inherit;
                color:inherit;
                width:100%;
            "
        >

    </form>

    <a
        href="{{ route('notifications.index') }}"
        class="icon-btn"
        title="Notifications"
    >

        <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
        >
            <path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z" />
            <path d="M10 21a2 2 0 0 0 4 0" />
        </svg>

        @if (
            ($unreadCount ??
            \App\Models\Notification::where('user_id', auth()->id())
                ->where('is_read', false)
                ->count()) > 0
        )
            <span class="dot"></span>
        @endif

    </a>

</div>