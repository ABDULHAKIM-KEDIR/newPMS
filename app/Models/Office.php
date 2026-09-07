<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    protected $primaryKey = 'office_id';

    protected $fillable = [
        'office_name', 'office_code', 'description', 'head_user_id', 'status',
    ];

    public function head()
    {
        return $this->belongsTo(User::class, 'head_user_id', 'user_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'office_id', 'office_id');
    }

    public function teams()
    {
        return $this->hasMany(Team::class, 'office_id', 'office_id');
    }

    /** Projects whose primary office is this office. */
    public function primaryProjects()
    {
        return $this->hasMany(Project::class, 'primary_office_id', 'office_id');
    }

    /** Projects this office participates in (cross-office). */
    public function participatingProjects()
    {
        return $this->belongsToMany(Project::class, 'project_office', 'office_id', 'project_id')
            ->withPivot('participation_type')
            ->withTimestamps();
    }

    /** All projects touched by this office (primary + participating). */
    public function allProjects()
    {
        $primary = $this->primaryProjects()->get();
        $participating = $this->participatingProjects()->get();

        return $primary->merge($participating)->unique('project_id')->values();
    }

    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    /**
     * Budget roll-up for the office: allocated/spent/remaining across
     * projects whose primary office is this office (financial ownership
     * follows the primary office, matching the project's structure).
     *
     * @return array{allocated: float, spent: float, remaining: float}
     */
    public function budgetSummary(): array
    {
        $rows = ProjectBudget::query()
            ->whereIn('project_id', $this->primaryProjects()->select('projects.project_id'))
            ->get(['allocated_amount', 'spent_amount']);

        $allocated = (float) $rows->sum('allocated_amount');
        $spent = (float) $rows->sum('spent_amount');

        return [
            'allocated' => $allocated,
            'spent' => $spent,
            'remaining' => $allocated - $spent,
        ];
    }
}
