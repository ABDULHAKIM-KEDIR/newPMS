<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PhaseBudget;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->can('view_budgets') || $user->can('manage_budgets') || $user->isDirectorOrAdmin(), 403);

        $scoped = ! $user->isDirectorOrAdmin();

        $query = Payment::with(['project', 'phase', 'task', 'creator'])->orderByDesc('payment_date');

        if ($scoped) {
            $query->whereHas('project', fn ($q) => $q->visibleTo($user));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }
        if ($request->filled('status')) {
            $query->where('payment_status', $request->input('status'));
        }
        if ($request->filled('task_id')) {
            $query->where('task_id', $request->input('task_id'));
        }

        $payments = $query->paginate(20)->withQueryString();

        $projectsQuery = Project::orderBy('project_name');
        if ($scoped) {
            $projectsQuery->visibleTo($user);
        }
        $projects = $projectsQuery->get();

        $allPayments = (clone $query)->get();
        $totalPayments = (float) $allPayments->where('payment_status', 'Completed')->sum('amount');
        $pendingPayments = (float) $allPayments->where('payment_status', 'Pending')->sum('amount');
        $completedCount = $allPayments->where('payment_status', 'Completed')->count();
        $pendingCount = $allPayments->where('payment_status', 'Pending')->count();

        return view('payments.index', compact(
            'payments',
            'projects',
            'totalPayments',
            'pendingPayments',
            'completedCount',
            'pendingCount'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->can('manage_budgets') || $user->isDirectorOrAdmin(), 403);

        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,project_id'],
            'phase_id' => ['nullable', 'exists:phases,phase_id'],
            'task_id' => ['nullable', 'exists:tasks,task_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'recipient' => ['required', 'string', 'max:255'],
            'payment_status' => ['required', 'in:Completed,Pending,Approved,Cancelled'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sop_process' => ['nullable', 'string', 'max:150'],
        ]);

        $data['created_by'] = $user->user_id;

        $payment = Payment::create($data);

        $this->syncBudgetRollups($payment->project_id, $payment->phase_id);

        Activity::log(
            'Recorded payment',
            'Payment',
            $payment->payment_id,
            'ETB '.number_format($payment->amount)." to {$payment->recipient} for project #{$payment->project_id}"
        );

        return back()->with('status', 'Payment of ETB '.number_format($payment->amount).' recorded successfully.');
    }

    public function update(Request $request, Payment $payment)
    {
        $user = Auth::user();
        abort_unless($user->can('manage_budgets') || $user->isDirectorOrAdmin(), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'recipient' => ['required', 'string', 'max:255'],
            'payment_status' => ['required', 'in:Completed,Pending,Approved,Cancelled'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sop_process' => ['nullable', 'string', 'max:150'],
        ]);

        $payment->update($data);

        $this->syncBudgetRollups($payment->project_id, $payment->phase_id);

        Activity::log(
            'Updated payment',
            'Payment',
            $payment->payment_id,
            "Updated payment #{$payment->payment_id} (ETB ".number_format($payment->amount)." to {$payment->recipient})"
        );

        return back()->with('status', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $user = Auth::user();
        abort_unless($user->can('manage_budgets') || $user->isDirectorOrAdmin(), 403);

        $projectId = $payment->project_id;
        $phaseId = $payment->phase_id;
        $details = "Payment #{$payment->payment_id} of ETB ".number_format($payment->amount)." to {$payment->recipient}";

        $payment->delete();

        $this->syncBudgetRollups($projectId, $phaseId);

        Activity::log('Deleted payment', 'Payment', $payment->payment_id, $details);

        return back()->with('status', 'Payment deleted successfully.');
    }

    private function syncBudgetRollups(int $projectId, ?int $phaseId = null): void
    {
        $completedSum = (float) Payment::where('project_id', $projectId)
            ->where('payment_status', 'Completed')
            ->sum('amount');

        if ($completedSum > 0) {
            ProjectBudget::updateOrCreate(
                ['project_id' => $projectId],
                ['spent_amount' => $completedSum]
            );
        }

        if ($phaseId) {
            $phaseCompletedSum = (float) Payment::where('phase_id', $phaseId)
                ->where('payment_status', 'Completed')
                ->sum('amount');

            if ($phaseCompletedSum > 0) {
                PhaseBudget::updateOrCreate(
                    ['phase_id' => $phaseId],
                    ['spent_amount' => $phaseCompletedSum]
                );
            }
        }
    }
}
