{{-- Task detail slide-over. Alpine component fetches /tasks/{id} (JSON) and lets
     authorized users change status, reassign, and comment — all persisted via
     the routes in TaskController. --}}
<div x-data="taskPanel()" x-show="open" x-cloak>
  <div class="overlay" :class="{ show: open }" @click="close()"></div>
  <div class="panel" :class="{ show: open }">
    <div class="panel-head">
      <div>
        <div class="mono" style="color:var(--ink-faint); font-size:11px;" x-text="'TASK-' + task.id"></div>
        <h3 style="margin-top:4px; font-size:16px;" x-text="task.name"></h3>
      </div>
      <div class="panel-close" @click="close()">✕</div>
    </div>
    <div class="panel-body">
      <div class="field-row">
        <span class="k">Status</span>
        <span class="v">
          <template x-if="task.can_update_status">
            <select x-model="task.status" @change="updateStatus()" style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; font-weight:600; color:inherit; background:var(--surface);">
              <template x-for="s in task.statuses" :key="s"><option :value="s" x-text="s"></option></template>
            </select>
          </template>
          <template x-if="!task.can_update_status"><span x-text="task.status"></span></template>
        </span>
      </div>
      <div class="field-row">
        <span class="k">Assignee</span>
        <span class="v">
          <template x-if="task.can_manage">
            <select x-model="task.assignee_id" @change="reassign()" style="border:1px solid var(--line); border-radius:6px; padding:4px 8px; font-size:12.5px; font-family:inherit; font-weight:600; color:inherit; background:var(--surface);">
              <template x-for="u in task.assignable_users" :key="u.id"><option :value="u.id" x-text="u.name"></option></template>
            </select>
          </template>
          <template x-if="!task.can_manage"><span x-text="task.assignee"></span></template>
        </span>
      </div>
      <div class="field-row"><span class="k">Priority</span><span class="v" x-text="task.priority"></span></div>
      <div class="field-row"><span class="k">Phase</span><span class="v" x-text="task.phase"></span></div>
      <div class="field-row"><span class="k">Due</span><span class="v" x-text="task.due"></span></div>

      <div x-show="savedMessage" x-cloak style="margin-top:12px; font-size:12px; color:var(--success); font-weight:600;" x-text="savedMessage"></div>

      <div style="margin-top:18px;">
        <div class="stat-label" style="margin-bottom:8px;">Description</div>
        <div style="font-size:13px; color:var(--ink-soft); line-height:1.6;" x-text="task.description"></div>
      </div>

      <div style="margin-top:20px;" x-show="task.subtasks && task.subtasks.length">
        <div class="stat-label" style="margin-bottom:8px;">Subtasks</div>
        <template x-for="s in task.subtasks" :key="s.name">
          <div class="list-row"><span x-text="(s.status === 'Done' ? '☑ ' : '☐ ') + s.name"></span></div>
        </template>
      </div>

      <div style="margin-top:20px;">
        <div class="stat-label" style="margin-bottom:10px;">Activity &amp; comments</div>
        <template x-for="c in task.comments" :key="c.text + c.at">
          <div class="comment">
            <div class="avatar" x-text="(c.user || '?').split(' ').map(w=>w[0]).join('')"></div>
            <div class="txt">
              <div class="who" x-text="c.user"><span class="when" x-text="c.at"></span></div>
              <span x-text="c.text"></span>
            </div>
          </div>
        </template>
        <div x-show="!task.comments || !task.comments.length" style="font-size:12.5px; color:var(--ink-faint);">No comments yet.</div>
        <div style="display:flex; gap:8px; margin-top:12px;">
          <input x-model="newComment" @keydown.enter="postComment()" placeholder="Write a comment…" style="flex:1; border:1px solid var(--line); border-radius:8px; padding:9px 11px; font-size:12.8px; font-family:inherit;">
          <button class="btn btn-primary" style="padding:9px 14px;" @click="postComment()" :disabled="posting">Send</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function taskPanel() {
    return {
      open: false,
      task: {},
      dirty: false,
      newComment: '',
      posting: false,
      savedMessage: '',

      csrf() {
        return document.querySelector('meta[name="csrf-token"]').content;
      },

      async show(taskId) {
        const res = await fetch(`/tasks/${taskId}`);
        this.task = await res.json();
        this.open = true;
        this.dirty = false;
      },
      close() {
        this.open = false;
        // Kanban / My Tasks are rendered server-side, so if status or
        // assignee changed while the panel was open, refresh to match.
        if (this.dirty) window.location.reload();
      },

      flash(msg) {
        this.savedMessage = msg;
        setTimeout(() => { this.savedMessage = ''; }, 2500);
      },

      async updateStatus() {
        await fetch(`/tasks/${this.task.id}/status`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
          body: JSON.stringify({ status: this.task.status }),
        });
        this.flash('Status updated');
        this.dirty = true;
      },

      async reassign() {
        await fetch(`/tasks/${this.task.id}/assign`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
          body: JSON.stringify({ assigned_to: this.task.assignee_id }),
        });
        this.flash('Reassigned');
        this.dirty = true;
      },

      async postComment() {
        if (!this.newComment.trim() || this.posting) return;
        this.posting = true;
        const res = await fetch(`/tasks/${this.task.id}/comments`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
          body: JSON.stringify({ comment_text: this.newComment }),
        });
        const comment = await res.json();
        this.task.comments = [...(this.task.comments || []), comment];
        this.newComment = '';
        this.posting = false;
      },
    }
  }
  // Global helper so any onclick="openTask(id)" in the page can reach the Alpine component.
  window.openTask = (id) => {
    document.querySelector('[x-data^="taskPanel"]').__x.$data.show(id);
  };
</script>
