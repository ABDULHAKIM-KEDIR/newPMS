@extends('layouts.landing')
@section('title', 'ICT PMS — Jimma University')

@section('content')

<nav class="lp-nav" id="lpNav">
  <div class="lp-nav-brand">
    <div class="mark">JU</div>
    <div class="name">ICT PMS</div>
  </div>
  <div class="lp-nav-links">
    <a href="#features">Features</a>
    <a href="#workflow">Workflow</a>
    <a href="#roles">Roles</a>
    <a href="#faq">FAQ</a>
  </div>
  <div class="lp-nav-cta">
    <a href="{{ route('login') }}" class="btn btn-ghost">Sign in</a>
    <a href="{{ route('register') }}" class="btn btn-accent">Get started</a>
  </div>
</nav>

<header class="lp-hero">
  <div class="lp-hero-inner">
    <div>
      <div class="lp-badge"><span class="dot"></span> Built for Jimma University's ICT Directorate</div>
      <h1>Run every ICT project with <span class="accent-word">total clarity.</span></h1>
      <p class="lead">
        Projects, phases, tasks, budgets, and teams — tracked in one place, from
        initiation to closure. No more status updates scattered across email,
        spreadsheets, and group chats.
      </p>
      <div class="lp-hero-cta">
        <a href="{{ route('register') }}" class="btn btn-accent">Get started free</a>
        <a href="#features" class="btn btn-ghost">See how it works</a>
      </div>
      <div class="lp-microstats">
        <div><span class="n" data-count="{{ $stats['projects'] }}">0</span><span class="l">Projects tracked</span></div>
        <div><span class="n" data-count="{{ $stats['teams'] }}">0</span><span class="l">Directorate teams</span></div>
        <div><span class="n" data-count="{{ $stats['tasks_done'] }}">0</span><span class="l">Tasks completed</span></div>
        <div><span class="n" data-count="{{ $stats['members'] }}">0</span><span class="l">Team members</span></div>
      </div>
    </div>

    <div class="lp-hero-visual">
      <div class="lp-float-card c1">
        <div class="t">🔔 Budget alert</div>
        <div class="s">ERP Migration crossed 70% utilisation</div>
      </div>
      <div class="lp-mock">
        <div class="lp-mock-head"><span></span><span></span><span></span></div>
        <div class="lp-mock-body">
          <div class="lp-mock-row">
            <h4>Student Records Portal Revamp</h4>
            <span class="lp-mock-badge">On track</span>
          </div>
          @include('partials.phase-rail', ['currentIndex' => 2, 'mini' => false])
          <div style="margin-top:18px; display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
            <div style="background:var(--surface-alt); border-radius:9px; padding:10px;">
              <div style="font-size:10px; color:var(--ink-soft); margin-bottom:4px;">Pending</div>
              <div style="font-family:'IBM Plex Mono'; font-weight:600; font-size:15px;">4</div>
            </div>
            <div style="background:var(--surface-alt); border-radius:9px; padding:10px;">
              <div style="font-size:10px; color:var(--ink-soft); margin-bottom:4px;">In Progress</div>
              <div style="font-family:'IBM Plex Mono'; font-weight:600; font-size:15px;">6</div>
            </div>
            <div style="background:var(--surface-alt); border-radius:9px; padding:10px;">
              <div style="font-size:10px; color:var(--ink-soft); margin-bottom:4px;">Done</div>
              <div style="font-family:'IBM Plex Mono'; font-weight:600; font-size:15px;">12</div>
            </div>
          </div>
        </div>
      </div>
      <div class="lp-float-card c2">
        <div class="t">✅ Rahel Tesfaye</div>
        <div class="s">marked "Migrate legacy records" as Done</div>
      </div>
    </div>
  </div>
</header>

<div class="lp-stats-strip" data-reveal-group>
  <div class="stat" data-reveal-item><div class="n" data-count="{{ $stats['projects'] }}">0</div><div class="l">Active &amp; past projects</div></div>
  <div class="stat" data-reveal-item><div class="n" data-count="{{ $stats['teams'] }}">0</div><div class="l">Teams coordinated</div></div>
  <div class="stat" data-reveal-item><div class="n" data-count="{{ $stats['tasks_done'] }}">0</div><div class="l">Tasks shipped</div></div>
  <div class="stat" data-reveal-item><div class="n" data-count="5">0</div><div class="l">Lifecycle phases tracked</div></div>
</div>

