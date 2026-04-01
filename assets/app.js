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
 * @param {number} duration  ms (default 4000)
 */
function firmaToast(message, type = 'success', duration = 4000) {
    const container = getToastContainer();
    const toast = document.createElement('div');
    toast.className = 'firma-toast firma-toast-' + type;
    toast.style.position = 'relative';
    const iconKey = type;
    toast.innerHTML =
        '<div class="firma-toast-icon">' + (FIRMA_ICONS[iconKey] || FIRMA_ICONS.info) + '</div>' +
        '<span class="firma-toast-text">' + message + '</span>' +
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

/* ── Auto-bind all delete forms (admin confirm) ── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-firma-confirm]').forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const msg = form.dataset.firmaConfirm || 'Supprimer cet élément ?';
            firmaConfirm('Confirmer la suppression', msg, 'Supprimer', 'danger').then(ok => {
                if (ok) form.submit();
            });
        });
    });
});

/* ── Mobile navigation toggles ── */
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

    // Marketplace tab switching
    document.querySelectorAll('.mp-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;
            btn.closest('.mp-page').querySelectorAll('.mp-tab').forEach(t => t.classList.remove('active'));
            btn.closest('.mp-page').querySelectorAll('.mp-tab-pane').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            const pane = document.getElementById('tab-' + target);
            if (pane) pane.classList.add('active');
        });
    });

    // Flash alert close buttons
    document.querySelectorAll('.mp-alert-close').forEach(btn => {
        btn.addEventListener('click', () => btn.parentElement.remove());
    });

    /* ── Marketplace real-time search ── */
    document.querySelectorAll('.mp-search').forEach(input => {
        input.addEventListener('input', () => {
            mpCurrentPage[input.dataset.table] = 1;
            applyTableFilters(input.dataset.table);
        });
    });

    /* ── Marketplace column filters ── */
    document.querySelectorAll('.mp-filter').forEach(select => {
        select.addEventListener('change', () => {
            mpCurrentPage[select.dataset.table] = 1;
            applyTableFilters(select.dataset.table);
        });
    });

    /* ── Marketplace stock filter (equipements only) ── */
    document.querySelectorAll('.mp-filter-stock').forEach(select => {
        select.addEventListener('change', () => {
            mpCurrentPage[select.dataset.table] = 1;
            applyTableFilters(select.dataset.table);
        });
    });

    /* ── Initialize pagination on all marketplace tables ── */
    document.querySelectorAll('.mp-table[id^="table-"]').forEach(table => {
        const name = table.id.replace('table-', '');
        mpCurrentPage[name] = 1;
        applyTableFilters(name);
    });
});

const MP_PER_PAGE = 10;
const mpCurrentPage = {};

/**
 * Unified filter + pagination: search → column filters → stock filter → paginate
 */
function applyTableFilters(tableName) {
    const table = document.getElementById('table-' + tableName);
    if (!table) return;

    const searchInput = document.querySelector('.mp-search[data-table="' + tableName + '"]');
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

    // Gather all column filters for this table
    const colFilters = [];
    document.querySelectorAll('.mp-filter[data-table="' + tableName + '"]').forEach(sel => {
        colFilters.push({ col: parseInt(sel.dataset.col, 10), value: sel.value.toLowerCase().trim() });
    });

    // Stock filter (equipements only)
    const stockSelect = document.querySelector('.mp-filter-stock[data-table="' + tableName + '"]');
    const stockFilter = stockSelect ? stockSelect.value : '';

    const rows = table.querySelectorAll('tbody tr');
    const matchingRows = [];

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        let show = true;

        // Text search: check all cells
        if (query) {
            const rowText = Array.from(cells).map(c => c.textContent.toLowerCase()).join(' ');
            if (!rowText.includes(query)) show = false;
        }

        // Column filters
        if (show) {
            for (const f of colFilters) {
                if (!f.value) continue;
                const cell = cells[f.col];
                if (cell) {
                    const cellText = cell.textContent.toLowerCase().trim();
                    if (!cellText.includes(f.value)) { show = false; break; }
                }
            }
        }

        // Stock filter
        if (show && stockFilter) {
            const isLow = row.getAttribute('data-stock-low') === '1';
            if (stockFilter === 'low' && !isLow) show = false;
            if (stockFilter === 'ok' && isLow) show = false;
        }

        row.style.display = 'none'; // hide all first
        if (show) matchingRows.push(row);
    });

    // Pagination
    const page = mpCurrentPage[tableName] || 1;
    const totalPages = Math.max(1, Math.ceil(matchingRows.length / MP_PER_PAGE));
    const start = (page - 1) * MP_PER_PAGE;
    const end = start + MP_PER_PAGE;

    matchingRows.forEach((row, i) => {
        row.style.display = (i >= start && i < end) ? '' : 'none';
    });

    // Toggle no-results row
    const noResults = table.querySelector('.mp-no-results');
    if (noResults) noResults.style.display = matchingRows.length === 0 ? '' : 'none';

    // Render pagination
    renderPagination(table, tableName, page, totalPages, matchingRows.length);
}

