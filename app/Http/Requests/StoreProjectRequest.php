<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by Gate::authorize('create_projects') in the controller.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'client' => ['nullable', 'string', 'max:150'],
            'project_type' => ['nullable', 'string', 'max:100'],
            'project_type_id' => ['nullable', 'exists:project_types,project_type_id'],
            'team_id' => ['nullable', 'exists:teams,team_id'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['exists:teams,team_id'],
            'teams' => ['nullable', 'array'],
            'teams.*' => ['exists:teams,team_id'],
            'priority' => ['nullable', 'string', 'in:Low,Medium,High,Urgent'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'allocated_amount' => ['nullable', 'numeric', 'min:0'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['nullable', 'exists:users,user_id'],
            'members.*.role_id' => ['nullable', 'exists:roles,role_id'],
            'members.*.specialty' => ['nullable', 'string', 'max:100'],
            'tasks' => ['nullable', 'array'],
            'tasks.*.task_name' => ['nullable', 'string', 'max:150'],
            'tasks.*.team_id' => ['nullable', 'exists:teams,team_id'],
            'tasks.*.assigned_to' => ['nullable'],
            'tasks.*.priority' => ['nullable', 'in:Low,Medium,High,Urgent'],
            'tasks.*.status' => ['nullable', 'string'],
            'tasks.*.budget' => ['nullable', 'numeric', 'min:0'],
            'tasks.*.end_date' => ['nullable', 'date'],
            'tasks.*.description' => ['nullable', 'string'],
        ];
    }
}
