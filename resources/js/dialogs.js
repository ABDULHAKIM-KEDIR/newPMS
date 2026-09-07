import Swal from 'sweetalert2';

/**
 * Shared dark-theme defaults for every dialog in the app.
 * Matches the PMS dark UI (var(--bg), var(--card), var(--line), etc.)
 */
const baseOptions = {
    background: '#F8FAFC',
    color: '#475569',
    customClass: {
        popup: 'swal2-pms',
        title: 'swal2-pms-title',
        htmlContainer: 'swal2-pms-text',
        confirmButton: 'swal2-pms-confirm',
        cancelButton: 'swal2-pms-cancel',
    },
    buttonsStyling: false,
    reverseButtons: true,
};

/**
 * A confirmation dialog. Resolves true only when confirmed.
 * Pass `danger: true` for destructive actions (delete) to use the
 * vivid red confirm button instead of the primary blue.
 */
export async function confirmDialog({
    title = 'Are you sure?',
    text = '',
    icon = 'warning',
    confirmText = 'Yes, continue',
    cancelText = 'Cancel',
    danger = false,
} = {}) {
    const result = await Swal.fire({
        ...baseOptions,
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        customClass: {
            ...baseOptions.customClass,
            confirmButton: danger
                ? 'swal2-pms-confirm swal2-pms-danger'
                : baseOptions.customClass.confirmButton,
        },
    });

    return result.isConfirmed;
}

/** A themed replacement for native alert(). */
export function alertDialog({ title = '', text = '', icon = 'error' } = {}) {
    return Swal.fire({
        ...baseOptions,
        title,
        text,
        icon,
        confirmButtonText: 'OK',
    });
}

export { Swal };
