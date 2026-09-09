import './bootstrap';

// Alpine.js is loaded via CDN in resources/views/layouts/app.blade.php.
// This file is the Vite entry point for any future app-specific JS/CSS imports.

// SweetAlert2 dialogs (dark-theme helper), exposed globally for Blade
// inline handlers and Alpine components.
import { confirmDialog, alertDialog, Swal } from './dialogs';

window.confirmDialog = confirmDialog;
window.alertDialog = alertDialog;
window.Swal = Swal;

/**
 * Delegated confirmation handler for forms marked with `data-confirm`.
 * Replaces the old inline `onsubmit="return confirm(...)"` pattern —
 * the SweetAlert2 dialog is shown first and the form is only
 * programmatically submitted when the user confirms.
 */
document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');

    if (!form || form.dataset.confirmed === '1') {
        return;
    }

    event.preventDefault();

    confirmDialog({
        title: form.dataset.confirmTitle || 'Are you sure?',
        text: form.dataset.confirmText || '',
        icon: form.dataset.confirmIcon || 'warning',
        confirmText: form.dataset.confirmButton || 'Yes, continue',
        cancelText: form.dataset.cancelButton || 'Cancel',
    }).then((confirmed) => {
        if (!confirmed) {
            return;
        }

        form.dataset.confirmed = '1';
        form.submit();
    });
});
