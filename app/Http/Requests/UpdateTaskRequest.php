<?php

namespace App\Http\Requests;

use App\Http\Controllers\TaskController;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization (management / assignee checks) is enforced in the controller.
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Legacy behaviour from the controller: empty strings mean "clear the field".
        foreach (['start_date', 'end_date', 'description'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'task_name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'phase_id' => ['nullable', 'exists:phases,phase_id'],
            'team_id' => ['nullable', 'exists:teams,team_id'],
            'priority' => ['sometimes', 'required', 'in:High,Medium,Low,Urgent'],
            'status' => ['sometimes', 'required', 'in:'.implode(',', TaskController::STATUSES)],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ];
    }
}
