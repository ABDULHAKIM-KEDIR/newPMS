{{-- Task detail slide-over. Alpine component fetches /tasks/{id} (JSON) and lets
     authorized users change status, reassign, edit details, and comment --}}
<div x-data="taskPanel()" x-show="open" x-cloak>
  <div class="overlay" :class="{ show: open }" @click="close()"></div>
  <div class="panel" :class="{ show: open }">
    <div class="panel-head">
      <div style="flex:1; margin-right:12px; min-width:0;">
        <div class="mono" style="color:var(--ink-faint); font-size:11px;" x-text="'TASK-' + String(task.id || '').padStart(4, '0')"></div>
        <template x-if="editing">
          <input type="text" x-model="task.name" style="width:100%; border:1px solid var(--line); border-radius:6px; padding:6px 10px; font-size:15px; font-weight:700; margin-top:4px; font-family:inherit; background:var(--surface);">
        </template>
        <template x-if="!editing">
          <h3 style="margin-top:4px; font-size:16px; word-break:break-word;" x-text="task.name"></h3>
        </template>
      </div>
      <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
        <template x-if="task.can_manage || task.can_update_status">
          <button type="button" class="btn btn-ghost" style="padding:4px 10px; font-size:12px;" @click="editing = !editing" x-text="editing ? 'Cancel' : '✎ Edit'"></button>
        </template>
        <template x-if="task.can_manage">
          <button type="button" class="btn btn-ghost" style="padding:4px 10px; font-size:12px; color:var(--danger);" @click="deleteTask()" title="Delete Task">🗑 Delete</button>
        </template>
        <div class="panel-close" @click="close()">✕</div>
      </div>
    </div>

    <div class="panel-body">
      <div class="field-row">
        <span class="k">Status</span>
        <span class="v">
          <template x-if="task.can_update_status || editing">
            <select
              x-model="task.status"
              @change="updateStatus()"
              style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; font-weight:600; color:inherit; background:var(--surface);"
            >
              <template x-for="s in task.statuses" :key="s">
                <option :value="s" x-text="s"></option>
              </template>
            </select>
          </template>
          <template x-if="!task.can_update_status && !editing">
            <span class="badge" :class="task.status === 'Done' ? 'b-active' : (task.status === 'In Progress' ? 'b-planning' : 'b-risk')" x-text="task.status"></span>
          </template>
        </span>
      </div>

      <div class="field-row">
        <span class="k">Assignee</span>
        <span class="v">
          <template x-if="task.can_manage || editing">
            <select
              x-model="task.assignee_id"
              @change="reassign()"
              style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; font-weight:600; color:inherit; background:var(--surface);"
            >
              <option value="">— Unassigned —</option>
              <template x-for="u in task.assignable_users" :key="u.id">
                <option :value="u.id" x-text="u.name"></option>
              </template>
            </select>
          </template>
          <template x-if="!task.can_manage && !editing">
            <span x-text="task.assignee || 'Unassigned'"></span>
          </template>
        </span>
      </div>

      <div class="field-row">
        <span class="k">Priority</span>
        <span class="v">
          <template x-if="editing">
            <select x-model="task.priority" style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; background:var(--surface);">
              <option value="High">High</option>
              <option value="Medium">Medium</option>
              <option value="Low">Low</option>
            </select>
          </template>
          <template x-if="!editing">
            <span class="priority" :class="'p-' + (task.priority || '').toLowerCase()" x-text="task.priority"></span>
          </template>
        </span>
      </div>

      <div class="field-row">
        <span class="k">Phase</span>
        <span class="v">
          <template x-if="editing && task.phases && task.phases.length">
            <select
              x-model="task.phase_id"
              style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; background:var(--surface);"
            >
              <template x-for="p in task.phases" :key="p.id">
                <option :value="p.id" x-text="p.name" :selected="p.id == task.phase_id"></option>
              </template>
            </select>
          </template>
          <template x-if="!editing || !task.phases || !task.phases.length">
            <span x-text="task.phase || '—'"></span>
          </template>
        </span>
      </div>

      <div class="field-row">
        <span class="k">Start Date</span>
        <span class="v">
          <template x-if="editing">
            <input type="date" x-model="task.start_date" style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; background:var(--surface);">
          </template>
          <template x-if="!editing">
            <span x-text="task.start_date_formatted || '—'"></span>
          </template>
        </span>
      </div>

      <div class="field-row">
        <span class="k">End Date</span>
        <span class="v">
          <template x-if="editing">
            <input type="date" x-model="task.end_date" style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; background:var(--surface);">
          </template>
          <template x-if="!editing">
            <span x-text="task.end_date_formatted || '—'"></span>
          </template>
        </span>
      </div>

      <div style="margin-top:18px;">
        <div class="stat-label" style="margin-bottom:8px;">Description</div>
        <template x-if="editing">
          <textarea x-model="task.description" placeholder="Add description..." style="width:100%; border:1px solid var(--line); border-radius:6px; padding:8px 10px; font-size:13px; font-family:inherit; background:var(--surface); min-height:80px;"></textarea>
        </template>
        <template x-if="!editing">
          <div
            style="font-size:13px; color:var(--ink-soft); line-height:1.6;"
            x-text="task.description || 'No description provided.'"
          ></div>
        </template>
      </div>

      <template x-if="editing">
        <div style="margin-top:16px; display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" class="btn btn-ghost" @click="editing = false">Cancel</button>
          <button type="button" class="btn btn-primary" @click="saveTaskChanges()">Save Changes</button>
        </div>
      </template>

      <div
        x-show="savedMessage"
        x-cloak
        style="margin-top:12px; font-size:12px; color:var(--success); font-weight:600;"
        x-text="savedMessage"
      ></div>

      <div
        style="margin-top:20px;"
        x-show="task.subtasks && task.subtasks.length"
      >
        <div class="stat-label" style="margin-bottom:8px;">Subtasks</div>
        <template x-for="s in task.subtasks" :key="s.name">
          <div class="list-row">
            <span x-text="(s.status === 'Done' ? '☑ ' : '☐ ') + s.name"></span>
          </div>
        </template>
      </div>

      <div style="margin-top:20px;">
        <div class="stat-label" style="margin-bottom:10px;">
          Activity &amp; comments
        </div>
        <template x-for="c in task.comments" :key="c.text + c.at">
          <div class="comment">
            <div
              class="avatar"
              x-text="(c.user || '?').split(' ').map(w => w[0]).join('')"
            ></div>
            <div class="txt">
              <div class="who" x-text="c.user">
                <span class="when" x-text="c.at"></span>
              </div>
              <span x-text="c.text"></span>
            </div>
          </div>
        </template>
        <div
          x-show="!task.comments || !task.comments.length"
          style="font-size:12.5px; color:var(--ink-faint);"
        >
          No comments yet.
        </div>
        <div style="display:flex; gap:8px; margin-top:12px;">
          <input
            x-model="newComment"
            @keydown.enter="postComment()"
            placeholder="Write a comment…"
            style="flex:1; border:1px solid var(--line); border-radius:8px; padding:9px 11px; font-size:12.8px; font-family:inherit;"
          >
          <button
            class="btn btn-primary"
            style="padding:9px 14px;"
            @click="postComment()"
            :disabled="posting"
          >
            Send
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function taskPanel() {
    return {
      open: false,
      editing: false,
      task: {},
      dirty: false,
      newComment: '',
      posting: false,
      savedMessage: '',

      csrf() {
        return document.querySelector('meta[name="csrf-token"]').content;
      },

      async show(taskId, startInEditMode = false) {
        const res = await fetch(`/tasks/${taskId}`);
        if (!res.ok) {
          console.error('Failed to load task:', res.status, res.statusText);
          return;
        }
        this.task = await res.json();
        this.open = true;
        this.editing = startInEditMode;
        this.dirty = false;
      },

      close() {
        this.open = false;
        // Kanban / My Tasks are rendered server-side, so if status or
        // assignee changed while the panel was open, refresh to match.
        if (this.dirty) {
          window.location.reload();
        }
      },

      flash(msg) {
        this.savedMessage = msg;
        setTimeout(() => {
          this.savedMessage = '';
        }, 2500);
      },

      async deleteTask() {
        if (!confirm(`Are you sure you want to delete task "${this.task.name}"?`)) {
          return;
        }

        try {
          const res = await fetch(`/tasks/${this.task.id}`, {
            method: 'DELETE',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': this.csrf()
            }
          });

          if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            alert(err.message || 'Failed to delete task.');
            return;
          }

          this.open = false;
          window.location.reload();
        } catch (e) {
          console.error('Delete error:', e);
          alert('An error occurred while deleting the task.');
        }
      },

      async saveTaskChanges() {
        const res = await fetch(`/tasks/${this.task.id}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.csrf()
          },
          body: JSON.stringify({
            task_name: this.task.name,
            status: this.task.status,
            priority: this.task.priority,
            phase_id: this.task.phase_id,
            assigned_to: this.task.assignee_id,
            start_date: this.task.start_date,
            end_date: this.task.end_date,
            description: this.task.description
          })
        });

        if (!res.ok) {
          console.error('Failed to update task:', res.status, await res.text());
          return;
        }

        this.editing = false;
        this.flash('Task updated successfully');
        this.dirty = true;
      },

      async updateStatus() {
        const res = await fetch(`/tasks/${this.task.id}/status`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.csrf()
          },
          body: JSON.stringify({
            status: this.task.status
          })
        });
        if (!res.ok) {
          console.error(
            'Failed to update status:',
            res.status,
            await res.text()
          );
          return;
        }
        this.flash('Status updated');
        this.dirty = true;
      },

      async reassign() {
        const res = await fetch(`/tasks/${this.task.id}/assign`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.csrf()
          },
          body: JSON.stringify({
            assigned_to: this.task.assignee_id
          })
        });
        if (!res.ok) {
          console.error(
            'Failed to reassign task:',
            res.status,
            await res.text()
          );
          return;
        }
        this.flash('Reassigned');
        this.dirty = true;
      },

      async postComment() {
        if (!this.newComment.trim() || this.posting) {
          return;
        }
        this.posting = true;
        try {
          const res = await fetch(`/tasks/${this.task.id}/comments`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': this.csrf()
            },
            body: JSON.stringify({
              comment_text: this.newComment
            })
          });
          if (!res.ok) {
            console.error(
              'Failed to post comment:',
              res.status,
              await res.text()
            );
            return;
          }
          const comment = await res.json();
          this.task.comments = [
            ...(this.task.comments || []),
            comment
          ];
          this.newComment = '';
        } finally {
          this.posting = false;
        }
      }
    };
  }

  // Global helper so any onclick="openTask(id, editMode)" in the page
  // can reach the Alpine component using Alpine v3's public API.
  window.openTask = (id, editMode = false) => {
    const panel = document.querySelector('[x-data^="taskPanel"]');
    if (!panel) {
      console.error('Task panel element not found.');
      return;
    }
    Alpine.$data(panel).show(id, editMode);
  };
</script>