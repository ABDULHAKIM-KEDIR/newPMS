<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectType extends Model
{
    protected $primaryKey = 'project_type_id';

    protected $fillable = ['name', 'description', 'is_active', 'office_id'];

    protected $casts = ['is_active' => 'boolean'];

    /** Office this type is scoped to; NULL = available to every office. */
    public function office()
    {
        return $this->belongsTo(Office::class, 'office_id', 'office_id');
    }

    /**
     * Types available for a given primary office: the office's own types
     * plus all global (office-less) types. Pass NULL for everything global.
     */
    public function scopeForOffice($query, ?int $officeId)
    {
        return $query->where(function ($q) use ($officeId) {
            $q->whereNull('office_id');

            if ($officeId) {
                $q->orWhere('office_id', $officeId);
            }
        });
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'project_type_id', 'project_type_id');
    }
}
