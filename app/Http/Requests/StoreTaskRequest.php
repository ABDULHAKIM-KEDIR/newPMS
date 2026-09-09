<?php

namespace App\Http\Requests;

use App\Http\Controllers\TaskController;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization (create_tasks + project management) is enforced in the controller
        // after the project/phase context is resolved.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['nullable', 'exists:projects,project_id'],
            'phase_id' => ['nullable', 'exists:phases,phase_id'],
            'team_id' => ['nullable', 'exists:teams,team_id'],
            'task_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', 'in:High,Medium,Low,Urgent'],
            'status' => ['nullable', 'in:'.implode(',', TaskController::STATUSES)],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && $this->filled('start_date') && $value < $this->input('start_date')) {
                        $fail('The end date must be a date after or equal to start date.');
                    }
                },
            ],
        ];
    }
}
