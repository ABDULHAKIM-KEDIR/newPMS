@extends('layouts.guest')

@section('title', 'Awaiting approval')

@section('content')

  <style>
    .guest-card {
      width: 480px;
      max-width: 100%;
      padding: 26px 28px;
    }

    .pending-logo {
      width: 72px;
      height: 72px;
      margin: 0 auto 12px;
      border-radius: 12px;
      background: #fff;
      border: 1px solid var(--line);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 14px rgba(0, 103, 184, .10);
      overflow: hidden;
    }

    .pending-logo img {
      width: 62px;
      height: 62px;
      object-fit: contain;
      display: block;
    }

    .pending-alert {
      display: flex;
      gap: 12px;
      align-items: flex-start;
      padding: 11px 14px;
      border-radius: 10px;
      margin-bottom: 16px;
      background: var(--primary-soft);
      border: 1px solid #B7D6EC;
    }

    .pending-alert .icon {
      font-size: 18px;
      line-height: 1.2;
    }

    .pending-alert h2 {
      font-family: 'Space Grotesk';
      font-size: 14.5px;
      font-weight: 600;
      margin: 0 0 4px;
      color: var(--primary-dark);
    }

    .pending-alert p {
      margin: 0;
      font-size: 12px;
      color: var(--ink-soft);
      line-height: 1.55;
    }

    .pending-alert b {
      color: var(--ink);
    }

    .pending-info {
      margin-bottom: 16px;
    }

    .pending-info h3 {
      font-family: 'Space Grotesk';
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--ink-faint);
      margin: 0 0 10px;
    }

    .pending-info ul {
      list-style: none;
      margin: 0;
      padding: 0;
    }

    .pending-info li {
      display: flex;
      gap: 10px;
      font-size: 12px;
      color: var(--ink-soft);
      line-height: 1.55;
      padding: 4px 0;
    }

    .pending-info li::before {
      content: '›';
      color: var(--primary);
      font-weight: 600;
    }

    .pending-faq {
      border-top: 1px solid var(--line);
      padding-top: 12px;
      margin-bottom: 16px;
    }

    .pending-faq details {
      margin-bottom: 6px;
    }

    .pending-faq summary {
      cursor: pointer;
      font-size: 12.5px;
      font-weight: 600;
      color: var(--ink);
      padding: 4px 0;
    }

    .pending-faq details p {
      margin: 4px 0 0;
      font-size: 12px;
      color: var(--ink-soft);
      line-height: 1.6;
    }

    .pending-contact {
      padding: 11px 13px;
      background: var(--surface-alt);
      border-radius: 9px;
      font-size: 11.8px;
      color: var(--ink-soft);
      line-height: 1.6;
      margin-bottom: 14px;
    }

    .pending-contact b {
      color: var(--ink);
      font-family: 'IBM Plex Mono';
      font-weight: 600;
    }
  </style>
  <div class="pending-logo">
    <img src="{{ asset('images/logo.png') }}" alt="Project Management System">
  </div>

  <div style="
              text-align:center;
              margin-bottom:20px;
          ">
    <div style="
                  font-family:'Space Grotesk';
                  font-weight:700;
                  font-size:16px;
                  color:var(--primary);
                  letter-spacing:-.01em;
              ">
      PMS
    </div>

    <div style="
                  font-size:11.5px;
                  color:var(--ink-soft);
                  margin-top:2px;
              ">
      Project Management System
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
      <li>The Project Management System helps Project Management System plan, track and deliver projects across
        departments.</li>
      <li>Approved users can manage projects, phases, tasks, teams and budgets, and follow progress in real time.</li>
      <li>Access to each module depends on the role assigned to you after approval.</li>
    </ul>
  </div>

  <div class="pending-faq">
    <h3
      style="font-family:'Space Grotesk';font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-faint);margin:0 0 10px;">
      Frequently Asked Questions</h3>
    <details>
      <summary>How long does approval take?</summary>
      <p>Registrations are typically reviewed within one business day. You will be able to sign in to your full account
        immediately after an administrator approves it.</p>
    </details>
    <details>
      <summary>Do I need to register again?</summary>
      <p>No. Your account already exists. Once approved, simply log in with the email address and password you used here.
      </p>
    </details>
    <details>
      <summary>What role will I get?</summary>
      <p>An administrator assigns your role based on your department and responsibilities — for example Team Member, Team
        Lead, Project Manager, or Director.</p>
    </details>
  </div>

  <div class="pending-contact">
    Need help? Contact your support team at
    <b>support@example.com</b> or extension <b>1234</b>.
  </div>

  <form method="POST" action="{{ route('logout') }}">
    @csrf

    <button type="submit" class="btn btn-ghost" style="
                  width:100%;
                  justify-content:center;
              ">
      Log out
    </button>
  </form>

@endsection