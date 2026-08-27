<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

/**
 * Upserts the permission catalogue and the default dynamic roles.
 * Safe to re-run: permissions and roles are matched by name, permission
 * sets are re-synced, and legacy directorate roles are aliased onto the
 * new default roles so existing user_roles rows keep working.
 */
class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Permissions (upsert by slug).
        foreach (Permissions::ALL as $slug => [$description, $group]) {
            Permission::updateOrCreate(
                ['permission_name' => $slug],
                ['description' => $description, 'group' => $group]
            );
        }

        $allPermissionIds = Permission::pluck('permission_id', 'permission_name');

        // 2. Default roles (upsert by name), then parents, then permissions.
        $roles = [];

        foreach (Permissions::DEFAULT_ROLES as $name => $def) {
            $roles[$name] = Role::updateOrCreate(
                ['role_name' => $name],
                [
                    'description' => "$name role",
                    'scope' => $def['scope'],
                    'rank' => $def['rank'],
                    'is_system' => true,
                ]
            );
        }

        foreach (Permissions::DEFAULT_ROLES as $name => $def) {
            if ($def['parent'] && isset($roles[$def['parent']])) {
                $roles[$name]->update(['parent_role_id' => $roles[$def['parent']]->role_id]);
            }

            $slugs = $def['permissions'] === '*'
                ? array_keys(Permissions::ALL)
                : $def['permissions'];

            $ids = collect($slugs)
                ->map(fn ($slug) => $allPermissionIds[$slug] ?? null)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $roles[$name]->permissions()->sync($ids);
        }

        // 3. Alias legacy directorate roles: re-point their permission sets
        //    to the canonical default role and re-point assigned users.
        foreach (Permissions::LEGACY_ROLE_ALIASES as $legacy => $canonical) {
            $legacyRole = Role::where('role_name', $legacy)->first();

            if (! $legacyRole || ! isset($roles[$canonical])) {
                continue;
            }

            $canonicalRole = $roles[$canonical];

            // Move user assignments over, avoiding duplicates.
            $existing = $canonicalRole->users()->pluck('users.user_id')->all();

            $legacyRole->users()
                ->whereNotIn('users.user_id', $existing)
                ->get()
                ->each(fn ($u) => $canonicalRole->users()->syncWithoutDetaching([$u->user_id]));

            $legacyRole->users()->detach();
            $legacyRole->permissions()->detach();
            $legacyRole->delete();
        }
    }
}
