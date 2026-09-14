<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Office;
use App\Models\User;
use App\Services\OrgHierarchyService;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfficeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Office::class);

        $offices = Office::with(['head'])
            ->withCount(['users', 'teams', 'primaryProjects as projects_count'])
            ->orderBy('office_name')
            ->get();

        return view('admin.offices.index', compact('offices'));
    }

    public function create()
    {
        $this->authorize('create', Office::class);

        $users = User::where('status', 'Active')->orderBy('full_name')->get();
        $departments = Department::where('status', 'Active')->orderBy('department_name')->get();
        $parentOffices = Office::active()->orderBy('office_name')->get();

        return view('admin.offices.create', compact('users', 'departments', 'parentOffices'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Office::class);

        $data = $this->validated($request);

        $office = Office::create($data);

        // Grant the scoped Head of Office role so the RBAC engine honours
        // the head's permissions (head_user_id alone is not a grant).
        if ($office->head_user_id) {
            app(OrgHierarchyService::class)->assignHead(
                User::findOrFail($office->head_user_id),
                $office
            );
        }

        Activity::log('Created office', 'Office', $office->office_id, "{$office->office_name} ({$office->office_code})");

        return redirect()->route('admin.offices.index')->with('status', "Office \"{$office->office_name}\" created.");
    }

    public function show(Office $office)
    {
        $this->authorize('view', $office);

        $office->load(['head', 'department', 'parentOffice', 'childOffices', 'users.roles', 'teams.leader', 'primaryProjects', 'participatingProjects']);

        return view('admin.offices.show', [
            'office' => $office,
            'budget' => $office->budgetSummary(),
        ]);
    }

    public function edit(Office $office)
    {
        $this->authorize('update', $office);

        $users = User::where('status', 'Active')->orderBy('full_name')->get();
        $departments = Department::where('status', 'Active')->orderBy('department_name')->get();
        $parentOffices = Office::where('office_id', '!=', $office->office_id)
            ->whereNotIn('office_id', $office->allDescendantIds())
            ->orderBy('office_name')
            ->get();

        return view('admin.offices.edit', compact('office', 'users', 'departments', 'parentOffices'));
    }

    public function update(Request $request, Office $office)
    {
        $this->authorize('update', $office);

        if ($request->filled('parent_office_id') && $office->wouldCauseCycle((int) $request->parent_office_id)) {
            return back()->withErrors(['parent_office_id' => 'Cannot set a descendant office as parent (circular reference).'])->withInput();
        }

        $data = $this->validated($request, $office);

        $previousHeadId = (int) ($office->head_user_id ?? 0);
        $office->update($data);

        // Keep the scoped Head of Office role in sync when the head
        // changes: revoke it from the previous head, grant it to the new
        // one. head_user_id alone is not an RBAC grant.
        $hierarchy = app(OrgHierarchyService::class);

        $previousHead = $previousHeadId && $previousHeadId !== (int) ($office->head_user_id ?? 0)
            ? User::find($previousHeadId)
            : null;

        if ($previousHead) {
            $hierarchy->revokeHead($previousHead, $office);
        }

        if ($office->head_user_id && (int) $office->head_user_id !== $previousHeadId) {
            $hierarchy->assignHead(User::findOrFail($office->head_user_id), $office);
        }

        if ((int) ($office->head_user_id ?? 0) !== $previousHeadId && $office->head_user_id) {
            Activity::notify(
                (int) $office->head_user_id,
                "You are now the head of the {$office->office_name} office",
                'general',
                route('admin.offices.show', $office)
            );
        }

        Activity::log('Updated office', 'Office', $office->office_id, $office->office_name);

        return redirect()->route('admin.offices.show', $office)->with('status', 'Office updated.');
    }

    public function toggleStatus(Office $office)
    {
        $this->authorize('update', $office);

        $office->status = $office->isActive() ? 'Inactive' : 'Active';
        $office->save();

        Activity::log(
            $office->isActive() ? 'Activated office' : 'Deactivated office',
            'Office',
            $office->office_id,
            $office->office_name
        );

        return back()->with('status', "Office \"{$office->office_name}\" is now {$office->status}.");
    }

    /**
     * Shared validation. Unique rules ignore the current record on update;
     * head_user_id must exist if provided (never trusted blindly).
     */
    private function validated(Request $request, ?Office $office = null): array
    {
        return $request->validate([
            'office_name' => [
                'required', 'string', 'max:150',
                Rule::unique('offices', 'office_name')->ignore($office?->office_id, 'office_id'),
            ],
            'office_code' => [
                'required', 'string', 'max:20',
                Rule::unique('offices', 'office_code')->ignore($office?->office_id, 'office_id'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit_type' => ['nullable', 'string', 'max:50'],
            'parent_office_id' => ['nullable', 'exists:offices,office_id'],
            'head_user_id' => ['nullable', 'exists:users,user_id'],
            'department_id' => ['nullable', 'exists:departments,department_id'],
            'parent_office_id' => [
                'nullable',
                'exists:offices,office_id',
                Rule::notIn($office ? [$office->office_id] : []),
            ],
        ]);
    }
}
