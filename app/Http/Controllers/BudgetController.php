<?php

namespace App\Http\Controllers;

use App\Models\Phase;
use App\Models\PhaseBudget;
use App\Models\Project;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BudgetController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        abort_unless($user->can('view_projects') || $user->can('manage_budgets'), 403);

        $projects = Project::with(['team', 'budget', 'phases.budget', 'phases.tasks'])->whereHas('budget')->get();

        $totalAllocated = $projects->sum(fn ($p) => (float) (optional($p->budget)->allocated_amount ?? 0));
        $totalSpent = $projects->sum(fn ($p) => (float) (optional($p->budget)->spent_amount ?? 0));
        $totalRemaining = max(0, $totalAllocated - $totalSpent);
        $overallUtilization = $totalAllocated > 0 ? round(($totalSpent / $totalAllocated) * 100) : 0;

        return view('budgets.index', compact('projects', 'totalAllocated', 'totalSpent', 'totalRemaining', 'overallUtilization'));
    }

    public function updateProjectBudget(Request $request, Project $project)
    {
        $user = Auth::user();
        abort_unless($user->can('manage_budgets') || $user->isDirectorOrAdmin(), 403);

        $data = $request->validate([
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'spent_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $budget = $project->budget()->firstOrCreate(
            ['project_id' => $project->project_id],
            ['allocated_amount' => 0, 'spent_amount' => 0, 'currency' => 'ETB']
        );

        $budget->update([
            'allocated_amount' => $data['allocated_amount'],
            'spent_amount' => $data['spent_amount'],
        ]);

        Activity::log('Updated project budget', 'Project', $project->project_id, "{$project->project_name}: Allocated ETB ".number_format($data['allocated_amount']).', Spent ETB '.number_format($data['spent_amount']));

        return back()->with('status', "Budget for \"{$project->project_name}\" updated successfully.");
    }

    public function updatePhaseBudget(Request $request, Phase $phase)
    {
        $user = Auth::user();
        abort_unless($user->can('manage_budgets') || $user->isDirectorOrAdmin(), 403);

        $data = $request->validate([
            'allocated_amount' => ['required', 'numeric', 'min:0'],
            'spent_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $budget = $phase->budget()->firstOrCreate(
            ['phase_id' => $phase->phase_id],
            ['allocated_amount' => 0, 'spent_amount' => 0]
        );

        $budget->update([
            'allocated_amount' => $data['allocated_amount'],
            'spent_amount' => $data['spent_amount'],
        ]);

        // Auto-recalculate project spent amount from sum of phase spent amounts if applicable
        $project = $phase->project;
        if ($project && $project->budget) {
            $totalPhaseSpent = PhaseBudget::whereIn('phase_id', $project->phases->pluck('phase_id'))->sum('spent_amount');
            if ($totalPhaseSpent > $project->budget->spent_amount) {
                $project->budget->update(['spent_amount' => $totalPhaseSpent]);
            }
        }

        Activity::log('Updated phase budget', 'Phase', $phase->phase_id, "{$phase->phase_name}: Allocated ETB ".number_format($data['allocated_amount']).', Spent ETB '.number_format($data['spent_amount']));

        return back()->with('status', "Phase \"{$phase->phase_name}\" budget updated successfully.");
    }
}
