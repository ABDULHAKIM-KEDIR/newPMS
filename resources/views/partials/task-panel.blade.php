{{-- Task detail slide-over. Alpine component fetches /tasks/{id} (JSON) and lets
     authorized users change status, reassign, comment, and manage attachments. --}}
<div x-data="taskPanel()" x-show="open" x-cloak>
  <div class="overlay" :class="{ show: open }" @click="close()"></div>
  <div class="panel" :class="{ show: open }">
    <div class="panel-head">
      <div style="flex:1; margin-right:12px; min-width:0;">
        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
          <span class="mono" style="color:var(--ink-faint); font-size:11px;" x-text="'TASK-' + String(task.id || '').padStart(4, '0')"></span>
          <template x-if="task.project">
            <a :href="task.project_url" class="mono" style="color:var(--primary); font-size:11px; text-decoration:none; background:var(--primary-soft); padding:1px 6px; border-radius:4px;" x-text="'📁 ' + task.project"></a>
          </template>
        </div>
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
      <!-- Blocker Banner if Blocked -->
      <template x-if="task.status === 'Blocked'">
        <div style="background:#fee2e2; border:1px solid #f87171; border-radius:8px; padding:12px 14px; margin-bottom:16px;">
          <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div>
              <div style="font-weight:700; color:#991b1b; font-size:13px; display:flex; align-items:center; gap:6px;">
                <span>⚠️ TASK BLOCKED</span>
              </div>
              <div style="font-size:12.5px; color:#7f1d1d; margin-top:4px; line-height:1.4;" x-text="task.blocker_reason || 'A team member reported a blocker on this task.'"></div>
            </div>
            <template x-if="task.can_update_status">
              <button type="button" class="btn btn-accent" style="padding:4px 10px; font-size:11.5px;" @click="resolveBlocker()">Resolve Blocker</button>
            </template>
          </div>
        </div>
      </template>

      <div class="field-row">
        <span class="k">Status</span>
        <span class="v">
          <template x-if="task.can_update_status || editing">
            <select
              x-model="task.status"
              @change="!editing && updateStatus()"
              style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; font-weight:700; color:inherit; background:var(--surface);"
            >
              <option value="To Do">To Do</option>
              <option value="In Progress">In Progress</option>
              <option value="In Review">In Review</option>
              <option value="Completed">Completed</option>
              <option value="Blocked">Blocked</option>
            </select>
          </template>
          <template x-if="!task.can_update_status && !editing">
            <span class="badge" :class="task.status === 'Completed' || task.status === 'Done' ? 'b-active' : (task.status === 'In Progress' ? 'b-planning' : (task.status === 'Blocked' ? 'b-blocked' : 'b-risk'))" x-text="task.status"></span>
          </template>
        </span>
      </div>

      <div class="field-row">
        <span class="k">Priority</span>
        <span class="v">
          <template x-if="task.can_manage || editing">
            <select
              x-model="task.priority"
              @change="!editing && savePriority()"
              style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; font-weight:600; background:var(--surface);"
            >
              <option value="High">High</option>
              <option value="Medium">Medium</option>
              <option value="Low">Low</option>
              <option value="Urgent">Urgent</option>
            </select>
          </template>
          <template x-if="!task.can_manage && !editing">
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
        <span class="k">Budget</span>
        <span class="v">
          <template x-if="editing">
            <input type="number" step="0.01" min="0" x-model="task.budget" placeholder="Budget in ETB" style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; background:var(--surface);">
          </template>
          <template x-if="!editing">
            <span style="font-weight:700; color:var(--ink);" x-text="task.budget ? 'ETB ' + Number(task.budget).toLocaleString() : 'ETB 0'"></span>
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

      <!-- Subtasks Section -->
      <div style="margin-top:20px; border-top:1px solid var(--line); padding-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
          <div class="stat-label" style="margin:0;">Subtasks</div>
          <span style="font-size:11.5px; color:var(--ink-soft);" x-text="(task.subtasks ? task.subtasks.filter(s => s.is_completed).length : 0) + ' / ' + (task.subtasks ? task.subtasks.length : 0) + ' completed'"></span>
        </div>

        <template x-for="s in task.subtasks" :key="s.id">
          <div style="display:flex; align-items:center; justify-content:space-between; padding:6px 10px; background:var(--bg-subtle); border:1px solid var(--line); border-radius:6px; margin-bottom:6px;">
            <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; flex:1;">
              <input type="checkbox" :checked="s.is_completed" @change="toggleSubtask(s)" style="accent-color:var(--accent); cursor:pointer;">
              <span :style="s.is_completed ? 'text-decoration:line-through; color:var(--ink-muted);' : 'color:var(--ink);'" x-text="s.name"></span>
            </label>
            <span class="badge" :class="s.is_completed ? 'b-active' : 'b-risk'" style="font-size:10px;" x-text="s.is_completed ? 'Done' : 'Pending'"></span>
          </div>
        </template>

        <div style="display:flex; gap:8px; margin-top:8px;">
          <input
            type="text"
            x-model="newSubtaskName"
            @keydown.enter="addSubtask()"
            placeholder="Add a new subtask..."
            style="flex:1; border:1px solid var(--line); border-radius:6px; padding:6px 10px; font-size:12.5px; font-family:inherit; background:var(--surface);"
          >
          <button type="button" class="btn btn-ghost" style="padding:6px 12px; font-size:12px;" @click="addSubtask()" :disabled="addingSubtask">+ Add</button>
        </div>
      </div>

      <!-- File Attachments Section -->
      <div style="margin-top:20px; border-top:1px solid var(--line); padding-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
          <div class="stat-label" style="margin:0;">Attachments</div>
          <span style="font-size:11.5px; color:var(--ink-soft);" x-text="(task.attachments ? task.attachments.length : 0) + ' file(s)'"></span>
        </div>

        <template x-for="a in task.attachments" :key="a.id">
          <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:var(--bg-subtle); border:1px solid var(--line); border-radius:6px; margin-bottom:6px;">
            <div style="display:flex; align-items:center; gap:8px; overflow:hidden;">
              <span>📎</span>
              <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                <a :href="'/attachments/' + a.id + '/download'" style="font-weight:600; font-size:13px; color:var(--accent); text-decoration:none;" x-text="a.file_name" target="_blank"></a>
                <div style="font-size:11px; color:var(--ink-muted);" x-text="'By ' + a.uploader + ' • ' + a.uploaded_at"></div>
              </div>
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
              <a :href="'/attachments/' + a.id + '/download'" class="btn btn-ghost" style="padding:3px 8px; font-size:11px;" title="Download File">⬇</a>
              <button type="button" class="btn btn-ghost" style="padding:3px 8px; font-size:11px; color:var(--danger);" @click="deleteAttachment(a.id)" title="Remove Attachment">✕</button>
            </div>
          </div>
        </template>

        <div style="margin-top:8px;">
          <label class="btn btn-ghost" style="display:inline-flex; align-items:center; gap:6px; font-size:12px; padding:6px 12px; cursor:pointer; width:100%; justify-content:center; border:1px dashed var(--line);">
            <span>📁 Upload Attachment</span>
            <input type="file" @change="uploadFile($event)" style="display:none;" :disabled="uploadingFile">
          </label>
        </div>
      </div>

      <!-- Activity & Status Trail -->
      <div style="margin-top:20px; border-top:1px solid var(--line); padding-top:16px;" x-show="task.activity_logs && task.activity_logs.length">
        <div class="stat-label" style="margin-bottom:10px;">Activity Trail &amp; Remarks</div>
        <template x-for="l in task.activity_logs" :key="l.at + l.from + l.to + (l.remarks || '')">
          <div style="padding:6px 0; border-bottom:1px solid var(--line); font-size:12px;">
            <div style="display:flex; justify-content:space-between; color:var(--ink);">
              <span>
                <strong x-text="l.user"></strong>:
                <span class="badge" style="font-size:10px;" x-text="l.from"></span> →
                <span class="badge b-active" style="font-size:10px;" x-text="l.to"></span>
              </span>
              <span style="font-size:11px; color:var(--ink-muted);" x-text="l.at"></span>
            </div>
            <template x-if="l.remarks">
              <div style="font-size:11.5px; color:var(--ink-soft); margin-top:3px; font-style:italic;" x-text="'📝 ' + l.remarks"></div>
            </template>
          </div>
        </template>
      </div>

      <!-- Comments Section -->
      <div style="margin-top:20px; border-top:1px solid var(--line); padding-top:16px;">
        <div class="stat-label" style="margin-bottom:10px;">Comments</div>
        <template x-for="c in task.comments" :key="c.id || (c.user + c.at + c.text)">
          <div class="comment">
            <div
              class="avatar"
              x-text="(c.user || '?').split(' ').map(w => w[0]).join('')"
            ></div>
            <div class="txt">
              <div class="who">
                <span x-text="c.user"></span>
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
      newSubtaskName: '',
      addingSubtask: false,
      uploadingFile: false,
      savedMessage: '',
      fileSelected: null,
      uploading: false,

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
        this.fileSelected = null;
        document.getElementById('file-input').value = ''; // reset file input
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
        try {
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
              phase_id: this.task.phase_id ? Number(this.task.phase_id) : null,
              assigned_to: this.task.assignee_name || this.task.assignee_id || null,
              budget: this.task.budget ? Number(this.task.budget) : 0,
              start_date: this.task.start_date || null,
              end_date: this.task.end_date || null,
              description: this.task.description || null
            })
          });

          if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            console.error('Failed to update task:', res.status, err);
            alert(err.message || 'Failed to save task changes.');
            return;
          }

          const data = await res.json();
          if (data.task) {
            this.task.name = data.task.task_name;
            this.task.status = data.task.status;
            this.task.priority = data.task.priority;
            this.task.phase_id = data.task.phase_id;
            this.task.assignee_id = data.task.assigned_to;
            this.task.assignee = data.task.assignee ? data.task.assignee.full_name : null;
            this.task.assignee_name = data.task.assignee ? data.task.assignee.full_name : null;
            this.task.budget = data.task.budget;
            this.task.start_date = data.task.start_date;
            this.task.end_date = data.task.end_date;
            this.task.start_date_formatted = data.task.start_date_formatted;
            this.task.end_date_formatted = data.task.end_date_formatted;
            this.task.phase = data.task.phase ? data.task.phase.phase_name : null;
            this.task.description = data.task.description;
            // Keep the project context in sync if the task moved to another
            // project's phase — the panel is shared across Projects / My Tasks.
            if (data.task.phase && data.task.phase.project) {
              this.task.project = data.task.phase.project_name;
              this.task.project_id = data.task.phase.project_id;
              this.task.project_url = '/projects/' + data.task.phase.project_id;
            }
          }

          this.editing = false;
          this.flash('Task updated successfully');
          this.dirty = true;
        } catch (e) {
          console.error('Save changes error:', e);
          alert('An error occurred while saving task changes.');
        }
      },

      async updateStatus() {
        if (this.task.status === 'Blocked' && !this.task.blocker_reason) {
          const reason = prompt('Please enter the reason why this task is blocked:');
          this.task.blocker_reason = reason || 'Blocker reported';
        }

        const res = await fetch(`/tasks/${this.task.id}/status`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.csrf()
          },
          body: JSON.stringify({
            status: this.task.status,
            blocker_reason: this.task.blocker_reason
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
        const data = await res.json();
        this.task.status = data.status;
        this.task.blocker_reason = data.blocker_reason;
        this.flash('Status updated');
        this.dirty = true;
      },

      async resolveBlocker() {
        this.task.status = 'In Progress';
        this.task.blocker_reason = null;
        await this.updateStatus();
        this.flash('Blocker resolved');
      },

      async reassign() {
        const res = await fetch(`/tasks/${this.task.id}/assign`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.csrf()
          },
          body: JSON.stringify({
            assigned_to: this.task.assignee_name || this.task.assignee_id
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
        const data = await res.json();
        this.task.assignee_id = data.assignee_id;
        this.task.assignee = data.assignee;
        this.task.assignee_name = data.assignee;
        this.flash('Reassigned');
        this.dirty = true;
      },

      async savePriority() {
        try {
          const res = await fetch(`/tasks/${this.task.id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': this.csrf()
            },
            body: JSON.stringify({ priority: this.task.priority })
          });
          if (res.ok) {
            this.flash('Priority updated');
            this.dirty = true;
          }
        } catch(e) {
          console.error(e);
        }
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
      },

      async addSubtask() {
        if (!this.newSubtaskName.trim() || this.addingSubtask) return;
        this.addingSubtask = true;
        try {
          const res = await fetch(`/tasks/${this.task.id}/subtasks`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': this.csrf()
            },
            body: JSON.stringify({ task_name: this.newSubtaskName })
          });
          if (res.ok) {
            const subtask = await res.json();
            this.task.subtasks = [...(this.task.subtasks || []), subtask];
            this.newSubtaskName = '';
            this.flash('Subtask added');
          }
        } catch(e) {
          console.error(e);
        } finally {
          this.addingSubtask = false;
        }
      },

      async toggleSubtask(subtask) {
        try {
          const res = await fetch(`/tasks/subtasks/${subtask.id}/toggle`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': this.csrf()
            }
          });
          if (res.ok) {
            const data = await res.json();
            subtask.status = data.status;
            subtask.is_completed = data.is_completed;
            this.flash('Subtask updated');
          }
        } catch(e) {
          console.error(e);
        }
      },

      async uploadFile(event) {
        const file = event.target.files[0];
        if (!file || this.uploadingFile) return;
        this.uploadingFile = true;
        const formData = new FormData();
        formData.append('file', file);

        try {
          const res = await fetch(`/tasks/${this.task.id}/attachments`, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': this.csrf()
            },
            body: formData
          });
          if (res.ok) {
            const data = await res.json();
            this.task.attachments = [...(this.task.attachments || []), data.attachment];
            this.flash('File uploaded successfully');
          } else {
            alert('Failed to upload file. Max size: 20MB.');
          }
        } catch(e) {
          console.error(e);
          alert('Upload failed.');
        } finally {
          this.uploadingFile = false;
          event.target.value = '';
        }
      },

      async deleteAttachment(attachmentId) {
        if (!confirm('Are you sure you want to remove this attachment?')) return;
        try {
          const res = await fetch(`/tasks/${this.task.id}/attachments/${attachmentId}`, {
            method: 'DELETE',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': this.csrf()
            }
          });
          if (res.ok) {
            this.task.attachments = (this.task.attachments || []).filter(a => a.id !== attachmentId);
            this.flash('Attachment removed');
          }
        } catch(e) {
          console.error(e);
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
