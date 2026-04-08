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

console.log('This log comes from assets/app.js - welcome to AssetMapper!');

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
                    event.preventDefault();
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