function renderPagination(table, tableName, current, totalPages, totalItems) {
    const wrap = table.closest('.mp-table-wrap');
    if (!wrap) return;

    let pag = wrap.nextElementSibling;
    if (pag && pag.classList.contains('mp-pagination')) pag.remove();

    if (totalPages <= 1) return;

    pag = document.createElement('div');
    pag.className = 'mp-pagination';

    // Info
    const info = document.createElement('span');
    info.className = 'mp-pagination-info';
    const from = (current - 1) * MP_PER_PAGE + 1;
    const to = Math.min(current * MP_PER_PAGE, totalItems);
    info.textContent = from + '–' + to + ' sur ' + totalItems;
    pag.appendChild(info);

    const btns = document.createElement('div');
    btns.className = 'mp-pagination-btns';

    // Prev
    const prev = document.createElement('button');
    prev.className = 'mp-pagination-btn';
    prev.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>';
    prev.disabled = current <= 1;
    prev.addEventListener('click', () => { mpCurrentPage[tableName] = current - 1; applyTableFilters(tableName); });
    btns.appendChild(prev);

    // Page numbers
    const maxBtns = 5;
    let startP = Math.max(1, current - Math.floor(maxBtns / 2));
    let endP = Math.min(totalPages, startP + maxBtns - 1);
    if (endP - startP + 1 < maxBtns) startP = Math.max(1, endP - maxBtns + 1);

    if (startP > 1) {
        btns.appendChild(makePageBtn(1, tableName, current));
        if (startP > 2) { const dots = document.createElement('span'); dots.className = 'mp-pagination-dots'; dots.textContent = '…'; btns.appendChild(dots); }
    }
    for (let p = startP; p <= endP; p++) btns.appendChild(makePageBtn(p, tableName, current));
    if (endP < totalPages) {
        if (endP < totalPages - 1) { const dots = document.createElement('span'); dots.className = 'mp-pagination-dots'; dots.textContent = '…'; btns.appendChild(dots); }
        btns.appendChild(makePageBtn(totalPages, tableName, current));
    }

    // Next
    const next = document.createElement('button');
    next.className = 'mp-pagination-btn';
    next.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>';
    next.disabled = current >= totalPages;
    next.addEventListener('click', () => { mpCurrentPage[tableName] = current + 1; applyTableFilters(tableName); });
    btns.appendChild(next);

    pag.appendChild(btns);
    wrap.insertAdjacentElement('afterend', pag);
}

function makePageBtn(page, tableName, current) {
    const btn = document.createElement('button');
    btn.className = 'mp-pagination-btn' + (page === current ? ' active' : '');
    btn.textContent = page;
    btn.addEventListener('click', () => { mpCurrentPage[tableName] = page; applyTableFilters(tableName); });
    return btn;
}

/* ══════════════════════════════════════════════════════
   USER MARKETPLACE — Catalogue, Modal, Cart, Locations
   ══════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    /* ── Guard: only run on marketplace pages ── */
    if (!document.querySelector('.um-page') && !document.querySelector('.um-pay-page')) return;

    /* ━━━━━━━━━━━━━ CATALOGUE PAGE ━━━━━━━━━━━━━ */
    if (document.querySelector('.um-page')) {
        initCatalogue();
    }

    /* ━━━━━━━━━━━━━ PAYMENT PAGES ━━━━━━━━━━━━━ */
    if (document.querySelector('.um-pay-page')) {
        initPaymentPage();
    }
});

