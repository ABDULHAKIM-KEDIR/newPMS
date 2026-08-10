# ICT PMS — Jimma University

A Laravel 11 implementation of the ICT Project Management System database design —
built to run locally with **Laravel Herd**.

This ships as application code only (no `vendor/` or `node_modules/` — those are
installed by Composer/npm on your machine, as usual for any Laravel project).

## What's inside

- **22 migrations** — one per table in the DB design (`database/migrations`)
- **20 Eloquent models** with the relationships between them (`app/Models`)
- **A seeder** with realistic sample data — users, teams, projects, phases, tasks,
  budgets, change requests, notifications, audit log (`database/seeders/DatabaseSeeder.php`)
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
   in with any seeded account (see "Notes on this prototype" below for the list).

### Using MySQL instead of SQLite
Herd includes a one-click MySQL service if you'd rather use that. In `.env`,
comment out the `DB_CONNECTION=sqlite` line and uncomment the MySQL block below it,
then create the `ict_pms` database (Herd's "Databases" tab, or `mysql -u root -e
"create database ict_pms"`) before running `php artisan migrate --seed`.

## Notes on this prototype

- **Real login, logout, and sign-up.** Every route except `/login` and `/register`
  requires a signed-in session. Anyone can create an account at `/register` (full
  name, email, optional phone, password) — new accounts start with the **Team
  Member** role, and an ICT Director can change that afterwards from **Roles &
  Access**. Login is by email + password against the `users` table.

  The sidebar footer shows whoever is actually logged in (name, role, initials) and
  has a working **Log out** button. "My Tasks" and "Notifications" are scoped to the
  logged-in user, so different accounts see different data.

  `DatabaseSeeder.php` still creates a handful of sample users (Tariku Bekele,
  Selam Girma, etc., all with password `password`) purely so the seeded projects,
  tasks, and teams have someone to be assigned to — the login page no longer
  advertises them. Sign up your own account, or use one of the seeded ones by
  looking up its email in `database/seeders/DatabaseSeeder.php` if you want to see
  data already assigned to you.
- **Task detail panel** (`/tasks/{id}`) returns JSON, fetched client-side by Alpine —
  this is the one endpoint in the app that isn't a full page, by design, since it
  backs the slide-over panel.
- **What the landing page promises is now actually wired up, not just displayed:**
  - **Task status** can be changed from the task detail panel by the assignee or
    whoever manages that project (the project's team leader, an ICT Director, or a
    System Administrator) — writes a `task_progress_logs` row and an audit log entry.
  - **Task comments** post for real via the panel's "Send" button.
  - **Task reassignment** — project managers get an editable assignee dropdown in
    the panel, scoped to that project's team members.
  - **Change requests** get real **Approve / Reject** buttons on the project page
    (visible to ICT Directors / System Administrators only), which update the
    request and notify whoever filed it.
  - **Roles & Access** — a user's role is now an editable dropdown (Director/Admin
    only) instead of a static badge; changing it updates `user_roles` and notifies
    the user.
  - Every one of the above writes to the **audit log** and, where relevant, creates
    a **notification** for the other party — via the small `App\Support\Activity`
    helper (`app/Support/Activity.php`), so "every action logged" is now true rather
    than just seeded-looking.
  - Authorization for all of this lives in two small helpers rather than a full
    policy/gate system: `User::isDirectorOrAdmin()` and `Project::isManagedBy()`
    (checks the project's `team_leader_id`). Good enough for this prototype's scope
    — worth replacing with Laravel Policies if this goes further.
- **Every remaining button in the UI is now wired up too** (previously several were
  decorative):
  - **+ New Project** (dashboard / Projects) → a real create form at `/projects/create`
    that also auto-generates the five lifecycle phases and a budget row.
  - **Edit Project** → `/projects/{id}/edit`, updates name, type, team, status, dates,
    and allocated budget.
  - **Log Change Request** (project page) → an inline form that submits a real
    pending change request.
  - **+ New Team** → `/teams/create`.
  - **Manage →** on each team card → a real team page (`/teams/{id}`) where the
    team's leader or a Director/Admin can add or remove members.
  - **+ New Role** and the **permission matrix** on Roles & Access → creating a role
    and toggling a role's permissions (click a ✓/– cell) are both live now,
    Director/Admin only.
  - **Export CSV** on the Audit Log → streams a real CSV download.
  - The **topbar search bar** → a working `/search` page across projects, tasks,
    and people.
- Sample data lives entirely in `DatabaseSeeder.php` — re-running `php artisan
  migrate:fresh --seed` gives you a clean, consistent dataset any time.
- Currency is hard-coded to ETB in a couple of display spots; `project_budgets.currency`
  is already a column if you need multi-currency later.

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
