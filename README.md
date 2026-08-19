# ICT PMS — Jimma University

A Laravel 11 implementation of the ICT Project Management System database design —
built to run locally with **Laravel Herd**.

This ships as application code only (no `vendor/` or `node_modules/` — those are
installed by Composer/npm on your machine, as usual for any Laravel project).

## What's inside

- **22 migrations** — one per table in the DB design (`database/migrations`)
- **20 Eloquent models** with the relationships between them (`app/Models`)
- **A seeder** that creates the RBAC scaffolding (4 roles, 15 permissions, the
  grants between them) plus exactly **one bootstrap account** — a System
  Administrator — and nothing else. This is a clean install, not demo data
  (`database/seeders/DatabaseSeeder.php`)
- **8 controllers** + routes for Dashboard, Projects, Tasks, Teams, Budgets, Roles &
  Access, Audit Log, Notifications (`app/Http/Controllers`, `routes/web.php`)
- **Blade views** implementing the UI mockup — sidebar/topbar layout, the "phase rail"
  component, project Kanban board, task detail slide-over (Alpine.js), tables and cards
  (`resources/views`)
- The same design system (colors, type, components) as the earlier HTML mockup, now
  as `resources/css/app.css`, compiled through Vite

## Setup with Laravel Herd

1. **Unzip this folder** into Herd's sites directory (Herd → Settings → Sites shows
   the path, typically `~/Herd`), e.g.:
   ```
   ~/Herd/ict-pms
   ```
   Herd auto-detects the folder and serves it at **`http://ict-pms.test`**.

2. **Install PHP dependencies** (Herd bundles PHP + Composer — open a terminal in
   the project folder):
   ```bash
   composer install
   ```

3. **Set up the environment file** (already included as `.env`, pointed at SQLite —
   no database server needed):
   ```bash
   php artisan key:generate
   ```
   The empty `database/database.sqlite` file is already included. If it's missing:
   ```bash
   touch database/database.sqlite
   ```

4. **Run migrations and seed sample data**:
   ```bash
   php artisan migrate --seed
   ```

5. **Install JS dependencies and build the CSS**:
   ```bash
   npm install
   npm run build
   ```
   (or `npm run dev` while you're actively editing styles)

6. Open **`http://ict-pms.test`** — Herd starts serving automatically once the site
   is detected; no need to run `php artisan serve`. You'll land on `/login` — sign
   in with `admin@ju.edu.et` / `ChangeMe123!` (the one seeded account — see
   "Notes on this app" below for what to do with it).

### Using MySQL instead of SQLite
Herd includes a one-click MySQL service if you'd rather use that. In `.env`,
comment out the `DB_CONNECTION=sqlite` line and uncomment the MySQL block below it,
then create the `ict_pms` database (Herd's "Databases" tab, or `mysql -u root -e
"create database ict_pms"`) before running `php artisan migrate --seed`.

## Notes on this app

- **There's no self-service sign-up.** `/register` has been removed entirely —
  accounts are created by a System Administrator from **Users**
  (`/admin/users`), who sets the person's name, email, temporary password, and
  starting role at creation time. The login page reflects this: no "Sign up"
  link, just a note to contact your System Administrator.

- **The one account that exists after a fresh install:**
  ```
  admin@ju.edu.et
  ChangeMe123!
  ```
  This is a **System Administrator** — by design, that role can manage users
  and roles but doesn't have project/team/budget permissions (see the RBAC
  breakdown below). Its first real job is to create the actual people who'll
  use the system: go to **Users → + New User**, create someone as an
  **ICT Director** (or promote a Team Leader later), then let that account
  create the first teams and projects. Change this password after logging in
  for the first time — there's no in-app "change my own password" screen yet,
  so for now that means editing the user's password hash directly or adding
  one if you need it.

- **Full role-based access control, backed by the database, not hard-coded
  role names.** `roles` → `permissions` → `role_permissions` (already in the
  original schema) is the actual source of truth at runtime: `User::hasPermission()`
  plus a `Gate::before` in `AppServiceProvider` mean `$user->can('edit_projects')`,
  `@can('manage_users')` in Blade, and `->middleware('can:approve_change_requests')`
  on a route all just work — granting or revoking a permission from **Roles &
  Access** takes effect immediately, no deploy needed. The canonical permission
  list and each role's starting grant live in one place, `app/Support/Permissions.php`.

  Default grants:
  - **ICT Director** — full project/task/team/budget/change-request authority,
    plus viewing the audit log. Not user or role management.
  - **Team Leader** — can create and edit projects, create/assign tasks, manage
    team membership. Actual "is this your project" scoping still comes from
    being set as a specific team's `team_leader_id`, not the role label alone.
  - **Team Member** — read access everywhere, can update the status of tasks
    assigned to them. Can't create or edit anything organizational.
  - **System Administrator** — manages users, roles, permissions, the audit
    log, and system settings. Deliberately *not* given project/budget
    authority by default — that's an ICT Director's job. A System
    Administrator can grant themselves more from Roles & Access if a
    directorate wants to run that way, but it's not the default.

  Every sensitive route carries `->middleware('can:...')` **and** a matching
  controller-level check — hitting a restricted URL directly returns a
  branded 403 page (`resources/views/errors/403.blade.php`), not just a
  hidden button.

- **User management** (`/admin/users`, System Administrator only): list with
  search + Active/Inactive filter, create, edit, and activate/deactivate.
  Deactivating someone takes effect immediately, not just at their next
  login — a small `EnsureUserIsActive` middleware force-logs-out an existing
  session the moment their status flips.

- **System Settings** (`/admin/settings`, System Administrator only) —
  directorate name, default currency, session-timeout notice, support email,
  stored in a `system_settings` key/value table.

- **The audit log captures more than before**: IP address on every entry, plus
  login/logout, user creation/deactivation, role changes, task
  assignment/status changes, project changes, change-request decisions, and
  permission/role edits — via a small `App\Support\Activity` helper
  (`app/Support/Activity.php`), not just the handful of actions seeded data
  used to fake.

- **Task detail panel** (`/tasks/{id}`) returns JSON, fetched client-side by
  Alpine — the one endpoint in the app that isn't a full page, since it backs
  the slide-over panel. Status changes, comments, and reassignment there are
  all real, permission-checked writes.

- **Dashboard is scoped by role** — an ICT Director/System Administrator sees
  directorate-wide numbers; anyone else sees only their own team(s).


## Folder structure

```
ict-pms/
├── app/
│   ├── Http/Controllers/     Dashboard, Project, Task, Team, Budget, Role, AuditLog, Notification
│   ├── Models/                20 Eloquent models mapped to the 22 tables
│   └── Providers/
├── database/
│   ├── migrations/            22 migrations, one per table
│   └── seeders/DatabaseSeeder.php
├── resources/
│   ├── css/app.css            Design tokens + all UI components
│   ├── js/app.js
│   └── views/
│       ├── layouts/app.blade.php
│       ├── partials/          sidebar, topbar, phase-rail, task-panel
│       ├── dashboard.blade.php
│       ├── projects/          index.blade.php, show.blade.php
│       ├── tasks/index.blade.php
│       ├── teams/index.blade.php
│       ├── budgets/index.blade.php
│       ├── admin/              roles.blade.php, audit.blade.php
│       └── notifications/index.blade.php
├── routes/web.php
├── public/index.php
└── composer.json / package.json / vite.config.js
```
