<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\User;
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

        return view('admin.offices.create', compact('users'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Office::class);

        $data = $this->validated($request);

        $office = Office::create($data);

        Activity::log('Created office', 'Office', $office->office_id, "{$office->office_name} ({$office->office_code})");

        return redirect()->route('admin.offices.index')->with('status', "Office \"{$office->office_name}\" created.");
    }

    public function show(Office $office)
    {
        $this->authorize('view', $office);

        $office->load(['head', 'users.roles', 'teams.leader', 'primaryProjects', 'participatingProjects']);

        return view('admin.offices.show', [
            'office' => $office,
            'budget' => $office->budgetSummary(),
        ]);
    }

    public function edit(Office $office)
    {
        $this->authorize('update', $office);

        $users = User::where('status', 'Active')->orderBy('full_name')->get();

        return view('admin.offices.edit', compact('office', 'users'));
    }

    public function update(Request $request, Office $office)
    {
        $this->authorize('update', $office);

        $data = $this->validated($request, $office);

        $previousHeadId = (int) ($office->head_user_id ?? 0);
        $office->update($data);

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
            'head_user_id' => ['nullable', 'exists:users,user_id'],
        ]);
    }
}
