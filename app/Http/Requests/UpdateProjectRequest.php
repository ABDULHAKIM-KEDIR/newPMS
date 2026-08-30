<?php

namespace App\Http\Requests;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by $this->authorize('update', $project) in the controller.
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Preserve legacy behaviour: an empty date input means "clear the field".
        foreach (['start_date', 'end_date'] as $field) {
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
        /** @var User $user */
        $user = Auth::user();

        $eligibleTeamIds = Team::query()
            ->when(! $user->isDirectorOrAdmin(), fn ($q) => $q->where('team_leader_id', $user->user_id))
            ->pluck('team_id');

        if (! $user->isDirectorOrAdmin() && $this->route('project')?->isManagedBy($user)) {
            $eligibleTeamIds->push($this->route('project')->team_id);
        }

        return [
            'project_name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'project_type' => ['required', 'in:Software,Network & Infrastructure,Training & Consultancy,Enterprise Systems,Research & Development'],
            'team_id' => ['required', Rule::in($eligibleTeamIds)],
            'status' => ['required', 'in:planning,active,risk,closed'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'allocated_amount' => ['nullable', 'numeric', 'min:0'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['nullable', 'exists:users,user_id'],
            'members.*.role_id' => ['nullable', 'exists:roles,role_id'],
            'members.*.specialty' => ['nullable', 'string', 'max:100'],
        ];
    }
}