{{-- ================= FEATURES ================= --}}
<section class="lp-section" id="features">
  <div class="lp-section-head" data-reveal>
    <div class="lp-eyebrow">Everything in one place</div>
    <h2>Built for how the directorate actually works</h2>
    <p>Six pieces that replace the spreadsheets, email threads, and status meetings.</p>
  </div>
  <div class="lp-features" data-reveal-group>
    <div class="lp-feature" data-reveal-item>
      <div class="icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg></div>
      <h3>Project &amp; phase tracking</h3>
      <p>Every project moves through Initiation, Planning, Execution, Monitoring, and Closure — always visible at a glance.</p>
    </div>
    <div class="lp-feature" data-reveal-item>
      <div class="icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
      <h3>Kanban task boards</h3>
      <p>Pending, in progress, done — with subtasks, dependencies, and threaded comments on every task.</p>
    </div>
    <div class="lp-feature" data-reveal-item>
      <div class="icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v10M9.5 9.2c0-1.2 1.1-2 2.5-2s2.5.9 2.5 2c0 3-5 1.6-5 4.6 0 1.2 1.1 2 2.5 2s2.5-.8 2.5-2"/></svg></div>
      <h3>Budget monitoring</h3>
      <p>Allocated vs. spent at both the project and phase level, with automatic utilisation alerts.</p>
    </div>
    <div class="lp-feature" data-reveal-item>
      <div class="icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c.7-3.5 3.3-5.5 6.5-5.5s5.8 2 6.5 5.5"/><circle cx="18" cy="8" r="2.6"/><path d="M15.8 14.7c2.4.4 4.1 2.1 4.7 5.3"/></svg></div>
      <h3>Team collaboration</h3>
      <p>Teams, leads, and membership — with every project scoped to the right team automatically.</p>
    </div>
    <div class="lp-feature" data-reveal-item>
      <div class="icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l1.6 3.2 3.5.4-2.6 2.5.7 3.5L12 10.9 8.8 12.6l.7-3.5-2.6-2.5 3.5-.4L12 3Z"/><circle cx="12" cy="17" r="4"/></svg></div>
      <h3>Role-based access</h3>
      <p>ICT Director, Team Leader, Team Member, System Administrator — each with its own permission set.</p>
    </div>
    <div class="lp-feature" data-reveal-item>
      <div class="icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h8M8 17h4"/></svg></div>
      <h3>Full audit trail</h3>
      <p>Every status change, approval, and edit logged — who did what, and when, always answerable.</p>
    </div>
  </div>
</section>

{{-- ================= BEFORE / AFTER ================= --}}
<section class="lp-section" style="padding-top:0;">
  <div class="lp-compare" data-reveal-group>
    <div class="lp-compare-card before" data-reveal-item>
      <h3>😩 Without ICT PMS</h3>
      <ul>
        <li>Project status lives in someone's inbox, not a shared view</li>
        <li>Budgets tracked in spreadsheets nobody else can see</li>
        <li>No record of who approved what, or when</li>
        <li>Tasks fall through the cracks between teams</li>
        <li>"What phase are we in?" needs a meeting to answer</li>
      </ul>
    </div>
    <div class="lp-compare-card after" data-reveal-item>
      <h3>✨ With ICT PMS</h3>
      <ul>
        <li>Every project's status is one click away, for anyone with access</li>
        <li>Budgets tracked at the project and phase level, visible instantly</li>
        <li>Every approval and status change logged automatically</li>
        <li>Kanban boards keep tasks visible until they're actually done</li>
        <li>The phase rail answers "what phase are we in?" at a glance</li>
      </ul>
    </div>
  </div>
</section>

{{-- ================= WORKFLOW ================= --}}
<section class="lp-section lp-workflow" id="workflow">
  <div class="lp-section-head" data-reveal>
    <div class="lp-eyebrow">The lifecycle</div>
    <h2>One workflow, from kickoff to closure</h2>
    <p>Every project — software, network infrastructure, or training — moves through the same five stages, so status means the same thing everywhere.</p>
  </div>
  <div class="lp-workflow-card" data-reveal>
    @include('partials.phase-rail', ['currentIndex' => 2, 'mini' => false])
    <div class="lp-workflow-legend">
      <div><h5>Initiation</h5><p>Scope, sponsor, and success criteria agreed.</p></div>
      <div><h5>Planning</h5><p>Phases, budget, and team assignments locked in.</p></div>
      <div><h5>Execution</h5><p>Tasks move through the board; work gets done.</p></div>
      <div><h5>Monitoring</h5><p>Budget, risk, and change requests tracked live.</p></div>
      <div><h5>Closure</h5><p>Deliverables signed off; project archived.</p></div>
    </div>
  </div>
</section>

