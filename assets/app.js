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
import './styles/user/evenements.css';
import './styles/admin/evenements.css';
import './styles/footer.css';
import './styles/static-pages.css';


import './marketplace.js';
import './validation.js';

console.log('This log comes from assets/app.js - welcome to AssetMapper!');

const FIRMA_ICONS = {
    danger: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><circle cx="12" cy="16" r=".5" fill="currentColor" stroke="none"/></svg>',
    warning: '<svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><circle cx="12" cy="17" r=".5" fill="currentColor" stroke="none"/></svg>',
    success: '<svg viewBox="0 0 24 24"><path d="M3.85 8.62a4 4 0 0 1 4.78-2.65A6 6 0 0 1 20.56 10 4 4 0 0 1 18 17H7a5 5 0 0 1-3.15-8.38z"/><polyline points="10 14 12 16 16 12"/></svg>',
    info: '<svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 0 1 5 5c0 2.76-2.5 4.5-3.5 5.5-.42.42-.5.72-.5 1.5h-2c0-1.22.28-1.78 1-2.5C13 10.5 15 9.2 15 7a3 3 0 0 0-6 0H7a5 5 0 0 1 5-5z"/><circle cx="12" cy="19" r="1" fill="currentColor" stroke="none"/></svg>',
    delete: '<svg viewBox="0 0 24 24"><path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><line x1="18" y1="9" x2="12" y2="15"/><line x1="12" y1="9" x2="18" y2="15"/></svg>',
};

function getToastContainer() {
    let container = document.getElementById('firmaToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'firmaToastContainer';
        container.className = 'firma-toast-container';
        document.body.appendChild(container);
    }

    return container;
}

/**
 * Show a toast notification at top of page.
 * @param {string} message
 * @param {'success'|'danger'|'warning'|'info'} type
 * @param {number} duration  ms (default 10000)
 */
function firmaToast(message, type = 'success', duration = 10000) {
    const normalizedType = type === 'error' ? 'danger' : type;
    const container = getToastContainer();
    const toast = document.createElement('div');
    toast.className = `firma-toast firma-toast-${normalizedType}`;
    toast.style.position = 'relative';
    toast.innerHTML =
        `<div class="firma-toast-icon">${FIRMA_ICONS[normalizedType] || FIRMA_ICONS.info}</div>` +
        `<div class="firma-toast-text">${message}</div>` +
        '<button class="firma-toast-close"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>' +
        `<div class="firma-toast-progress" style="animation-duration:${duration}ms"></div>`;

    container.appendChild(toast);

    const dismiss = () => {
        toast.classList.add('firma-toast-out');
        window.setTimeout(() => toast.remove(), 300);
    };

    const closeBtn = toast.querySelector('.firma-toast-close');
    if (closeBtn instanceof HTMLButtonElement) {
        closeBtn.addEventListener('click', dismiss);
    }
    window.setTimeout(dismiss, duration);
}

function firmaConfirm(title, message, confirmText = 'Supprimer', iconType = 'danger') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'firma-overlay';
        const modal = document.createElement('div');
        modal.className = 'firma-modal';
        const icon = FIRMA_ICONS[iconType === 'danger' ? 'delete' : iconType] || FIRMA_ICONS.danger;
        modal.innerHTML =
            `<div class="firma-modal-icon firma-icon-${iconType}">${icon}</div>` +
            `<div class="firma-modal-title">${title}</div>` +
            `<div class="firma-modal-msg">${message}</div>` +
            '<div class="firma-modal-btns">' +
            '<button class="firma-modal-btn firma-btn-cancel">Annuler</button>' +
            `<button class="firma-modal-btn firma-btn-danger">${confirmText}</button>` +
            '</div>';

        document.body.appendChild(overlay);
        document.body.appendChild(modal);

        requestAnimationFrame(() => {
            overlay.classList.add('firma-show');
            modal.classList.add('firma-show');
        });

        const close = (result) => {
            overlay.classList.remove('firma-show');
            modal.classList.remove('firma-show');
            window.setTimeout(() => {
                overlay.remove();
                modal.remove();
            }, 300);
            resolve(result);
        };

        modal.querySelector('.firma-btn-cancel')?.addEventListener('click', () => close(false));
        modal.querySelector('.firma-btn-danger')?.addEventListener('click', () => close(true));
        overlay.addEventListener('click', () => close(false));
    });
}

function firmaAlert(title, message, iconType = 'info', btnText = 'OK') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'firma-overlay';
        const modal = document.createElement('div');
        modal.className = 'firma-modal';
        modal.innerHTML =
            `<div class="firma-modal-icon firma-icon-${iconType}">${FIRMA_ICONS[iconType] || FIRMA_ICONS.info}</div>` +
            `<div class="firma-modal-title">${title}</div>` +
            `<div class="firma-modal-msg">${message}</div>` +
            '<div class="firma-modal-btns"><button class="firma-modal-btn firma-btn-primary">' + btnText + '</button></div>';

        document.body.appendChild(overlay);
        document.body.appendChild(modal);

        requestAnimationFrame(() => {
            overlay.classList.add('firma-show');
            modal.classList.add('firma-show');
        });

        const close = () => {
            overlay.classList.remove('firma-show');
            modal.classList.remove('firma-show');
            window.setTimeout(() => {
                overlay.remove();
                modal.remove();
            }, 300);
            resolve();
        };

        modal.querySelector('.firma-btn-primary')?.addEventListener('click', close);
        overlay.addEventListener('click', close);
    });
}

