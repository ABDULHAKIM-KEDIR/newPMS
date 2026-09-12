<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $primaryKey = 'department_id';

    protected $fillable = [
        'department_name', 'department_code', 'parent_department_id', 'head_user_id', 'status',
    ];

    public function parentDepartment()
    {
        return $this->belongsTo(self::class, 'parent_department_id', 'department_id');
    }

    public function childDepartments()
    {
        return $this->hasMany(self::class, 'parent_department_id', 'department_id');
    }

    public function head()
    {
        return $this->belongsTo(User::class, 'head_user_id', 'user_id');
    }

    public function offices()
    {
        return $this->hasMany(Office::class, 'department_id', 'department_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'department_id', 'department_id');
    }

    public function heads()
    {
        return $this->morphMany(OrgUnitHead::class, 'headable');
    }
}
