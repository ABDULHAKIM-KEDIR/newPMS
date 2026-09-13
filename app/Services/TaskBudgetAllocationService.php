<?php

namespace App\Services;

use App\Models\Phase;
use Illuminate\Validation\ValidationException;

class TaskBudgetAllocationService
{
    public function allocatedAmount(Phase $phase, ?int $ignoreTaskId = null): float
    {
        return (float) $phase->tasks()
            ->when($ignoreTaskId, fn ($query) => $query->where('task_id', '!=', $ignoreTaskId))
            ->sum('budget');
    }

    public function remainingAmount(Phase $phase, ?int $ignoreTaskId = null): float
    {
        $phase->loadMissing('budget');

        $budgetRemaining = (float) ($phase->budget?->allocated_amount ?? 0)
            - (float) ($phase->budget?->spent_amount ?? 0);

        return max(0, $budgetRemaining - $this->allocatedAmount($phase, $ignoreTaskId));
    }

    public function assertAllocationAllowed(Phase $phase, float|int|string|null $amount, ?int $ignoreTaskId = null): void
    {
        $amount = (float) ($amount ?? 0);

        if ($amount < 0) {
            throw ValidationException::withMessages([
                'budget' => 'Task allocation cannot be negative.',
            ]);
        }

        $phase->loadMissing('budget');
        $phaseBudget = (float) ($phase->budget?->allocated_amount ?? 0);
        $phaseSpent = (float) ($phase->budget?->spent_amount ?? 0);
        $existingAllocations = $this->allocatedAmount($phase, $ignoreTaskId);
        $remaining = max(0, $phaseBudget - $phaseSpent - $existingAllocations);

        if ($amount > $remaining) {
            throw ValidationException::withMessages([
                'budget' => sprintf(
                    'This task allocation exceeds the remaining phase budget of ETB %s.',
                    number_format($remaining, 2)
                ),
            ]);
        }
    }

    public function assertPhaseBudgetAllowed(Phase $phase, float|int|string $allocated, float|int|string $spent): void
    {
        $allocated = (float) $allocated;
        $spent = (float) $spent;
        $taskAllocations = $this->allocatedAmount($phase);

        if ($allocated < 0 || $spent < 0) {
            throw ValidationException::withMessages([
                'allocated_amount' => 'Phase budget values cannot be negative.',
            ]);
        }

        if ($spent > $allocated) {
            throw ValidationException::withMessages([
                'spent_amount' => 'Spent amount cannot exceed the phase allocation.',
            ]);
        }

        if ($taskAllocations > ($allocated - $spent)) {
            throw ValidationException::withMessages([
                'allocated_amount' => sprintf(
                    'The phase budget cannot be reduced below its existing task allocations of ETB %s.',
                    number_format($taskAllocations, 2)
                ),
            ]);
        }
    }

    public function assertBatchAllocationAllowed(Phase $phase, float|int|string|null $amount): void
    {
        $amount = (float) ($amount ?? 0);
        $phase->loadMissing('budget');

        $remaining = (float) ($phase->budget?->allocated_amount ?? 0)
            - (float) ($phase->budget?->spent_amount ?? 0);

        if ($amount < 0 || $amount > max(0, $remaining)) {
            throw ValidationException::withMessages([
                'budget' => sprintf(
                    'Task allocations exceed the remaining phase budget of ETB %s.',
                    number_format(max(0, $remaining), 2)
                ),
            ]);
        }
    }
}
