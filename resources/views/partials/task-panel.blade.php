{{-- Task detail slide-over. Alpine component fetches /tasks/{id} (JSON) and renders it. --}}
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
      <div class="field-row"><span class="k">Status</span><span class="v" x-text="task.status"></span></div>
      <div class="field-row"><span class="k">Assignee</span><span class="v" x-text="task.assignee"></span></div>
      <div class="field-row"><span class="k">Priority</span><span class="v" x-text="task.priority"></span></div>
      <div class="field-row"><span class="k">Phase</span><span class="v" x-text="task.phase"></span></div>
      <div class="field-row"><span class="k">Due</span><span class="v" x-text="task.due"></span></div>

      <div style="margin-top:18px;">
        <div class="stat-label" style="margin-bottom:8px;">Description</div>
        <div style="font-size:13px; color:var(--ink-soft); line-height:1.6;" x-text="task.description"></div>
      </div>

      <div style="margin-top:20px;">
        <div class="stat-label" style="margin-bottom:8px;">Subtasks</div>
        <template x-for="s in task.subtasks" :key="s.name">
          <div class="list-row"><span x-text="(s.status === 'Done' ? '☑ ' : '☐ ') + s.name"></span></div>
        </template>
      </div>

      <div style="margin-top:20px;">
        <div class="stat-label" style="margin-bottom:10px;">Activity &amp; comments</div>
        <template x-for="c in task.comments" :key="c.text">
          <div class="comment">
            <div class="avatar" x-text="(c.user || '?').split(' ').map(w=>w[0]).join('')"></div>
            <div class="txt">
              <div class="who" x-text="c.user"><span class="when" x-text="c.at"></span></div>
              <span x-text="c.text"></span>
            </div>
          </div>
        </template>
        <div style="display:flex; gap:8px; margin-top:12px;">
          <input placeholder="Write a comment…" style="flex:1; border:1px solid var(--line); border-radius:8px; padding:9px 11px; font-size:12.8px; font-family:inherit;">
          <button class="btn btn-primary" style="padding:9px 14px;">Send</button>
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
      async show(taskId) {
        const res = await fetch(`/tasks/${taskId}`);
        this.task = await res.json();
        this.open = true;
      },
      close() { this.open = false; }
    }
  }
  // Global helper so any onclick="openTask(id)" in the page can reach the Alpine component.
  window.openTask = (id) => {
    document.querySelector('[x-data^="taskPanel"]').__x.$data.show(id);
  };
</script>
