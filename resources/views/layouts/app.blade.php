<!doctype html>
<html lang="en">

<head>

    <meta charset="UTF-8" />

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    />

    {{-- Required by the task panel AJAX requests --}}
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    />

    <title>
        @yield('title', 'Dashboard') · PMS — Project Management System
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap"
        rel="stylesheet"
    >

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <script
        src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"
        defer
    ></script>

</head>

<body>

    <div class="app">

        @include('partials.sidebar')

        <div class="main">

            @include('partials.topbar')

            <div class="content">

                @include('partials.flash')

                @yield('content')

            </div>

        </div>

    </div>

    @include('partials.task-panel')

    {{-- =========================================================
         GLOBAL DELETE / DANGER CONFIRM MODAL
         Any form with `data-confirm` intercepts its submit and
         shows this modal. Optional attributes:
           data-confirm-title  — heading (default: "Are you sure?")
           data-confirm-text   — body text
           data-confirm-button — confirm button label
           data-cancel-button  — cancel button label
         ========================================================= --}}
    <div
        id="globalConfirmModal"
        class="gcm-overlay"
        style="display:none;"
        aria-hidden="true"
    >
        <div class="gcm" role="dialog" aria-modal="true">
            <div class="gcm-head">
                <div>
                    <h3 id="gcmTitle">Are you sure?</h3>
                </div>
                <button
                    type="button"
                    class="pms-modal-close"
                    id="gcmClose"
                    aria-label="Close"
                >✕</button>
            </div>

            <div class="gcm-body" id="gcmText">
                This action cannot be undone.
            </div>

            <div class="gcm-foot">
                <button type="button" class="btn" id="gcmCancel">Cancel</button>
                <button type="submit" class="btn btn-accent gcm-danger" id="gcmConfirm">
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <style>
        .gcm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 95;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: gcm-fade 0.15s ease-out;
        }

        @keyframes gcm-fade {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .gcm {
            width: min(420px, 100%);
            background: var(--surface, #fff);
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.3);
            animation: gcm-in 0.18s ease-out;
            overflow: hidden;
        }

        @keyframes gcm-in {
            from { transform: translateY(12px) scale(0.98); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }

        .gcm-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 20px;
            border-bottom: 1px solid var(--line, #e2e8f0);
        }

        .gcm-head h3 {
            margin: 0;
            font-size: 16px;
        }

        .gcm-body {
            padding: 20px;
            font-size: 14px;
            line-height: 1.55;
            color: var(--text, #0f172a);
        }

        .gcm-foot {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 14px 20px;
            border-top: 1px solid var(--line, #e2e8f0);
        }

        .gcm-danger {
            background: var(--danger, #dc2626) !important;
            border-color: var(--danger, #dc2626) !important;
            color: #fff !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('globalConfirmModal');
            var pendingForm = null;

            function openModal(form) {
                pendingForm = form;

                var title = form.getAttribute('data-confirm-title') || 'Are you sure?';
                var text = form.getAttribute('data-confirm-text')
                    || 'This action cannot be undone.';
                var confirmLabel = form.getAttribute('data-confirm-button') || 'Confirm';
                var cancelLabel = form.getAttribute('data-cancel-button') || 'Cancel';

                document.getElementById('gcmTitle').textContent = title;
                document.getElementById('gcmText').textContent = text;
                document.getElementById('gcmConfirm').textContent = confirmLabel;
                document.getElementById('gcmCancel').textContent = cancelLabel;

                modal.style.display = 'flex';
                modal.setAttribute('aria-hidden', 'false');
            }

            function closeModal() {
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                pendingForm = null;
            }

            document.addEventListener('submit', function (e) {
                var form = e.target;
                if (form.matches('form[data-confirm]') && ! form.dataset.confirmed) {
                    e.preventDefault();
                    openModal(form);
                }
            }, true);

            document.getElementById('gcmClose').addEventListener('click', closeModal);
            document.getElementById('gcmCancel').addEventListener('click', closeModal);

            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal.style.display !== 'none') {
                    closeModal();
                }
            });

            document.getElementById('gcmConfirm').addEventListener('click', function () {
                if (pendingForm) {
                    var form = pendingForm;
                    closeModal();
                    form.dataset.confirmed = '1';
                    form.submit();
                }
            });
        });
    </script>

</body>

</html>