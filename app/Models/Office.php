<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Office extends Model
{
    protected $primaryKey = 'office_id';

    protected $fillable = [
        'office_name', 'office_code', 'unit_type', 'department_id', 'parent_office_id', 'description', 'head_user_id', 'status',
    ];

    public function parent()
    {
        return $this->belongsTo(Office::class, 'parent_office_id', 'office_id');
    }

    public function children()
    {
        return $this->hasMany(Office::class, 'parent_office_id', 'office_id');
    }

    public function subOffices()
    {
        return $this->children();
    }

    /**
     * Get all recursive descendant IDs to prevent circular assignment.
     *
     * @return Collection<int, int>
     */
    public function allDescendantIds(): Collection
    {
        $ids = collect();
        foreach ($this->children as $child) {
            $ids->push((int) $child->office_id);
            $ids = $ids->merge($child->allDescendantIds());
        }

        return $ids->unique();
    }

    /**
     * Check if setting another office as parent would create a circular hierarchy.
     */
    public function wouldCauseCycle(int $newParentId): bool
    {
        if ($this->office_id && (int) $this->office_id === (int) $newParentId) {
            return true;
        }

        return $this->allDescendantIds()->contains((int) $newParentId);
    }

    /**
     * Breadcrumb path of ancestors for display.
     *
     * @return array<int, string>
     */
    public function hierarchyPath(): array
    {
        $path = [$this->office_name];
        $current = $this->parent;
        $seen = [(int) $this->office_id];

        while ($current && ! in_array((int) $current->office_id, $seen, true)) {
            $seen[] = (int) $current->office_id;
            array_unshift($path, $current->office_name);
            $current = $current->parent;
        }

        return $path;
    }

    public function head()
    {
        return $this->belongsTo(User::class, 'head_user_id', 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function parentOffice()
    {
        return $this->belongsTo(self::class, 'parent_office_id', 'office_id');
    }

    public function childOffices()
    {
        return $this->hasMany(self::class, 'parent_office_id', 'office_id');
    }

    public function heads()
    {
        return $this->morphMany(OrgUnitHead::class, 'headable');
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

    /**
     * Every office id in this office's branch: the office itself plus all
     * descendant offices (recursive).
     *
     * @return array<int, int>
     */
    public function branchIds(): array
    {
        $ids = collect([(int) $this->office_id]);
        $frontier = [$this->office_id];

        while ($frontier !== []) {
            $children = self::whereIn('parent_office_id', $frontier)
                ->pluck('office_id')
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $ids->contains($id))
                ->all();

            $ids = $ids->merge($children)->unique()->values();
            $frontier = $children;
        }

        return $ids->all();
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
