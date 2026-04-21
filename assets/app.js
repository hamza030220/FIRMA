import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import './styles/landing.css';
import './styles/login.css';
import './styles/user/dashboard.css';
import './styles/admin/dashboard.css';
import './styles/admin/marketplace.css';
import './styles/user/marketplace.css';
import './styles/footer.css';
import './styles/static-pages.css';


import './marketplace.js';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

/* ══════════════════════════════════════════════════════
   FIRMA — Global Custom Modal & Toast System
   ══════════════════════════════════════════════════════ */

/* ── SVG Icons (custom, FIRMA-themed) ── */
const FIRMA_ICONS = {
    danger: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><circle cx="12" cy="16" r=".5" fill="currentColor" stroke="none"/></svg>',
    warning: '<svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><circle cx="12" cy="17" r=".5" fill="currentColor" stroke="none"/></svg>',
    success: '<svg viewBox="0 0 24 24"><path d="M3.85 8.62a4 4 0 0 1 4.78-2.65A6 6 0 0 1 20.56 10 4 4 0 0 1 18 17H7a5 5 0 0 1-3.15-8.38z"/><polyline points="10 14 12 16 16 12"/></svg>',
    info: '<svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 0 1 5 5c0 2.76-2.5 4.5-3.5 5.5-.42.42-.5.72-.5 1.5h-2c0-1.22.28-1.78 1-2.5C13 10.5 15 9.2 15 7a3 3 0 0 0-6 0H7a5 5 0 0 1 5-5z"/><circle cx="12" cy="19" r="1" fill="currentColor" stroke="none"/></svg>',
    delete: '<svg viewBox="0 0 24 24"><path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><line x1="18" y1="9" x2="12" y2="15"/><line x1="12" y1="9" x2="18" y2="15"/></svg>',
    timer: '<svg viewBox="0 0 24 24"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3L2 6"/><path d="M22 6l-3-3"/><path d="M9 1h6"/></svg>',
};

/* ── Toast container (auto-created) ── */
function getToastContainer() {
    let c = document.getElementById('firmaToastContainer');
    if (!c) {
        c = document.createElement('div');
        c.id = 'firmaToastContainer';
        c.className = 'firma-toast-container';
        document.body.appendChild(c);
    }
    return c;
}

/**
 * Show a toast notification at top of page.
 * @param {string} message
 * @param {'success'|'danger'|'warning'|'info'} type
 * @param {number} duration  ms (default 10000)
 */
function firmaToast(message, type = 'success', duration = 10000) {
    const container = getToastContainer();
    const toast = document.createElement('div');
    toast.className = 'firma-toast firma-toast-' + type;
    toast.style.position = 'relative';
    const iconKey = type;
    toast.innerHTML =
        '<div class="firma-toast-icon">' + (FIRMA_ICONS[iconKey] || FIRMA_ICONS.info) + '</div>' +
        '<div class="firma-toast-text">' + message + '</div>' +
        '<button class="firma-toast-close"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>' +
        '<div class="firma-toast-progress" style="animation-duration:' + duration + 'ms"></div>';

    container.appendChild(toast);

    const closeBtn = toast.querySelector('.firma-toast-close');
    function dismiss() {
        toast.classList.add('firma-toast-out');
        setTimeout(() => toast.remove(), 300);
    }
    closeBtn.addEventListener('click', dismiss);
    setTimeout(dismiss, duration);
}

/**
 * Show a confirm modal (Annuler + Supprimer).
 * Returns a Promise<boolean>.
 * @param {string} title
 * @param {string} message
 * @param {string} confirmText
 * @param {'danger'|'warning'} iconType
 */
function firmaConfirm(title, message, confirmText = 'Supprimer', iconType = 'danger') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'firma-overlay';
        const modal = document.createElement('div');
        modal.className = 'firma-modal';
        modal.innerHTML =
            '<div class="firma-modal-icon firma-icon-' + iconType + '">' + (FIRMA_ICONS[iconType === 'danger' ? 'delete' : iconType] || FIRMA_ICONS.danger) + '</div>' +
            '<div class="firma-modal-title">' + title + '</div>' +
            '<div class="firma-modal-msg">' + message + '</div>' +
            '<div class="firma-modal-btns">' +
                '<button class="firma-modal-btn firma-btn-cancel">Annuler</button>' +
                '<button class="firma-modal-btn firma-btn-danger">' + confirmText + '</button>' +
            '</div>';

        document.body.appendChild(overlay);
        document.body.appendChild(modal);

        requestAnimationFrame(() => {
            overlay.classList.add('firma-show');
            modal.classList.add('firma-show');
        });

        function close(result) {
            overlay.classList.remove('firma-show');
            modal.classList.remove('firma-show');
            setTimeout(() => { overlay.remove(); modal.remove(); }, 300);
            resolve(result);
        }

        modal.querySelector('.firma-btn-cancel').addEventListener('click', () => close(false));
        modal.querySelector('.firma-btn-danger').addEventListener('click', () => close(true));
        overlay.addEventListener('click', () => close(false));
    });
}

