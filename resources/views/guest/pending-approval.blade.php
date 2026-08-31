<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Awaiting Approval · ICT PMS — Jimma University</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
  /* Undo app.css viewport lock (height:100vh + overflow:hidden) so this
     standalone page can scroll on short windowed viewports. */
  html, body {
    height: auto !important;
    max-height: none !important;
    overflow-y: auto !important;
  }
  body.guest-body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px 16px;
    box-sizing: border-box;
    background:
      radial-gradient(circle at 15% 10%, rgba(201,134,44,.10), transparent 45%),
      radial-gradient(circle at 85% 90%, rgba(31,75,75,.14), transparent 45%),
      var(--bg);
  }
  .pending-card {
    width: 520px; max-width: 100%;
    padding: 26px 24px;
    margin: auto 0; /* centered when short, scrollable top-down when tall */
  }
  @media (max-width: 480px) {
    .pending-card { padding: 20px 16px; }
  }
  .pending-brand { display:flex; align-items:center; gap:10px; margin-bottom:18px; }
  .pending-brand .brand-mark { width:36px; height:36px; border-radius:9px; background:linear-gradient(155deg,var(--accent),var(--accent-dark)); display:flex; align-items:center; justify-content:center; font-family:'Space Grotesk'; font-weight:700; color:#1B1200; }
  .pending-brand .t1 { font-family:'Space Grotesk'; font-weight:600; font-size:16px; }
  .pending-brand .t2 { font-size:12px; color:var(--ink-soft); }
  .pending-alert {
    display:flex; gap:12px; align-items:flex-start;
    padding:11px 14px; border-radius:10px; margin-bottom:16px;
    background:rgba(201,134,44,.10); border:1px solid rgba(201,134,44,.35);
  }
  .pending-alert .icon { font-size:18px; line-height:1.2; }
  .pending-alert h2 { font-family:'Space Grotesk'; font-size:14.5px; font-weight:600; margin:0 0 4px; color:var(--ink); }
  .pending-alert p { margin:0; font-size:12px; color:var(--ink-soft); line-height:1.55; }
  .pending-info { margin-bottom:16px; }
  .pending-info h3 { font-family:'Space Grotesk'; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-faint); margin:0 0 10px; }
  .pending-info ul { list-style:none; margin:0; padding:0; }
  .pending-info li { display:flex; gap:10px; font-size:12px; color:var(--ink-soft); line-height:1.55; padding:4px 0; }
  .pending-info li::before { content:'›'; color:var(--accent); font-weight:600; }
  .pending-faq { border-top:1px solid var(--line); padding-top:12px; margin-bottom:16px; }
  .pending-faq details { margin-bottom:6px; }
  .pending-faq summary { cursor:pointer; font-size:12.5px; font-weight:600; color:var(--ink); padding:4px 0; }
  .pending-faq details p { margin:4px 0 0; font-size:12px; color:var(--ink-soft); line-height:1.6; }
  .pending-contact {
    padding:11px 13px; background:var(--surface-alt); border-radius:9px;
    font-size:11.8px; color:var(--ink-soft); line-height:1.6; margin-bottom:14px;
  }
  .pending-contact b { color:var(--ink); font-family:'IBM Plex Mono'; font-weight:600; }
  .pending-logout { display:flex; justify-content:flex-end; }
  .btn-logout {
    display:inline-flex; align-items:center; gap:8px;
    border:1px solid var(--line); background:var(--surface); color:var(--ink);
    border-radius:8px; padding:9px 16px; font-size:12.5px; font-weight:600;
    font-family:inherit; cursor:pointer; transition:border-color .15s ease;
  }
  .btn-logout:hover { border-color:var(--danger); color:var(--danger); }
</style>
</head>
<body class="guest-body">
  <div class="card pending-card">

    <div class="pending-brand">
      <div class="brand-mark">JU</div>
      <div>
        <div class="t1">ICT PMS</div>
        <div class="t2">Jimma University · Project Management System</div>
      </div>
    </div>

    <div class="pending-alert">
      <div class="icon">⏳</div>
      <div>
        <h2>Your account is awaiting administrator review</h2>
        <p>
          Thanks for registering, <b>{{ $user->full_name }}</b>.
          Your account has been created as a <b>Guest</b> with a
          <b>pending</b> status. A System Administrator will review your
          registration and assign you an appropriate role. You will be able
          to access the full system once your account is approved.
        </p>
      </div>
    </div>

    <div class="pending-info">
      <h3>Program Overview</h3>
      <ul>
        <li>The ICT Project Management System helps Jimma University plan, track and deliver ICT projects across departments.</li>
        <li>Approved users can manage projects, phases, tasks, teams and budgets, and follow progress in real time.</li>
        <li>Access to each module depends on the role assigned to you after approval.</li>
      </ul>
    </div>

    <div class="pending-faq">
      <h3 style="font-family:'Space Grotesk';font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-faint);margin:0 0 10px;">Frequently Asked Questions</h3>
      <details>
        <summary>How long does approval take?</summary>
        <p>Registrations are typically reviewed within one business day. You will be able to sign in to your full account immediately after an administrator approves it.</p>
      </details>
      <details>
        <summary>Do I need to register again?</summary>
        <p>No. Your account already exists. Once approved, simply log in with the email address and password you used here.</p>
      </details>
      <details>
        <summary>What role will I get?</summary>
        <p>An administrator assigns your role based on your department and responsibilities — for example Team Member, Team Lead, Project Manager, or Director.</p>
      </details>
    </div>

    <div class="pending-contact">
      Need help? Contact the ICT support desk at
      <b>ict-support@ju.edu.et</b> or extension <b>1234</b>.
    </div>

    <div class="pending-logout">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-logout">Log out</button>
      </form>
    </div>

  </div>
</body>
</html>