/* ── Format number: 1234.5 → "1 234,50" ── */
function umFmt(n) {
    return parseFloat(n).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/* ══════════════════════════════════════════════════════
   CATALOGUE INIT
   ══════════════════════════════════════════════════════ */
function initCatalogue() {
    const ROUTES = window.UM_ROUTES || {};
    let cartData = window.UM_CART_INIT || { items: [], total: 0, count: 0 };
    let locData = window.UM_LOC_INIT || { items: [], total: 0, totalCaution: 0, count: 0 };

    /* current modal item */
    let modalType = null;
    let modalId = null;

    /* ── Tab switching ── */
    document.querySelectorAll('.um-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.um-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.um-tab-pane').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            const pane = document.getElementById('pane-' + btn.dataset.tab);
            if (pane) pane.classList.add('active');
        });
    });

    /* ── Search ── */
    const searchInput = document.getElementById('umSearch');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.toLowerCase().trim();
            document.querySelectorAll('.um-card').forEach(card => {
                const text = card.getAttribute('data-search') || '';
                card.style.display = text.includes(q) ? '' : 'none';
            });
        });
    }

    /* ── Modal elements ── */
    const overlay = document.getElementById('umModal');
    const modalImg = document.getElementById('umModalImg');
    const modalTitle = document.getElementById('umModalTitle');
    const modalDesc = document.getElementById('umModalDesc');
    const modalMeta = document.getElementById('umModalMeta');
    const modalPriceRow = document.getElementById('umModalPriceRow');
    const actionsEquip = document.getElementById('umModalActionsEquip');
    const actionsLoc = document.getElementById('umModalActionsLoc');
    const qtyInput = document.getElementById('umQtyInput');
    const stockInfo = document.getElementById('umStockInfo');
    const dateDebut = document.getElementById('umDateDebut');
    const dateFin = document.getElementById('umDateFin');
    const dateError = document.getElementById('umDateError');
    const dateSummary = document.getElementById('umDateSummary');

    /* ── Open modal ── */
    document.querySelectorAll('.um-card-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            modalType = d.detailType;
            modalId = d.detailId;

            /* Image */
            if (d.detailImage) {
                modalImg.innerHTML = '<img src="' + d.detailImage + '" alt="">';
            } else {
                modalImg.innerHTML = '<div class="um-modal-no-img"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>';
            }

            modalTitle.textContent = d.detailNom;
            modalDesc.textContent = d.detailDesc;

            /* Meta tags */
            let metaHtml = '';
            if (modalType === 'equipement') {
                metaHtml += '<span>Catégorie : ' + escHtml(d.detailCategorie) + '</span>';
                metaHtml += '<span>Fournisseur : ' + escHtml(d.detailFournisseur) + '</span>';
                metaHtml += '<span>Stock : ' + d.detailStock + '</span>';
            } else if (modalType === 'vehicule') {
                metaHtml += '<span>' + escHtml(d.detailMarque) + ' ' + escHtml(d.detailModele) + '</span>';
                metaHtml += '<span>Immat. : ' + escHtml(d.detailImmatriculation) + '</span>';
                metaHtml += '<span>Caution : ' + umFmt(d.detailCaution) + ' TND</span>';
            } else if (modalType === 'terrain') {
                metaHtml += '<span>' + escHtml(d.detailVille) + '</span>';
                metaHtml += '<span>' + escHtml(d.detailAdresse) + '</span>';
                metaHtml += '<span>' + d.detailSuperficie + ' ha</span>';
                metaHtml += '<span>Caution : ' + umFmt(d.detailCaution) + ' TND</span>';
            }
            modalMeta.innerHTML = metaHtml;

            /* Prices */
            let priceHtml = '';
            if (modalType === 'equipement') {
                priceHtml = '<span class="um-price-main">' + d.detailPrixFmt + '</span>';
            } else if (modalType === 'vehicule') {
                priceHtml = '<span class="um-price-main">' + umFmt(d.detailPrixJour) + ' TND/jour</span>';
                priceHtml += '<span class="um-price-sub">' + umFmt(d.detailPrixSemaine) + ' TND/sem · ' + umFmt(d.detailPrixMois) + ' TND/mois</span>';
            } else if (modalType === 'terrain') {
                priceHtml = '<span class="um-price-main">' + umFmt(d.detailPrixMois) + ' TND/mois</span>';
                priceHtml += '<span class="um-price-sub">' + umFmt(d.detailPrixAnnee) + ' TND/an</span>';
            }
            modalPriceRow.innerHTML = priceHtml;

            /* Toggle actions */
            actionsEquip.style.display = modalType === 'equipement' ? '' : 'none';
            actionsLoc.style.display = (modalType === 'vehicule' || modalType === 'terrain') ? '' : 'none';

            /* Mini-map for terrain */
            const miniMapEl = document.getElementById('umModalMiniMap');
            if (miniMapEl) {
                // Destroy previous Leaflet instance properly
                if (miniMapEl._leaflet_map) {
                    miniMapEl._leaflet_map.remove();
                    miniMapEl._leaflet_map = null;
                }
                miniMapEl.style.display = 'none';
                miniMapEl.innerHTML = '';
                if (modalType === 'terrain') {
                    miniMapEl.style.display = '';
                    initTerrainMiniMap(miniMapEl, d.detailAdresse, d.detailVille);
                }
            }

            if (modalType === 'equipement') {
                qtyInput.value = 1;
                qtyInput.max = d.detailStock;
                stockInfo.textContent = d.detailStock + ' en stock';
            }

            /* Reset date fields + enforce min = today */
            if (dateDebut) {
                const today = new Date().toISOString().split('T')[0];
                dateDebut.value = ''; dateFin.value = '';
                dateDebut.min = today;
                dateFin.min = today;
            }
            dateError.style.display = 'none';
            dateSummary.style.display = 'none';

            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    });

    /* ── Close modal ── */
    function closeModal() {
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    document.getElementById('umModalClose').addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });

    /* ── Qty controls ── */
    document.getElementById('umQtyMinus').addEventListener('click', () => {
        let v = parseInt(qtyInput.value) || 1;
        if (v > 1) qtyInput.value = v - 1;
    });
    document.getElementById('umQtyPlus').addEventListener('click', () => {
        let v = parseInt(qtyInput.value) || 1;
        let max = parseInt(qtyInput.max) || 999;
        if (v < max) qtyInput.value = v + 1;
    });
    qtyInput.addEventListener('change', () => {
        let v = parseInt(qtyInput.value) || 1;
        let max = parseInt(qtyInput.max) || 999;
        qtyInput.value = Math.max(1, Math.min(v, max));
    });

    /* ── Date validation ── */
    function checkDates() {
        if (!dateDebut.value || !dateFin.value) { dateSummary.style.display = 'none'; dateError.style.display = 'none'; return; }
        const s = new Date(dateDebut.value);
        const e = new Date(dateFin.value);
        if (e <= s) {
            dateError.textContent = 'La date de fin doit être après la date de début.';
            dateError.style.display = '';
            dateSummary.style.display = 'none';
            return;
        }
        dateError.style.display = 'none';
        const days = Math.round((e - s) / 86400000);
        let cost = 0;
        const btn = document.querySelector('.um-card-btn[data-detail-id="' + modalId + '"][data-detail-type="' + modalType + '"]');
        if (btn) {
            if (modalType === 'vehicule') {
                cost = parseFloat(btn.dataset.detailPrixJour) * days;
            } else if (modalType === 'terrain') {
                cost = parseFloat(btn.dataset.detailPrixMois) * (days / 30);
            }
        }
        dateSummary.innerHTML = days + ' jour' + (days > 1 ? 's' : '') + ' — Coût estimé : <strong>' + umFmt(cost) + ' TND</strong>';
        dateSummary.style.display = '';
    }
    if (dateDebut) dateDebut.addEventListener('change', function() {
        if (dateDebut.value) dateFin.min = dateDebut.value;
        if (dateFin.value && dateFin.value <= dateDebut.value) dateFin.value = '';
        checkDates();
    });
    if (dateFin) dateFin.addEventListener('change', checkDates);

    /* ── AJAX helper ── */
    async function umPost(url, body) {
        const resp = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(body).toString(),
        });
        return resp.json();
    }

    /* ── Add to cart ── */
    document.getElementById('umAddCart').addEventListener('click', async () => {
        const qty = parseInt(qtyInput.value) || 1;
        const data = await umPost(ROUTES.cartAdd, { id: modalId, qty: qty });
        if (data.error) { showFlash(data.error, 'danger'); return; }
        cartData = data.cart;
        updateCartBadge();
        renderCartDrawer();
        closeModal();
        showFlash('Article ajouté au panier !', 'success');
    });

    /* ── Add to locations ── */
    document.getElementById('umAddLoc').addEventListener('click', async () => {
        if (!dateDebut.value || !dateFin.value) {
            dateError.textContent = 'Veuillez sélectionner les deux dates.';
            dateError.style.display = '';
            return;
        }
        const data = await umPost(ROUTES.locAdd, {
            type: modalType, id: modalId,
            dateDebut: dateDebut.value, dateFin: dateFin.value,
        });
        if (data.error) { dateError.textContent = data.error; dateError.style.display = ''; return; }
        locData = data.locations;
        updateLocBadge();
        renderLocDrawer();
        closeModal();
        showFlash('Location ajoutée !', 'success');
    });

    /* ━━━━━━━━━━━━━ CART DRAWER ━━━━━━━━━━━━━ */
    const cartDrawer = document.getElementById('umCartDrawer');
    const cartBody = document.getElementById('umCartDrawerBody');
    const cartFooter = document.getElementById('umCartDrawerFooter');
    const cartTotal = document.getElementById('umCartTotal');
    const cartBadge = document.getElementById('umCartBadge');
    const locDrawer = document.getElementById('umLocDrawer');
    const locBody = document.getElementById('umLocDrawerBody');
    const locFooter = document.getElementById('umLocDrawerFooter');
    const locTotal = document.getElementById('umLocTotal');
    const locCaution = document.getElementById('umLocCaution');
    const locBadge = document.getElementById('umLocBadge');
    const backdrop = document.getElementById('umDrawerBackdrop');

    function updateCartBadge() {
        if (cartData.count > 0) {
            cartBadge.textContent = cartData.count;
            cartBadge.style.display = '';
        } else {
            cartBadge.style.display = 'none';
        }
    }

    function updateLocBadge() {
        if (locData.count > 0) {
            locBadge.textContent = locData.count;
            locBadge.style.display = '';
        } else {
            locBadge.style.display = 'none';
        }
    }

    /* ── Resolve image URL from card data attributes ── */
    function resolveImgUrl(id, type) {
        const btn = document.querySelector('.um-card-btn[data-detail-id="' + id + '"][data-detail-type="' + (type || 'equipement') + '"]');
        return btn ? btn.dataset.detailImage : '';
    }

    function renderCartDrawer() {
        if (!cartData.items || cartData.items.length === 0) {
            cartBody.innerHTML = '<p class="um-drawer-empty">Votre panier est vide.</p>';
            cartFooter.style.display = 'none';
            return;
        }
        let html = '';
        cartData.items.forEach(item => {
            const imgUrl = resolveImgUrl(item.id, 'equipement');
            html += '<div class="um-drawer-item">';
            html += '<div class="um-drawer-item-img">';
            if (imgUrl) html += '<img src="' + imgUrl + '" alt="">';
            html += '</div>';
            html += '<div class="um-drawer-item-info">';
            html += '<strong>' + escHtml(item.nom) + '</strong>';
            html += '<div class="um-drawer-item-qty">';
            html += '<button data-cart-qty="' + item.id + '" data-dir="-1">−</button>';
            html += '<span>' + item.qty + '</span>';
            html += '<button data-cart-qty="' + item.id + '" data-dir="1">+</button>';
            html += '</div>';
            html += '<div class="um-drawer-item-price">' + umFmt(item.sousTotal) + ' TND</div>';
            html += '</div>';
            html += '<button class="um-drawer-item-remove" data-cart-rm="' + item.id + '" title="Supprimer">✕</button>';
            html += '</div>';
        });
        cartBody.innerHTML = html;
        cartTotal.textContent = umFmt(cartData.total) + ' TND';
        cartFooter.style.display = '';

        /* Qty buttons inside drawer */
        cartBody.querySelectorAll('[data-cart-qty]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.cartQty;
                const dir = parseInt(btn.dataset.dir);
                const item = cartData.items.find(i => String(i.id) === id);
                if (!item) return;
                const newQty = item.qty + dir;
                if (newQty < 1) return;
                const data = await umPost(ROUTES.cartUpdate, { id: id, qty: newQty });
                if (data.error) { showFlash(data.error, 'danger'); return; }
                cartData = data.cart;
                updateCartBadge();
                renderCartDrawer();
            });
        });

        /* Remove buttons */
        cartBody.querySelectorAll('[data-cart-rm]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const data = await umPost(ROUTES.cartRemove, { id: btn.dataset.cartRm });
                if (data.error) { showFlash(data.error, 'danger'); return; }
                cartData = data.cart;
                updateCartBadge();
                renderCartDrawer();
            });
        });
    }

    function renderLocDrawer() {
        if (!locData.items || locData.items.length === 0) {
            locBody.innerHTML = '<p class="um-drawer-empty">Aucune location ajoutée.</p>';
            locFooter.style.display = 'none';
            return;
        }
        let html = '';
        locData.items.forEach(item => {
            const imgUrl = resolveImgUrl(item.id, item.type);
            html += '<div class="um-drawer-item">';
            html += '<div class="um-drawer-item-img">';
            if (imgUrl) html += '<img src="' + imgUrl + '" alt="">';
            html += '</div>';
            html += '<div class="um-drawer-item-info">';
            html += '<strong>' + escHtml(item.nom) + '</strong>';
            html += '<div class="um-drawer-item-meta">' + item.dateDebut + ' → ' + item.dateFin + ' (' + item.jours + 'j)</div>';
            html += '<div class="um-drawer-item-price">' + umFmt(item.total) + ' TND</div>';
            html += '</div>';
            html += '<button class="um-drawer-item-remove" data-loc-rm="' + item.key + '" title="Supprimer">✕</button>';
            html += '</div>';
        });
        locBody.innerHTML = html;
        locTotal.textContent = umFmt(locData.total) + ' TND';
        locCaution.textContent = umFmt(locData.totalCaution) + ' TND';
        locFooter.style.display = '';

        /* Remove buttons */
        locBody.querySelectorAll('[data-loc-rm]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const data = await umPost(ROUTES.locRemove, { key: btn.dataset.locRm });
                if (data.error) { showFlash(data.error, 'danger'); return; }
                locData = data.locations;
                updateLocBadge();
                renderLocDrawer();
            });
        });
    }

    /* ── Drawer toggles ── */
    function closeAllDrawers() {
        cartDrawer.classList.remove('open');
        locDrawer.classList.remove('open');
        backdrop.classList.remove('open');
        document.body.style.overflow = '';
    }

    document.getElementById('umFabCart').addEventListener('click', () => {
        closeAllDrawers();
        renderCartDrawer();
        cartDrawer.classList.add('open');
        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    });
    document.getElementById('umFabLoc').addEventListener('click', () => {
        closeAllDrawers();
        renderLocDrawer();
        locDrawer.classList.add('open');
        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    });
    document.getElementById('umCartDrawerClose').addEventListener('click', closeAllDrawers);
    document.getElementById('umLocDrawerClose').addEventListener('click', closeAllDrawers);
    backdrop.addEventListener('click', closeAllDrawers);

    /* ── Flash messages ── */
    function showFlash(msg, type) {
        const el = document.createElement('div');
        el.className = 'um-flash um-flash-' + (type || 'success');
        el.innerHTML = msg + '<button class="um-flash-close">&times;</button>';
        document.body.appendChild(el);
        el.querySelector('.um-flash-close').addEventListener('click', () => el.remove());
        setTimeout(() => el.remove(), 4000);
    }

    /* Close existing flash messages */
    document.querySelectorAll('.um-flash-close').forEach(btn => {
        btn.addEventListener('click', () => btn.parentElement.remove());
    });

    /* ── Init badges & drawers from session ── */
    updateCartBadge();
    updateLocBadge();
}

