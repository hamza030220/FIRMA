/* ══════════════════════════════════════════════════════
   FIRMA — Marketplace Module
   Admin tables, user catalogue, cart, locations,
   payment (Stripe), map picker (Leaflet/Nominatim)
   ══════════════════════════════════════════════════════ */

/* ── HTML escape (local copy) ── */
function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

/* ── Format number: 1234.5 → "1 234,50" ── */
function umFmt(n) {
    return parseFloat(n).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/* ══════════════════════════════════════════════════════
   ADMIN MARKETPLACE — Tabs, Search, Filter, Pagination
   ══════════════════════════════════════════════════════ */

const MP_PER_PAGE = 10;
const mpCurrentPage = {};

document.addEventListener('DOMContentLoaded', () => {
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

    /* ── Restore active tab from URL ── */
    const urlTab = new URLSearchParams(window.location.search).get('tab');
    if (urlTab) {
        const tabBtn = document.querySelector('.um-tab[data-tab="' + urlTab + '"]');
        if (tabBtn) tabBtn.click();
    }

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

    let availCalendar = null;
    let bookedRanges = [];

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

            /* Load availability mini-calendar for vehicule/terrain */
            const availSection = document.getElementById('umAvailSection');
            const availCalEl = document.getElementById('umAvailCalendar');
            const availStatus = document.getElementById('umAvailStatus');
            if (availSection && (modalType === 'vehicule' || modalType === 'terrain')) {
                availSection.style.display = '';
                bookedRanges = [];
                if (availCalendar) { availCalendar.destroy(); availCalendar = null; }
                availStatus.textContent = 'Chargement…';
                availCalEl.innerHTML = '';

                fetch(ROUTES.locDisponibilite + '?type=' + modalType + '&id=' + modalId)
                    .then(r => r.json())
                    .then(data => {
                        bookedRanges = data.booked || [];
                        if (bookedRanges.length === 0) {
                            availStatus.innerHTML = '<span style="color:#27ae60;font-weight:600;">✓ Entièrement disponible — aucune réservation en cours</span>';
                        } else {
                            const parts = bookedRanges.map(b => {
                                const s = new Date(b.start); const e = new Date(b.end);
                                return s.toLocaleDateString('fr-FR') + ' → ' + e.toLocaleDateString('fr-FR');
                            });
                            availStatus.innerHTML = '<span style="color:#c0392b;">Réservé : ' + parts.join(' &nbsp;|&nbsp; ') + '</span>';
                        }

                        // Build FullCalendar mini view
                        if (typeof FullCalendar !== 'undefined') {
                            availCalendar = new FullCalendar.Calendar(availCalEl, {
                                initialView: 'dayGridMonth',
                                locale: 'fr',
                                firstDay: 1,
                                headerToolbar: { left: 'prev', center: 'title', right: 'next' },
                                height: 280,
                                events: bookedRanges.map(b => ({
                                    start: b.start,
                                    end: addDay(b.end),
                                    display: 'background',
                                    color: '#e74c3c',
                                })),
                                dateClick: function(info) {
                                    // Check if date is booked
                                    const clickedDate = info.dateStr;
                                    const isBooked = bookedRanges.some(b => clickedDate >= b.start && clickedDate < addDay(b.end).slice(0,10));
                                    if (isBooked) return;

                                    if (!dateDebut.value || (dateDebut.value && dateFin.value)) {
                                        dateDebut.value = clickedDate;
                                        dateFin.value = '';
                                        dateFin.min = clickedDate;
                                    } else {
                                        if (clickedDate > dateDebut.value) {
                                            dateFin.value = clickedDate;
                                        } else {
                                            dateDebut.value = clickedDate;
                                            dateFin.value = '';
                                        }
                                    }
                                    checkDates();
                                },
                                dayCellDidMount: function(info) {
                                    const d = info.date.toISOString().split('T')[0];
                                    const isBooked = bookedRanges.some(b => d >= b.start && d < addDay(b.end).slice(0,10));
                                    if (isBooked) {
                                        info.el.style.opacity = '0.5';
                                        info.el.style.cursor = 'not-allowed';
                                    }
                                },
                                displayEventTime: false,
                            });
                            availCalendar.render();
                        }
                    })
                    .catch(() => {
                        availStatus.textContent = 'Erreur de chargement.';
                    });
            } else if (availSection) {
                availSection.style.display = 'none';
            }

            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    });

    /* ── Helper: add 1 day to a date string ── */
    function addDay(dateStr) {
        const d = new Date(dateStr);
        d.setDate(d.getDate() + 1);
        return d.toISOString().split('T')[0];
    }

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
    const cartFooterInline = document.getElementById('umCartDrawerFooterInline');
    const cartTotal = document.getElementById('umCartTotal');
    const cartBadge = document.getElementById('umCartBadge');
    const locDrawer = document.getElementById('umLocDrawer');
    const locBody = document.getElementById('umLocDrawerBody');
    const locFooterInline = document.getElementById('umLocDrawerFooterInline');
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
            cartFooterInline.style.display = 'none';
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
        cartFooterInline.style.display = '';

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
            locFooterInline.style.display = 'none';
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
        locFooterInline.style.display = '';

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
        loadHistoriqueCommandes();
        cartDrawer.classList.add('open');
        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    });
    document.getElementById('umFabLoc').addEventListener('click', () => {
        closeAllDrawers();
        renderLocDrawer();
        loadHistoriqueLocations();
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

    /* ━━━━━━━━━━━━━ HISTORIQUE — Ancien paniers ━━━━━━━━━━━━━ */
    const histCartList = document.getElementById('umHistCartList');
    const histLocList = document.getElementById('umHistLocList');
    const histOverlay = document.getElementById('umHistOverlay');
    const histModalTitle = document.getElementById('umHistModalTitle');
    const histModalBody = document.getElementById('umHistModalBody');
    const histReorderBtn = document.getElementById('umHistReorder');
    const histPdfLink = document.getElementById('umHistPdf');

    let currentHistCommande = null;

    async function loadHistoriqueCommandes() {
        histCartList.innerHTML = '<p class="um-drawer-empty um-hist-loading">Chargement…</p>';
        try {
            const resp = await fetch(ROUTES.histCommandes, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await resp.json();
            const commandes = data.commandes || [];
            if (commandes.length === 0) {
                histCartList.innerHTML = '<p class="um-drawer-empty">Aucun ancien panier.</p>';
                return;
            }
            let html = '';
            commandes.forEach(cmd => {
                html += '<div class="um-hist-card" data-hist-cmd-id="' + cmd.id + '">';
                html += '<div class="um-hist-card-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></div>';
                html += '<div class="um-hist-card-info">';
                html += '<strong>' + escHtml(cmd.numero) + '</strong>';
                html += '<span>' + cmd.date + '</span>';
                html += '</div>';
                html += '<div class="um-hist-card-amount">' + umFmt(cmd.montant) + ' TND</div>';
                html += '<button class="um-hist-card-rm" data-hist-hide="' + cmd.id + '" title="Masquer">✕</button>';
                html += '</div>';
            });
            histCartList.innerHTML = html;

            /* Click card → open overlay */
            histCartList.querySelectorAll('.um-hist-card').forEach(card => {
                card.addEventListener('click', (e) => {
                    if (e.target.closest('.um-hist-card-rm')) return;
                    const id = parseInt(card.dataset.histCmdId);
                    const cmd = commandes.find(c => c.id === id);
                    if (cmd) openHistOverlay(cmd);
                });
            });

            /* Hide buttons */
            histCartList.querySelectorAll('[data-hist-hide]').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.histHide;
                    await umPost(ROUTES.histHide, { id: id });
                    btn.closest('.um-hist-card').remove();
                    if (!histCartList.querySelector('.um-hist-card')) {
                        histCartList.innerHTML = '<p class="um-drawer-empty">Aucun ancien panier.</p>';
                    }
                });
            });
        } catch (err) {
            histCartList.innerHTML = '<p class="um-drawer-empty">Erreur de chargement.</p>';
        }
    }

    /* ── Open historique overlay ── */
    function openHistOverlay(cmd) {
        currentHistCommande = cmd;
        histModalTitle.textContent = 'Commande ' + cmd.numero;

        let html = '<label class="um-hist-select-all"><input type="checkbox" id="umHistSelectAll" checked> Tout sélectionner</label>';

        cmd.details.forEach((d, idx) => {
            const unavail = !d.disponible || d.stockActuel < 1;
            html += '<div class="um-hist-prod' + (unavail ? ' um-hist-prod-unavail' : '') + '">';
            html += '<input type="checkbox" class="um-hist-prod-check" data-idx="' + idx + '"' + (unavail ? ' disabled' : ' checked') + '>';
            html += '<div class="um-hist-prod-img">';
            if (d.image) html += '<img src="' + d.image + '" alt="">';
            html += '</div>';
            html += '<div class="um-hist-prod-info">';
            html += '<strong>' + escHtml(d.nom) + '</strong>';
            html += '<span>' + umFmt(d.prix) + ' TND/u</span>';
            if (unavail) html += '<span class="um-hist-prod-stock-warn">Indisponible</span>';
            else html += '<span>Stock : ' + d.stockActuel + '</span>';
            html += '</div>';
            html += '<div class="um-hist-prod-qty">';
            html += '<button class="um-hist-qty-btn" data-hist-qty-dir="-1" data-hist-qty-idx="' + idx + '"' + (unavail ? ' disabled' : '') + '>−</button>';
            html += '<span class="um-hist-qty-val" data-hist-qty-val="' + idx + '">' + d.qty + '</span>';
            html += '<button class="um-hist-qty-btn" data-hist-qty-dir="1" data-hist-qty-idx="' + idx + '"' + (unavail ? ' disabled' : '') + '>+</button>';
            html += '</div>';
            html += '</div>';
        });
        histModalBody.innerHTML = html;

        /* PDF link — set href */
        const pdfUrl = ROUTES.histPdfBase.replace('{id}', cmd.id);
        histPdfLink.href = pdfUrl;
        histPdfLink.classList.remove('disabled');

        /* Track qty changes for PDF disable */
        function checkQtyChanged() {
            let changed = false;
            cmd.details.forEach((d, idx) => {
                const valEl = histModalBody.querySelector('[data-hist-qty-val="' + idx + '"]');
                if (valEl && parseInt(valEl.textContent) !== d.qty) changed = true;
            });
            if (changed) histPdfLink.classList.add('disabled');
            else histPdfLink.classList.remove('disabled');
        }

        /* Qty buttons */
        histModalBody.querySelectorAll('.um-hist-qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.histQtyIdx);
                const dir = parseInt(btn.dataset.histQtyDir);
                const valEl = histModalBody.querySelector('[data-hist-qty-val="' + idx + '"]');
                let val = parseInt(valEl.textContent);
                val += dir;
                const max = cmd.details[idx].stockActuel;
                if (val < 1) val = 1;
                if (val > max) val = max;
                valEl.textContent = val;
                checkQtyChanged();
            });
        });

        /* Select all checkbox */
        const selectAll = document.getElementById('umHistSelectAll');
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                histModalBody.querySelectorAll('.um-hist-prod-check:not(:disabled)').forEach(cb => {
                    cb.checked = selectAll.checked;
                });
            });
        }

        /* Show overlay */
        histOverlay.classList.add('open');
    }

    /* ── Close historique overlay ── */
    function closeHistOverlay() {
        histOverlay.classList.remove('open');
        currentHistCommande = null;
    }
    document.getElementById('umHistOverlayClose').addEventListener('click', closeHistOverlay);
    histOverlay.addEventListener('click', (e) => { if (e.target === histOverlay) closeHistOverlay(); });

    /* ── Reorder from historique ── */
    histReorderBtn.addEventListener('click', async () => {
        if (!currentHistCommande) return;
        const items = [];
        histModalBody.querySelectorAll('.um-hist-prod-check:checked').forEach(cb => {
            const idx = parseInt(cb.dataset.idx);
            const d = currentHistCommande.details[idx];
            if (!d || !d.disponible) return;
            const valEl = histModalBody.querySelector('[data-hist-qty-val="' + idx + '"]');
            const qty = valEl ? parseInt(valEl.textContent) : d.qty;
            items.push({ id: d.id, qty: qty });
        });
        if (items.length === 0) {
            showFlash('Aucun article sélectionné.', 'warning');
            return;
        }
        try {
            const resp = await fetch(ROUTES.histReorder, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({ items: JSON.stringify(items) }).toString(),
            });
            const data = await resp.json();
            if (data.error) { showFlash(data.error, 'danger'); return; }
            cartData = data.cart;
            updateCartBadge();
            renderCartDrawer();
            closeHistOverlay();
            showFlash('Articles ajoutés au panier !', 'success');
        } catch (err) {
            showFlash('Erreur lors de l\'ajout.', 'danger');
        }
    });

    /* ── PDF link click guard ── */
    histPdfLink.addEventListener('click', (e) => {
        if (histPdfLink.classList.contains('disabled')) {
            e.preventDefault();
            showFlash('Exportation PDF indisponible : les quantités ont été modifiées.', 'warning');
        }
    });

    /* ━━━━━━━━━━━━━ HISTORIQUE — Locations ━━━━━━━━━━━━━ */
    async function loadHistoriqueLocations() {
        histLocList.innerHTML = '<p class="um-drawer-empty um-hist-loading">Chargement…</p>';
        try {
            const resp = await fetch(ROUTES.histLocations, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await resp.json();
            const { enCours = [], aVenir = [], expirees = [] } = data;

            if (enCours.length === 0 && aVenir.length === 0 && expirees.length === 0) {
                histLocList.innerHTML = '<p class="um-drawer-empty">Aucune location passée.</p>';
                return;
            }

            let html = '';
            if (enCours.length > 0) {
                html += '<div class="um-hist-section-title en-cours">En cours</div>';
                enCours.forEach(loc => { html += buildLocCard(loc, false); });
            }
            if (aVenir.length > 0) {
                html += '<div class="um-hist-section-title a-venir">À venir</div>';
                aVenir.forEach(loc => { html += buildLocCard(loc, false); });
            }
            if (expirees.length > 0) {
                html += '<div class="um-hist-section-title expirees">Expirées</div>';
                expirees.forEach(loc => { html += buildLocCard(loc, true); });
            }
            histLocList.innerHTML = html;

            /* Hide buttons on expired */
            histLocList.querySelectorAll('[data-hist-loc-hide]').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const id = btn.dataset.histLocHide;
                    await umPost(ROUTES.histLocHide, { id: id });
                    btn.closest('.um-hist-loc-card').remove();
                    /* Check if section is now empty */
                    if (!histLocList.querySelector('.um-hist-loc-card')) {
                        histLocList.innerHTML = '<p class="um-drawer-empty">Aucune location passée.</p>';
                    }
                });
            });
        } catch (err) {
            histLocList.innerHTML = '<p class="um-drawer-empty">Erreur de chargement.</p>';
        }
    }

    function buildLocCard(loc, showHide) {
        const iconColor = loc.type === 'vehicule' ? '#2563eb' : '#e6a817';
        const iconSvg = loc.type === 'vehicule'
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="' + iconColor + '" stroke-width="2"><path d="M5 17h2m10 0h2M3 9l2-5h14l2 5"/><rect x="3" y="9" width="18" height="8" rx="2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="' + iconColor + '" stroke-width="2"><path d="M3 21V3h18v18H3z"/><path d="M9 21V9h6v12"/></svg>';
        let html = '<div class="um-hist-loc-card">';
        html += '<div class="um-hist-card-icon">' + iconSvg + '</div>';
        html += '<div class="um-hist-card-info">';
        html += '<strong>' + escHtml(loc.nom) + '</strong>';
        html += '<span>' + loc.dateDebut + ' → ' + loc.dateFin + '</span>';
        html += '<span>' + loc.jours + 'j · ' + umFmt(loc.prix) + ' TND</span>';
        html += '</div>';
        if (showHide) {
            html += '<button class="um-hist-card-rm" data-hist-loc-hide="' + loc.id + '" title="Masquer">✕</button>';
        }
        html += '</div>';
        return html;
    }
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
                window.firmaAlert(
                    'Champs requis',
                    'Veuillez remplir l\'adresse et la ville de livraison avant de continuer.',
                    'warning',
                    'OK'
                );
                return;
            }

            window.firmaConfirm(
                'Payer à la livraison',
                'Vous allez passer commande avec paiement à la livraison. Confirmer ?',
                'Confirmer',
                'warning'
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
                window.firmaAlert(
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


