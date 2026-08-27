<?php

namespace App\Models;

use App\Services\RbacService;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const SCOPES = ['organization', 'project', 'team'];

    protected $primaryKey = 'role_id';

    public $timestamps = false;

    protected $fillable = ['role_name', 'description', 'scope', 'parent_role_id', 'is_system', 'rank'];

    protected $casts = ['is_system' => 'boolean', 'rank' => 'integer'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->withPivot('scope_type', 'scope_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    public function parentRole()
    {
        return $this->belongsTo(Role::class, 'parent_role_id', 'role_id');
    }

    public function childRoles()
    {
        return $this->hasMany(Role::class, 'parent_role_id', 'role_id');
    }

    /** True if the role applies system-wide (not scoped to a project/team). */
    public function isOrganizationScoped(): bool
    {
        return $this->scope === 'organization';
    }

    /** All permission slugs including inherited ones, via the RBAC engine. */
    public function effectivePermissionSlugs(): array
    {
        return app(RbacService::class)
            ->rolePermissionSlugs(collect([$this]))
            ->all();
    }
}
