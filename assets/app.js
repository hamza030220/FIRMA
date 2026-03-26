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

            if (modalType === 'equipement') {
                qtyInput.value = 1;
                qtyInput.max = d.detailStock;
                stockInfo.textContent = d.detailStock + ' en stock';
            }

            /* Reset date fields */
            if (dateDebut) { dateDebut.value = ''; dateFin.value = ''; }
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
    if (dateDebut) dateDebut.addEventListener('change', checkDates);
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
   PAYMENT PAGE — Card preview + formatting
   ══════════════════════════════════════════════════════ */
function initPaymentPage() {
    const numInput = document.getElementById('umCardNum');
    const nameInput = document.getElementById('umCardName');
    const expInput = document.getElementById('umCardExp');

    const prevNum = document.getElementById('umPrevNum');
    const prevName = document.getElementById('umPrevName');
    const prevExp = document.getElementById('umPrevExp');

    if (numInput && prevNum) {
        numInput.addEventListener('input', () => {
            let v = numInput.value.replace(/\D/g, '').substring(0, 16);
            let formatted = v.replace(/(.{4})/g, '$1 ').trim();
            numInput.value = formatted;
            prevNum.textContent = formatted || '•••• •••• •••• ••••';
        });
    }

    if (nameInput && prevName) {
        nameInput.addEventListener('input', () => {
            prevName.textContent = nameInput.value || 'NOM PRÉNOM';
        });
    }

    if (expInput && prevExp) {
        expInput.addEventListener('input', () => {
            let v = expInput.value.replace(/\D/g, '').substring(0, 4);
            if (v.length > 2) v = v.substring(0, 2) + '/' + v.substring(2);
            expInput.value = v;
            prevExp.textContent = v || 'MM/AA';
        });
    }
}

/* ── HTML escape ── */
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}