/**
 * Show an alert modal with a single OK button.
 * Returns a Promise that resolves when dismissed.
 * @param {string} title
 * @param {string} message
 * @param {'info'|'warning'|'success'|'danger'} iconType
 * @param {string} btnText
 */
function firmaAlert(title, message, iconType = 'info', btnText = 'OK') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'firma-overlay';
        const modal = document.createElement('div');
        modal.className = 'firma-modal';
        const iconSvg = FIRMA_ICONS[iconType] || FIRMA_ICONS.info;
        modal.innerHTML =
            '<div class="firma-modal-icon firma-icon-' + iconType + '">' + iconSvg + '</div>' +
            '<div class="firma-modal-title">' + title + '</div>' +
            '<div class="firma-modal-msg">' + message + '</div>' +
            '<div class="firma-modal-btns">' +
                '<button class="firma-modal-btn firma-btn-primary">' + btnText + '</button>' +
            '</div>';

        document.body.appendChild(overlay);
        document.body.appendChild(modal);

        requestAnimationFrame(() => {
            overlay.classList.add('firma-show');
            modal.classList.add('firma-show');
        });

        function close() {
            overlay.classList.remove('firma-show');
            modal.classList.remove('firma-show');
            setTimeout(() => { overlay.remove(); modal.remove(); }, 300);
            resolve();
        }

        modal.querySelector('.firma-btn-primary').addEventListener('click', close);
    });
}

/* ── Expose globally (used by marketplace.js via window.*) ── */
window.firmaToast = firmaToast;
window.firmaConfirm = firmaConfirm;
window.firmaAlert = firmaAlert;


/* ── Auto-bind all delete forms (admin confirm) ── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-firma-confirm]').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const msg = form.dataset.firmaConfirm || 'Supprimer cet élément ?';
            const title = form.dataset.firmaConfirmTitle || 'Confirmer la suppression';
            const btnText = form.dataset.firmaConfirmBtn || 'Supprimer';
            const iconType = form.dataset.firmaConfirmIcon || 'danger';
            firmaConfirm(title, msg, btnText, iconType).then(ok => {
                if (ok) form.submit();
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    // User navbar toggle
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    if (navToggle && navLinks) {
        navToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
    }

    // Admin sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('photo');
    const previewWrapper = document.getElementById('photoPreviewWrapper');
    const previewImage = document.getElementById('photoPreview');

    if (!input || !previewWrapper || !previewImage) {
        return;
    }

    let currentUrl = null;

    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (currentUrl) {
            URL.revokeObjectURL(currentUrl);
            currentUrl = null;
        }

        if (!file) {
            previewWrapper.classList.remove('is-visible');
            previewWrapper.setAttribute('aria-hidden', 'true');
            previewImage.removeAttribute('src');
            return;
        }

        if (!file.type.startsWith('image/')) {
            previewWrapper.classList.remove('is-visible');
            previewWrapper.setAttribute('aria-hidden', 'true');
            previewImage.removeAttribute('src');
            return;
        }

        currentUrl = URL.createObjectURL(file);
        previewImage.src = currentUrl;
        previewWrapper.classList.add('is-visible');
        previewWrapper.setAttribute('aria-hidden', 'false');
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('casePhotos');
    const previewWrapper = document.getElementById('casePhotosPreview');
    const previewGrid = document.getElementById('casePhotosPreviewGrid');

    if (!input || !previewWrapper || !previewGrid) {
        return;
    }

    let currentUrls = [];

    function clearPreviews() {
        currentUrls.forEach((url) => URL.revokeObjectURL(url));
        currentUrls = [];
        previewGrid.innerHTML = '';
    }

    input.addEventListener('change', () => {
        clearPreviews();
        const files = input.files ? Array.from(input.files) : [];

        if (files.length === 0) {
            previewWrapper.classList.remove('is-visible');
            previewWrapper.setAttribute('aria-hidden', 'true');
            return;
        }

        files.forEach((file) => {
            if (!file.type.startsWith('image/')) {
                return;
            }

            const url = URL.createObjectURL(file);
            currentUrls.push(url);
            const img = document.createElement('img');
            img.src = url;
            img.alt = 'Apercu photo';
            previewGrid.appendChild(img);
        });

        if (previewGrid.children.length > 0) {
            previewWrapper.classList.add('is-visible');
            previewWrapper.setAttribute('aria-hidden', 'false');
        } else {
            previewWrapper.classList.remove('is-visible');
            previewWrapper.setAttribute('aria-hidden', 'true');
        }
    });
});
