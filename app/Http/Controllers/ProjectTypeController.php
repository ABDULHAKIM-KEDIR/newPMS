<?php

namespace App\Http\Controllers;

use App\Models\ProjectType;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProjectTypeController extends Controller
{
    public function index()
    {
        Gate::authorize('manage_system_settings');

        $projectTypes = ProjectType::withCount('projects')
            ->orderBy('name')
            ->get();

        return view('admin.project-types.index', [
            'projectTypes' => $projectTypes,
            'editing' => null,
        ]);
    }

    /** Loads a record into the inline editor on the index page. */
    public function edit(ProjectType $projectType)
    {
        Gate::authorize('manage_system_settings');

        $projectTypes = ProjectType::withCount('projects')
            ->orderBy('name')
            ->get();

        return view('admin.project-types.index', [
            'projectTypes' => $projectTypes,
            'editing' => $projectType,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('manage_system_settings');

        $data = $this->validated($request);

        ProjectType::create($data);

        Activity::log(
            'Created project type',
            'ProjectType',
            null,
            $data['name']
        );

        return back()->with('status', "Project type \"{$data['name']}\" created.");
    }

    public function update(Request $request, ProjectType $projectType)
    {
        Gate::authorize('manage_system_settings');

        $data = $this->validated($request, $projectType->project_type_id);

        $projectType->update($data);

        Activity::log(
            'Updated project type',
            'ProjectType',
            $projectType->project_type_id,
            $data['name']
        );

        return redirect()
            ->route('admin.project-types.index')
            ->with('status', "Project type \"{$data['name']}\" updated.");
    }

    /** Activate / deactivate without destroying history. */
    public function toggleActive(ProjectType $projectType)
    {
        Gate::authorize('manage_system_settings');

        $projectType->update([
            'is_active' => ! $projectType->is_active,
        ]);

        Activity::log(
            $projectType->is_active ? 'Activated project type' : 'Deactivated project type',
            'ProjectType',
            $projectType->project_type_id,
            $projectType->name
        );

        return back()->with(
            'status',
            $projectType->is_active
                ? "Project type \"{$projectType->name}\" activated."
                : "Project type \"{$projectType->name}\" deactivated."
        );
    }

    public function destroy(ProjectType $projectType)
    {
        Gate::authorize('manage_system_settings');

        abort_if(
            $projectType->projects()->exists(),
            422,
            'This project type is used by existing projects and cannot be deleted. Deactivate it instead.'
        );

        $name = $projectType->name;

        $projectType->delete();

        Activity::log('Deleted project type', 'ProjectType', null, $name);

        return back()->with('status', "Project type \"{$name}\" deleted.");
    }

    /**
     * @return array{name: string, description: ?string}
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('project_types', 'name')->ignore($ignoreId, 'project_type_id'),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ];
    }
}