/* ══════════════════════════════════════════════════════
   PAYMENT PAGE — Stripe Elements + Countdown
   ══════════════════════════════════════════════════════ */
function initPaymentPage() {
    const page = document.querySelector('.um-pay-page');
    if (!page) return;

    const stripeKey = page.dataset.stripeKey;
    const clientSecret = page.dataset.clientSecret;
    const cancelUrl = page.dataset.cancelUrl;
    const marketplaceUrl = page.dataset.marketplaceUrl;
    const paymentType = page.dataset.paymentType || 'equipement';

    if (!stripeKey || !clientSecret) return;

    /* ── Init Stripe Elements ── */
    const stripe = Stripe(stripeKey);
    const elements = stripe.elements();

    const style = {
        base: {
            fontFamily: "'Plus Jakarta Sans', sans-serif",
            fontSize: '15px',
            color: '#1f2937',
            '::placeholder': { color: '#9ca3af' },
        },
        invalid: {
            color: '#dc2626',
            iconColor: '#dc2626',
        },
    };

    const cardElement = elements.create('card', { style: style, hidePostalCode: true });
    cardElement.mount('#stripe-card-element');

    const errorsEl = document.getElementById('stripe-card-errors');

    cardElement.on('change', (event) => {
        if (event.error) {
            errorsEl.textContent = event.error.message;
            errorsEl.style.display = '';
        } else {
            errorsEl.style.display = 'none';
        }
    });

    /* ── Form submission ── */
    const form = document.getElementById('umPayForm');
    const submitBtn = document.getElementById('umPaySubmit');
    const btnText = document.getElementById('umPayBtnText');
    const spinner = document.getElementById('umPaySpinner');
    let processing = false;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (processing) return;
        processing = true;

        submitBtn.disabled = true;
        btnText.style.display = 'none';
        spinner.style.display = '';

        const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
            payment_method: { card: cardElement },
        });

        if (error) {
            errorsEl.textContent = error.message;
            errorsEl.style.display = '';
            submitBtn.disabled = false;
            btnText.style.display = '';
            spinner.style.display = 'none';
            processing = false;
            return;
        }

        if (paymentIntent.status === 'succeeded') {
            form.submit();
        } else {
            errorsEl.textContent = 'Le paiement n\'a pas abouti. Veuillez réessayer.';
            errorsEl.style.display = '';
            submitBtn.disabled = false;
            btnText.style.display = '';
            spinner.style.display = 'none';
            processing = false;
        }
    });

    /* ── Payer à la livraison ── */
    const livraisonBtn = document.getElementById('umPayLivraison');
    const livraisonForm = document.getElementById('umPayLivraisonForm');
    if (livraisonBtn && livraisonForm) {
        livraisonBtn.addEventListener('click', () => {
            const adresse = document.getElementById('payAdresse').value.trim();
            const ville = document.getElementById('payVille').value.trim();

            if (!adresse || !ville) {
                firmaAlert(
                    'Champs requis',
                    'Veuillez remplir l\'adresse et la ville de livraison avant de continuer.',
                    'warning',
                    'OK'
                );
                return;
            }

            firmaConfirm(
                'Payer à la livraison',
                'Vous allez passer commande avec paiement à la livraison. Confirmer ?',
                'Confirmer',
                'Annuler'
            ).then((confirmed) => {
                if (confirmed) {
                    document.getElementById('umLivAdresse').value = adresse;
                    document.getElementById('umLivVille').value = ville;
                    livraisonForm.submit();
                }
            });
        });
    }

    /* ── 3-minute countdown ── */
    const timerEl = document.getElementById('umPayTimerText');
    const timerWrap = document.getElementById('umPayTimer');
    let remaining = 180; // 3 minutes in seconds

    const timerInterval = setInterval(() => {
        remaining--;
        const min = Math.floor(remaining / 60);
        const sec = remaining % 60;
        timerEl.textContent = String(min).padStart(2, '0') + ':' + String(sec).padStart(2, '0');

        if (remaining <= 30) {
            timerWrap.classList.add('um-pay-timer-urgent');
        }

        if (remaining <= 0) {
            clearInterval(timerInterval);
            submitBtn.disabled = true;

            // Cancel the PaymentIntent
            fetch(cancelUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'type=' + encodeURIComponent(paymentType),
            }).finally(() => {
                firmaAlert(
                    'Oops ! Temps écoulé',
                    'Vous avez atteint le temps limite. Vous serez redirigé vers la page marketplace dans quelques instants.',
                    'warning',
                    'OK'
                ).then(() => {
                    window.location.href = marketplaceUrl;
                });
            });
        }
    }, 1000);
}