{{-- ================= GALLERY ================= --}}
<section class="lp-section">
  <div class="lp-section-head" data-reveal>
    <div class="lp-eyebrow">See it in action</div>
    <h2>A closer look at the workspace</h2>
    <p>The same views your team will use every day.</p>
  </div>
  <div class="lp-gallery" data-reveal-group>
    <div class="lp-gallery-card" data-reveal-item>
      <div class="lp-gallery-head"><span>Budget overview</span><span class="mono" style="font-size:10px;color:var(--ink-faint);">68%</span></div>
      <div class="lp-gallery-body">
        <div class="lp-mini-donut"><span>68%</span></div>
        <div class="lp-mini-bar"><div style="width:92%; background:var(--success);"></div></div>
        <div class="lp-mini-bar"><div style="width:61%;"></div></div>
        <div class="lp-mini-bar"><div style="width:34%; background:var(--warning);"></div></div>
      </div>
    </div>
    <div class="lp-gallery-card" data-reveal-item>
      <div class="lp-gallery-head"><span>Task board</span><span class="mono" style="font-size:10px;color:var(--ink-faint);">12 tasks</span></div>
      <div class="lp-gallery-body">
        <div class="lp-mini-kanban">
          <div class="lp-mini-col"><div class="h">Pending</div><div class="lp-mini-card">Security review</div><div class="lp-mini-card">UAT sign-off</div></div>
          <div class="lp-mini-col"><div class="h">Doing</div><div class="lp-mini-card">SSO integration</div><div class="lp-mini-card">Migrate records</div></div>
          <div class="lp-mini-col"><div class="h">Done</div><div class="lp-mini-card">Unit tests</div></div>
        </div>
      </div>
    </div>
    <div class="lp-gallery-card" data-reveal-item>
      <div class="lp-gallery-head"><span>Audit log</span><span class="mono" style="font-size:10px;color:var(--ink-faint);">Live</span></div>
      <div class="lp-gallery-body" style="display:flex; flex-direction:column; gap:8px;">
        <div style="font-size:10.5px; color:var(--ink-soft);"><b style="color:var(--ink);">Rahel T.</b> updated task status <span style="color:var(--ink-faint);">· 2h ago</span></div>
        <div style="font-size:10.5px; color:var(--ink-soft);"><b style="color:var(--ink);">Tariku B.</b> approved change request <span style="color:var(--ink-faint);">· 3h ago</span></div>
        <div style="font-size:10.5px; color:var(--ink-soft);"><b style="color:var(--ink);">System</b> budget threshold alert <span style="color:var(--ink-faint);">· 1d ago</span></div>
        <div style="font-size:10.5px; color:var(--ink-soft);"><b style="color:var(--ink);">Selam G.</b> created change request <span style="color:var(--ink-faint);">· 2d ago</span></div>
      </div>
    </div>
  </div>
</section>

{{-- ================= ROLES ================= --}}
<section class="lp-section lp-workflow" id="roles">
  <div class="lp-section-head" data-reveal>
    <div class="lp-eyebrow">Role-based access</div>
    <h2>Built around your directorate's structure</h2>
    <p>Every account gets exactly the access their role needs — nothing more.</p>
  </div>
  <div class="lp-roles" data-reveal-group>
    <div class="lp-role-card" data-reveal-item>
      <span class="tag">DIRECTOR</span>
      <h3>ICT Director</h3>
      <ul>
        <li>Full visibility across all projects</li>
        <li>Approves change requests</li>
        <li>Manages budgets directorate-wide</li>
      </ul>
    </div>
    <div class="lp-role-card" data-reveal-item>
      <span class="tag">LEADER</span>
      <h3>Team Leader</h3>
      <ul>
        <li>Assigns and reassigns tasks</li>
        <li>Manages their team's membership</li>
        <li>Tracks their team's project phases</li>
      </ul>
    </div>
    <div class="lp-role-card" data-reveal-item>
      <span class="tag">MEMBER</span>
      <h3>Team Member</h3>
      <ul>
        <li>Updates status on assigned tasks</li>
        <li>Comments and logs progress</li>
        <li>Sees their own workload clearly</li>
      </ul>
    </div>
    <div class="lp-role-card" data-reveal-item>
      <span class="tag">ADMIN</span>
      <h3>System Administrator</h3>
      <ul>
        <li>Manages users and role assignments</li>
        <li>Full access to the audit log</li>
        <li>Keeps the system configured</li>
      </ul>
    </div>
  </div>
</section>

{{-- ================= QUOTE ================= --}}
<section class="lp-section">
  <div class="lp-quote" data-reveal>
    <blockquote>"We finally have one place where anyone can answer 'where's this project at?' — without a meeting."</blockquote>
    <div class="lp-quote-who">
      <div class="avatar">IC</div>
      <div class="meta"><div class="n">ICT Directorate</div><div class="r">Jimma University</div></div>
    </div>
  </div>