window.firmaToast = firmaToast;
window.firmaConfirm = firmaConfirm;
window.firmaAlert = firmaAlert;

const initAppUi = () => {
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');
    if (navToggle && navLinks && !navToggle.dataset.bound) {
        navToggle.dataset.bound = 'true';
        navToggle.addEventListener('click', () => navLinks.classList.toggle('open'));
    }

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    if (sidebarToggle && sidebar && !sidebarToggle.dataset.bound) {
        sidebarToggle.dataset.bound = 'true';
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    }

    document.querySelectorAll('form[data-firma-confirm]').forEach((form) => {
        if (form.dataset.firmaConfirmBound) {
            return;
        }

        form.dataset.firmaConfirmBound = 'true';
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const msg = form.dataset.firmaConfirm || 'Supprimer cet element ?';
            firmaConfirm('Confirmer la suppression', msg, 'Supprimer', 'danger').then((ok) => {
                if (ok) {
                    form.submit();
                }
            });
        });
    });

    const confirmModal = document.getElementById('appConfirmModal');
    const confirmTitle = document.getElementById('appConfirmTitle');
    const confirmMessage = document.getElementById('appConfirmMessage');
    const confirmSubmit = document.getElementById('appConfirmSubmit');
    const confirmCancelButtons = document.querySelectorAll('[data-confirm-cancel]');
    let pendingForm = null;

    const closeConfirmModal = () => {
        if (!confirmModal) {
            return;
        }

        confirmModal.hidden = true;
        pendingForm = null;
    };

    if (confirmModal && confirmTitle && confirmMessage && confirmSubmit && !confirmModal.dataset.bound) {
        confirmModal.dataset.bound = 'true';

        document.querySelectorAll('[data-confirm-trigger]').forEach((button) => {
            if (button.dataset.confirmBound) {
                return;
            }

            button.dataset.confirmBound = 'true';
            button.addEventListener('click', () => {
                const form = button.closest('form[data-confirm-form]');
                if (!form) {
                    return;
                }

                pendingForm = form;
                confirmTitle.textContent = form.dataset.confirmTitle || 'Confirmation';
                confirmMessage.textContent = form.dataset.confirmMessage || 'Voulez-vous confirmer cette action ?';
                confirmModal.hidden = false;
            });
        });

        confirmCancelButtons.forEach((button) => {
            button.addEventListener('click', closeConfirmModal);
        });

        confirmSubmit.addEventListener('click', () => {
            if (pendingForm) {
                const formToSubmit = pendingForm;
                closeConfirmModal();
                formToSubmit.submit();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !confirmModal.hidden) {
                closeConfirmModal();
            }
        });
    }

    document.querySelectorAll('[data-image-preview-input]').forEach((input) => {
        if (input.dataset.previewBound) {
            return;
        }

        input.dataset.previewBound = 'true';
        input.addEventListener('change', () => {
            const previewId = input.getAttribute('data-image-preview-input');
            const allowedExtensions = (input.getAttribute('data-image-allowed-extensions') || '')
                .split(',')
                .map((value) => value.trim().toLowerCase())
                .filter(Boolean);
            const minWidth = Number.parseInt(input.getAttribute('data-image-min-width') || '0', 10);
            const minHeight = Number.parseInt(input.getAttribute('data-image-min-height') || '0', 10);
            const maxWidth = Number.parseInt(input.getAttribute('data-image-max-width') || '0', 10);
            const maxHeight = Number.parseInt(input.getAttribute('data-image-max-height') || '0', 10);
            const maxBytes = Number.parseInt(input.getAttribute('data-image-max-bytes') || '0', 10);
            const preview = previewId ? document.getElementById(previewId) : null;
            const image = preview ? preview.querySelector('img') : null;
            const errorBox = input.parentElement ? input.parentElement.querySelector('[data-image-format-error]') : null;
            const sizeErrorBox = input.parentElement ? input.parentElement.querySelector('[data-image-size-error]') : null;
            const dimensionErrorBox = input.parentElement ? input.parentElement.querySelector('[data-image-dimension-error]') : null;
            const file = input.files && input.files[0] ? input.files[0] : null;

            if (!preview || !image) {
                return;
            }

            if (errorBox) {
                errorBox.hidden = true;
            }
            if (sizeErrorBox) {
                sizeErrorBox.hidden = true;
            }
            if (dimensionErrorBox) {
                dimensionErrorBox.hidden = true;
            }

            if (!file || !file.type.startsWith('image/')) {
                preview.hidden = true;
                image.setAttribute('src', '');
                if (file && errorBox) {
                    errorBox.hidden = false;
                }
                input.value = '';
                return;
            }

            const fileNameParts = file.name.toLowerCase().split('.');
            const extension = fileNameParts.length > 1 ? fileNameParts.pop() : '';
            if (!extension || (allowedExtensions.length > 0 && !allowedExtensions.includes(extension))) {
                preview.hidden = true;
                image.setAttribute('src', '');
                if (errorBox) {
                    errorBox.hidden = false;
                }
                input.value = '';
                return;
            }

            if (maxBytes > 0 && file.size > maxBytes) {
                preview.hidden = true;
                image.setAttribute('src', '');
                if (sizeErrorBox) {
                    const fileSizeMb = (file.size / (1024 * 1024)).toFixed(2);
                    const maxSizeMb = (maxBytes / (1024 * 1024)).toFixed(0);
                    sizeErrorBox.textContent = `Image trop lourde : ${fileSizeMb} Mo. Maximum autorise : ${maxSizeMb} Mo.`;
                    sizeErrorBox.hidden = false;
                }
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.addEventListener('load', () => {
                const result = typeof reader.result === 'string' ? reader.result : '';
                const probeImage = new Image();

                probeImage.addEventListener('load', () => {
                    const invalidMin = (minWidth > 0 && probeImage.width < minWidth) || (minHeight > 0 && probeImage.height < minHeight);
                    const invalidMax = (maxWidth > 0 && probeImage.width > maxWidth) || (maxHeight > 0 && probeImage.height > maxHeight);

                    if (invalidMin || invalidMax) {
                        preview.hidden = true;
                        image.setAttribute('src', '');
                        if (dimensionErrorBox) {
                            if (invalidMin) {
                                dimensionErrorBox.textContent = `Image trop petite : ${probeImage.width}x${probeImage.height} px. Minimum requis : ${minWidth}x${minHeight} px.`;
                            } else {
                                dimensionErrorBox.textContent = `Image trop grande : ${probeImage.width}x${probeImage.height} px. Maximum autorise : ${maxWidth}x${maxHeight} px.`;
                            }
                            dimensionErrorBox.hidden = false;
                        }
                        input.value = '';
                        return;
                    }

                    image.setAttribute('src', result);
                    preview.hidden = false;
                });

                probeImage.src = result;
            });
            reader.readAsDataURL(file);
        });
    });

    document.querySelectorAll('[data-comment-input]').forEach((input) => {
        if (input.dataset.commentBound) {
            return;
        }

        const minLength = Number.parseInt(input.getAttribute('data-comment-min-length') || '0', 10);
        const maxLength = Number.parseInt(input.getAttribute('data-comment-max-length') || '1000', 10);
        const errorId = input.getAttribute('data-comment-error-id');
        const counterId = input.getAttribute('data-comment-counter-id');
        const errorBox = errorId ? document.getElementById(errorId) : null;
        const counterBox = counterId ? document.getElementById(counterId) : null;

        const validateComment = () => {
            const rawValue = input.value || '';
            const trimmedValue = rawValue.trim();
            const length = rawValue.length;
            let message = '';

            if (counterBox) {
                counterBox.textContent = `${length} / ${maxLength} caracteres`;
            }

            if (trimmedValue === '') {
                message = 'Le commentaire est obligatoire.';
            } else if (trimmedValue.length < minLength) {
                message = `Le commentaire doit contenir au moins ${minLength} caracteres.`;
            } else if (length > maxLength) {
                message = `Le commentaire ne doit pas depasser ${maxLength} caracteres.`;
            }

            if (errorBox) {
                errorBox.textContent = message;
                errorBox.hidden = message === '';
            }

            return message === '';
        };

        input.dataset.commentBound = 'true';
        input.addEventListener('input', validateComment);
        const form = input.closest('form');
        if (form && !form.dataset.commentValidationBound) {
            form.dataset.commentValidationBound = 'true';
            form.addEventListener('submit', (event) => {
                const isTextValid = validateComment();
                if (!isTextValid) {
                    input.focus();
                }
            });
        }

        validateComment();
    });

    document.querySelectorAll('form[data-auto-search="true"]').forEach((form) => {
        if (form.dataset.autoSearchBound) {
            return;
        }

        const input = form.querySelector('input[data-auto-search-input="true"]');
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        form.dataset.autoSearchBound = 'true';
        const minLength = Number.parseInt(input.getAttribute('minlength') || '3', 10);
        const initialValue = (input.value || '').trim();
        let lastSubmitted = initialValue;
        let debounceTimer = null;

        input.addEventListener('input', () => {
            const value = (input.value || '').trim();

            if (debounceTimer) {
                window.clearTimeout(debounceTimer);
            }

            debounceTimer = window.setTimeout(() => {
                const hasEnoughChars = value.length >= minLength;
                const isReset = value.length === 0;

                if ((!hasEnoughChars && !isReset) || value === lastSubmitted) {
                    return;
                }

                lastSubmitted = value;
                form.submit();
            }, 280);
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAppUi);
} else {
    initAppUi();
}