/* ── HTML escape ── */
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

/* ══════════════════════════════════════════════════════
   FIRMA — Map Picker (Leaflet + OSM + Nominatim)
   ══════════════════════════════════════════════════════ */

/* ── Lazy-load Leaflet from CDN ── */
let leafletLoaded = false;
function loadLeaflet() {
    if (leafletLoaded) return Promise.resolve();
    return new Promise((resolve) => {
        if (window.L) { leafletLoaded = true; resolve(); return; }
        const s = document.createElement('script');
        s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        s.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
        s.crossOrigin = '';
        s.onload = () => { leafletLoaded = true; resolve(); };
        document.head.appendChild(s);
    });
}

/* ── Nominatim reverse geocode with debounce ── */
let _nominatimTimer = null;
function reverseGeocode(lat, lng) {
    return new Promise((resolve) => {
        clearTimeout(_nominatimTimer);
        _nominatimTimer = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=fr`, {
                headers: { 'User-Agent': 'FIRMA-App/1.0' }
            })
            .then(r => r.json())
            .then(data => {
                const a = data.address || {};
                const ville = a.city || a.town || a.village || a.municipality || a.state || '';
                const road = a.road || a.pedestrian || a.footway || '';
                const num = a.house_number || '';
                let adresse = (num ? num + ' ' : '') + road;
                // Fallback: use richer Nominatim fields if road is empty
                if (!adresse.trim()) {
                    const parts = [a.suburb, a.neighbourhood, a.hamlet, a.locality, a.county].filter(Boolean);
                    if (parts.length > 0) {
                        adresse = parts.join(', ');
                    } else {
                        // Last resort: use display_name minus country/postcode/ville
                        const display = data.display_name || '';
                        const filtered = display.split(',').map(s => s.trim())
                            .filter(s => s && s !== ville && !/^\d{4,5}$/.test(s) && s !== 'Tunisie' && s !== a.country);
                        adresse = filtered.slice(0, 3).join(', ');
                    }
                }
                resolve({ adresse: adresse.trim(), ville, display: data.display_name || '' });
            })
            .catch(() => resolve({ adresse: '', ville: '', display: '' }));
        }, 400);
    });
}

/* ── Forward geocode (address text → coords) ── */
function forwardGeocode(query) {
    const q = encodeURIComponent(query + ', Tunisie');
    return fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${q}&limit=1&accept-language=fr`, {
        headers: { 'User-Agent': 'FIRMA-App/1.0' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.length > 0) return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
        return null;
    })
    .catch(() => null);
}

/* ── Create the overlay DOM (singleton) ── */
let _mapOverlayEl = null;
function getMapOverlay() {
    if (_mapOverlayEl) return _mapOverlayEl;

    const overlay = document.createElement('div');
    overlay.className = 'firma-map-overlay';
    overlay.id = 'firmaMapOverlay';
    overlay.innerHTML = `
        <div class="firma-map-container">
            <div class="firma-map-header">
                <h3>Choisir un emplacement</h3>
                <button class="firma-map-close" id="firmaMapClose">&times;</button>
            </div>
            <div class="firma-map-area" id="firmaMapArea"></div>
            <div class="firma-map-footer">
                <div class="firma-map-address" id="firmaMapAddress"><em>Cliquez sur la carte pour choisir un emplacement</em></div>
                <button class="firma-map-confirm" id="firmaMapConfirm" disabled>Confirmer</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
    _mapOverlayEl = overlay;
    return overlay;
}

/**
 * Opens a map picker overlay.
 * @param {object} opts
 * @param {string} opts.adresseFieldId — ID of the address input
 * @param {string} opts.villeFieldId   — ID of the ville input
 * @param {string} opts.currentAdresse — Pre-fill address (for edit mode)
 * @param {string} opts.currentVille   — Pre-fill city (for edit mode)
 */
function firmaMapPicker(opts = {}) {
    loadLeaflet().then(() => {
        const overlay = getMapOverlay();
        const mapArea = document.getElementById('firmaMapArea');
        const addressEl = document.getElementById('firmaMapAddress');
        const confirmBtn = document.getElementById('firmaMapConfirm');
        const closeBtn = document.getElementById('firmaMapClose');

        // Default to Tunisia center
        let startLat = 36.8065;
        let startLng = 10.1815;
        let startZoom = 7;

        let selectedData = null;

        // Reset
        confirmBtn.disabled = true;
        addressEl.innerHTML = '<em>Cliquez sur la carte pour choisir un emplacement</em>';

        // Clear previous map instance
        mapArea.innerHTML = '';
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';

        // Slight delay for DOM render
        setTimeout(async () => {
            // If edit mode with existing address → try to geocode it
            const existingAddr = (opts.currentAdresse || '') + ' ' + (opts.currentVille || '');
            if (existingAddr.trim().length > 3) {
                const pos = await forwardGeocode(existingAddr.trim());
                if (pos) { startLat = pos.lat; startLng = pos.lng; startZoom = 15; }
            }

            const map = L.map(mapArea, { zoomControl: true }).setView([startLat, startLng], startZoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 19,
            }).addTo(map);

            let marker = null;

            // If we geocoded existing address, place marker
            if (startZoom === 15) {
                marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
                addressEl.innerHTML = '<em>Position actuelle chargée</em>';
                // Reverse geocode to fill data
                reverseGeocode(startLat, startLng).then(data => {
                    selectedData = data;
                    confirmBtn.disabled = false;
                    addressEl.textContent = data.display || data.adresse + ', ' + data.ville;
                });
                marker.on('dragend', () => {
                    const pos = marker.getLatLng();
                    addressEl.innerHTML = '<em>Chargement…</em>';
                    confirmBtn.disabled = true;
                    reverseGeocode(pos.lat, pos.lng).then(data => {
                        selectedData = data;
                        confirmBtn.disabled = false;
                        addressEl.textContent = data.display || data.adresse + ', ' + data.ville;
                    });
                });
            }

            // Click on map
            map.on('click', (e) => {
                const { lat, lng } = e.latlng;
                if (marker) {
                    marker.setLatLng([lat, lng]);
                } else {
                    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                    marker.on('dragend', () => {
                        const pos = marker.getLatLng();
                        addressEl.innerHTML = '<em>Chargement…</em>';
                        confirmBtn.disabled = true;
                        reverseGeocode(pos.lat, pos.lng).then(data => {
                            selectedData = data;
                            confirmBtn.disabled = false;
                            addressEl.textContent = data.display || data.adresse + ', ' + data.ville;
                        });
                    });
                }
                addressEl.innerHTML = '<em>Chargement…</em>';
                confirmBtn.disabled = true;

                reverseGeocode(lat, lng).then(data => {
                    selectedData = data;
                    confirmBtn.disabled = false;
                    addressEl.textContent = data.display || data.adresse + ', ' + data.ville;
                });
            });

            // Confirm
            const onConfirm = () => {
                if (!selectedData) return;
                const adField = opts.adresseFieldId ? document.getElementById(opts.adresseFieldId) : null;
                const villeField = opts.villeFieldId ? document.getElementById(opts.villeFieldId) : null;
                // Use the full display address (shown in footer) for the adresse field
                if (adField) {
                    const display = selectedData.display || '';
                    // Remove country and postcode from display for cleaner address
                    const cleaned = display.split(',').map(s => s.trim())
                        .filter(s => s && !/^\d{4,5}$/.test(s) && s !== 'Tunisie')
                        .join(', ');
                    adField.value = cleaned || selectedData.adresse;
                }
                if (villeField) villeField.value = selectedData.ville;
                closeMapOverlay();
            };
            confirmBtn.addEventListener('click', onConfirm, { once: true });

            // Close
            function closeMapOverlay() {
                overlay.classList.remove('open');
                document.body.style.overflow = '';
                confirmBtn.removeEventListener('click', onConfirm);
                setTimeout(() => { map.remove(); }, 300);
            }

            closeBtn.onclick = closeMapOverlay;
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) closeMapOverlay();
            }, { once: true });

        }, 100);
    });
}

/* ── Expose globally ── */
window.firmaMapPicker = firmaMapPicker;

/* ── Init mini-map for terrain detail (user marketplace) ── */
function initTerrainMiniMap(containerEl, adresse, ville) {
    if (!containerEl || (!adresse && !ville)) return;
    loadLeaflet().then(async () => {
        const fullQuery = ((adresse || '') + ' ' + (ville || '')).trim();

        // Try full address+ville first, then ville only as fallback
        let pos = null;
        if (fullQuery.length >= 3) pos = await forwardGeocode(fullQuery);
        if (!pos && ville && ville.length >= 2) pos = await forwardGeocode(ville);

        // Ultimate fallback: center on Tunisia
        const lat = pos ? pos.lat : 34.0;
        const lng = pos ? pos.lng : 9.0;
        const zoom = pos ? 14 : 6;

        const map = L.map(containerEl, { zoomControl: false, dragging: true, scrollWheelZoom: false }).setView([lat, lng], zoom);
        containerEl._leaflet_map = map; // Track instance for cleanup
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OSM',
            maxZoom: 19,
        }).addTo(map);
        if (pos) L.marker([lat, lng]).addTo(map);

        // Fix grey rectangle: invalidate after modal render
        setTimeout(() => map.invalidateSize(), 200);
    });
}

/* ── All-Terrains Map Overlay (admin dashboard) ── */
let _allTerrainsOpen = false;
const _geocodeCache = {};

function openAllTerrainsMap(terrains) {
    if (_allTerrainsOpen) return;
    _allTerrainsOpen = true;

    loadLeaflet().then(async () => {
        // Create overlay (no IDs — use direct refs)
        const overlay = document.createElement('div');
        overlay.className = 'firma-map-overlay';
        overlay.style.display = 'flex';
        overlay.innerHTML = `
            <div class="firma-map-container">
                <div class="firma-map-header">
                    <h3>Nos Terrains (${terrains.length})</h3>
                    <button class="firma-map-close">&times;</button>
                </div>
                <div class="firma-map-area"></div>
            </div>`;
        document.body.appendChild(overlay);

        const closeBtn = overlay.querySelector('.firma-map-close');
        const mapEl = overlay.querySelector('.firma-map-area');

        let map = null;
        function closeOverlay() {
            if (map) { map.remove(); map = null; }
            overlay.remove();
            _allTerrainsOpen = false;
        }
        closeBtn.addEventListener('click', closeOverlay);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) closeOverlay(); });

        // Init map centered on Tunisia
        map = L.map(mapEl).setView([34.0, 9.0], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OSM',
            maxZoom: 19,
        }).addTo(map);

        setTimeout(() => { if (map) map.invalidateSize(); }, 150);

        // Cached geocode helper
        async function cachedGeocode(query) {
            if (_geocodeCache[query] !== undefined) return _geocodeCache[query];
            const result = await forwardGeocode(query);
            _geocodeCache[query] = result;
            return result;
        }

        // Geocode terrains with throttling (Nominatim: max 1 req/sec)
        const markers = [];
        for (let i = 0; i < terrains.length; i++) {
            if (!_allTerrainsOpen) break;
            const t = terrains[i];
            const fullQuery = ((t.adresse || '') + ' ' + (t.ville || '')).trim();
            let pos = null;
            const isCachedFull = _geocodeCache[fullQuery] !== undefined;
            if (fullQuery.length >= 3) pos = await cachedGeocode(fullQuery);
            const isCachedVille = _geocodeCache[t.ville || ''] !== undefined;
            if (!pos && t.ville && t.ville.length >= 2) pos = await cachedGeocode(t.ville);
            if (pos) {
                const marker = L.marker([pos.lat, pos.lng]).addTo(map);
                marker.bindPopup(`<strong>${t.titre}</strong><br>${t.adresse || ''}<br><em>${t.ville || ''}</em>`);
                markers.push(marker);
            }
            // Only throttle if we actually hit the API (not cached)
            if (i < terrains.length - 1 && (!isCachedFull || !isCachedVille)) {
                await new Promise(r => setTimeout(r, 1200));
            }
        }

        // Fit bounds if we have markers
        if (markers.length > 0 && map) {
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.15));
        }
    });
}

// Bind to dashboard card
function bindTerrainsCard() {
    const card = document.getElementById('terrains-map-card');
    if (card) {
        card.addEventListener('click', () => {
            const terrains = JSON.parse(card.dataset.terrains || '[]');
            openAllTerrainsMap(terrains);
        });
    }
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindTerrainsCard);
} else {
    bindTerrainsCard();
}