</section>

{{-- ================= FAQ ================= --}}
<section class="lp-section lp-workflow" id="faq">
  <div class="lp-section-head" data-reveal>
    <div class="lp-eyebrow">Questions</div>
    <h2>Good to know</h2>
  </div>
  <div class="lp-faq" data-reveal x-data="{ open: 0 }">
    <div class="lp-faq-item">
      <div class="lp-faq-q" @click="open = open === 1 ? null : 1">
        Who can create an account?
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open === 1 && 'transform:rotate(180deg)'"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="lp-faq-a" :style="open === 1 && 'max-height:200px'"><div class="lp-faq-a-inner">Anyone can sign up at <code>/register</code>. New accounts start as a Team Member — an ICT Director can promote you from Roles &amp; Access afterwards.</div></div>
    </div>
    <div class="lp-faq-item">
      <div class="lp-faq-q" @click="open = open === 2 ? null : 2">
        How is my data kept separate from other teams?
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open === 2 && 'transform:rotate(180deg)'"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="lp-faq-a" :style="open === 2 && 'max-height:200px'"><div class="lp-faq-a-inner">Projects belong to a team, and your role determines what you can see and edit. Team Members see what's assigned to them; Team Leaders see their team's projects; Directors see everything.</div></div>
    </div>
    <div class="lp-faq-item">
      <div class="lp-faq-q" @click="open = open === 3 ? null : 3">
        Can I change my role later?
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open === 3 && 'transform:rotate(180deg)'"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="lp-faq-a" :style="open === 3 && 'max-height:200px'"><div class="lp-faq-a-inner">Yes — an ICT Director or System Administrator can update anyone's role from the Roles &amp; Access screen at any time.</div></div>
    </div>
    <div class="lp-faq-item">
      <div class="lp-faq-q" @click="open = open === 4 ? null : 4">
        Is every action logged?
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :style="open === 4 && 'transform:rotate(180deg)'"><path d="M6 9l6 6 6-6"/></svg>
      </div>
      <div class="lp-faq-a" :style="open === 4 && 'max-height:200px'"><div class="lp-faq-a-inner">Status changes, approvals, and key edits are written to the audit log automatically, with who made the change and when.</div></div>
    </div>
  </div>
</section>

{{-- ================= CTA ================= --}}
<section class="lp-section" id="about">
  <div class="lp-cta-banner" data-reveal>
    <h2>Ready to bring order to your ICT projects?</h2>
    <p>Create your account — new sign-ups start as a Team Member and can be promoted from Roles &amp; Access.</p>
    <a href="{{ route('register') }}" class="btn btn-accent">Get started free</a>
  </div>
</section>

<footer class="lp-footer">
  <div class="lp-footer-top">
    <div class="lp-footer-brand">
      <div class="mark" style="width:28px;height:28px;border-radius:7px;background:linear-gradient(155deg,var(--accent),var(--accent-dark));display:flex;align-items:center;justify-content:center;font-family:'Space Grotesk';font-weight:700;color:#1B1200;font-size:11px;">JU</div>
      <div class="name">ICT PMS · Jimma University</div>
    </div>
    <div class="lp-footer-links">
      <a href="#features">Features</a>
      <a href="#workflow">Workflow</a>
      <a href="#roles">Roles</a>
      <a href="#faq">FAQ</a>
      <a href="{{ route('login') }}">Sign in</a>
      <a href="{{ route('register') }}">Sign up</a>
    </div>
  </div>
  <div class="lp-footer-bottom">
    © {{ date('Y') }} Jimma University ICT Directorate. Built in-house for the ICT Project Management System.
  </div>
</footer>

<script>
  // Nav condenses on scroll
  const lpNav = document.getElementById('lpNav');
  window.addEventListener('scroll', () => {
    lpNav.classList.toggle('scrolled', window.scrollY > 30);
  }, { passive: true });

  // Scroll-reveal for [data-reveal] and [data-reveal-group]
  const revealTargets = document.querySelectorAll('[data-reveal], [data-reveal-group]');
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });
  revealTargets.forEach(el => revealObserver.observe(el));

  // Count-up numbers
  const counters = document.querySelectorAll('[data-count]');
  const countObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      const target = parseInt(el.dataset.count, 10) || 0;
      const duration = 900;
      const start = performance.now();
      function tick(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(eased * target);
        if (progress < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
      countObserver.unobserve(el);
    });
  }, { threshold: 0.4 });
  counters.forEach(el => countObserver.observe(el));
</script>

@endsection
