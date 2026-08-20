<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Models\PhaseBudget;
use App\Models\Project;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhaseController extends Controller
{
    private const STATUSES = ['Not started', 'In Progress', 'Done'];

    public function store(Request $request, Project $project)
    {
        $user = Auth::user();
        $canManage = $project->isManagedBy($user) || $user->can('edit_projects');
        abort_unless($canManage, 403);

        $data = $request->validate([
            'phase_name' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', self::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $maxOrder = $project->phases()->max('sequence_order') ?? -1;

        $phase = Phase::create([
            'project_id' => $project->project_id,
            'phase_name' => $data['phase_name'],
            'status' => $data['status'] ?? 'Not started',
            'sequence_order' => $maxOrder + 1,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        PhaseBudget::create([
            'phase_id' => $phase->phase_id,
            'allocated_amount' => 0,
            'spent_amount' => 0,
        ]);

        Activity::log('Added project phase', 'Phase', $phase->phase_id, "{$phase->phase_name} on {$project->project_name}");

        return back()->with('status', "Phase \"{$phase->phase_name}\" added successfully.");
    }

    public function update(Request $request, Phase $phase)
    {
        $phase->load('project');
        $user = Auth::user();
        $canManage = $phase->project->isManagedBy($user) || $user->can('edit_projects');
        abort_unless($canManage, 403);

        $data = $request->validate([
            'phase_name' => ['sometimes', 'required', 'string', 'max:100'],
            'status' => ['sometimes', 'required', 'in:'.implode(',', self::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $phase->update($data);

        Activity::log('Updated project phase', 'Phase', $phase->phase_id, "{$phase->phase_name} ({$phase->status})");

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Phase updated successfully.',
                'phase' => $phase,
            ]);
        }

        return back()->with('status', "Phase \"{$phase->phase_name}\" updated successfully.");
    }

    public function updateStatus(Request $request, Phase $phase)
    {
        $phase->load('project');
        $user = Auth::user();
        $canManage = $phase->project->isManagedBy($user) || $user->can('edit_projects');
        abort_unless($canManage, 403);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
        ]);

        $previous = $phase->status;
        $phase->update(['status' => $data['status']]);

        Activity::log('Updated phase status', 'Phase', $phase->phase_id, "{$phase->phase_name}: {$previous} → {$data['status']}");

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Phase status updated successfully.',
                'status' => $phase->status,
            ]);
        }

        return back()->with('status', "Phase \"{$phase->phase_name}\" is now {$phase->status}.");
    }

    public function destroy(Phase $phase)
    {
        $phase->load('project');
        $project = $phase->project;
        $user = Auth::user();
        $canManage = $project->isManagedBy($user) || $user->can('edit_projects');
        abort_unless($canManage, 403);

        if ($project->phases()->count() <= 1) {
            return back()->with('error', 'A project must have at least one phase.');
        }

        $otherPhase = $project->phases()->where('phase_id', '!=', $phase->phase_id)->first();
        if ($otherPhase) {
            $phase->tasks()->update(['phase_id' => $otherPhase->phase_id]);
        }

        $phaseName = $phase->phase_name;
        if ($phase->budget) {
            $phase->budget->delete();
        }
        $phase->delete();

        Activity::log('Deleted project phase', 'Phase', $phase->phase_id, "{$phaseName} from {$project->project_name}");

        return back()->with('status', "Phase \"{$phaseName}\" was deleted.");
    }
}
