/* ══════════════════════════════════════════════════════
   FIRMA — Field-by-Field Form Validation
   Toast popup (top-right) + red border on error fields
   ══════════════════════════════════════════════════════ */

(function () {
    'use strict';

    /* ── Regex ── */
    const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const TEL_TN_RE = /^[2-9]\d{7}$/; /* Tunisian: 8 digits, starts 2-9 */
    const IMMAT_TN_RE = /^(\d{3}\s*TUN|TUN\s*\d{4})$/i; /* Tunisian plate: 123 TUN or TUN 1234 */

    /* ══════════════════════════════════════════════════
       FIELD RULES — keyed by HTML id
       required  : field must have a value
       select    : field is a <select>
       money     : MoneyType (type="text") — parse as number
       integer   : IntegerType (type="number") — whole number
       number    : NumberType — decimal allowed
       gt        : value must be strictly greater than N
       min       : value must be ≥ N
       email     : must match email regex
       tel       : must match Tunisian phone
       image     : must be an image file
       maxSize   : max file size in bytes
       label     : human-readable name for error messages
       ══════════════════════════════════════════════════ */
    const RULES = {
        /* ── Equipement ── */
        'equipement_nom':             { required: true, label: 'Nom' },
        'equipement_categorie':       { required: true, select: true, label: 'Catégorie' },
        'equipement_fournisseur':     { required: true, select: true, label: 'Fournisseur' },
        'equipement_prixAchat':       { required: true, money: true, gt: 0, label: "Prix d'achat" },
        'equipement_prixVente':       { required: true, money: true, gt: 0, label: 'Prix de vente' },
        'equipement_quantiteStock':   { required: true, integer: true, min: 1, label: 'Quantité en stock' },
        'equipement_seuilAlerte':     { required: true, integer: true, min: 0, label: "Seuil d'alerte" },
        'equipement_imageFile':       { image: true, maxSize: 5242880, label: 'Image' },

        /* ── Vehicule ── */
        'vehicule_nom':               { required: true, label: 'Nom' },
        'vehicule_categorie':         { required: true, select: true, label: 'Catégorie' },
        'vehicule_marque':            { required: true, label: 'Marque' },
        'vehicule_modele':            { required: true, label: 'Modèle' },
        'vehicule_immatriculation':   { required: true, immat: true, label: 'Immatriculation' },
        'vehicule_prixJour':          { required: true, money: true, gt: 0, label: 'Prix / jour' },
        'vehicule_prixSemaine':       { required: true, money: true, gt: 0, label: 'Prix / semaine' },
        'vehicule_prixMois':          { required: true, money: true, gt: 0, label: 'Prix / mois' },
        'vehicule_caution':           { required: true, money: true, gt: 0, label: 'Caution' },
        'vehicule_imageFile':         { image: true, maxSize: 5242880, label: 'Image' },

        /* ── Terrain ── */
        'terrain_titre':              { required: true, label: 'Titre' },
        'terrain_categorie':          { required: true, select: true, label: 'Catégorie' },
        'terrain_ville':              { required: true, label: 'Ville' },
        'terrain_superficieHectares': { required: true, number: true, gt: 0, label: 'Superficie' },
        'terrain_adresse':            { required: true, label: 'Adresse' },
        'terrain_prixMois':           { required: true, money: true, gt: 0, label: 'Prix / mois' },
        'terrain_prixAnnee':          { required: true, money: true, gt: 0, label: "Prix / année" },
        'terrain_caution':            { required: true, money: true, gt: 0, label: 'Caution' },
        'terrain_imageFile':          { image: true, maxSize: 5242880, label: 'Image' },

        /* ── Fournisseur ── */
        'fournisseur_nomEntreprise':  { required: true, label: "Nom de l'entreprise" },
        'fournisseur_contactNom':     { required: true, label: 'Nom du contact' },
        'fournisseur_email':          { required: true, email: true, label: 'E-mail' },
        'fournisseur_telephone':      { required: true, tel: true, label: 'Téléphone' },
        'fournisseur_adresse':        { required: true, label: 'Adresse' },
        'fournisseur_ville':          { required: true, label: 'Ville' },

        /* ── Login ── */
        'input-email':                { required: true, email: true, label: 'Adresse email' },
        'input-password':             { required: true, label: 'Mot de passe' },

        /* ── Paiement équipements ── */
        'payAdresse':                 { required: true, label: 'Adresse' },
        'payVille':                   { required: true, label: 'Ville' },
    };

    /* ── Validate a single field against its rule ── */
    function checkField(field) {
        var id = field.id || '';
        var rule = RULES[id];
        var value = (field.value || '').trim();
        var type = (field.getAttribute('type') || '').toLowerCase();
        var tag = field.tagName.toLowerCase();

        /* skip non-user fields */
        if (type === 'hidden' || type === 'submit' || type === 'button' || tag === 'button') return null;

        /* browser can't parse number input → bad input */
        if (field.validity && field.validity.badInput) {
            var lbl = (rule && rule.label) || guessLabel(field) || 'Champ';
            return lbl + ' : valeur invalide.';
        }

        /* no rule → fallback: only check HTML required attribute */
        if (!rule) {
            if (field.hasAttribute('required') && type !== 'checkbox' && type !== 'file' && !value) {
                return (guessLabel(field) || 'Champ') + ' : ce champ est obligatoire.';
            }
            return null;
        }

        /* required */
        if (rule.required) {
            if (rule.select || tag === 'select') {
                if (!value || value === '-- Choisir --') return rule.label + ' : veuillez sélectionner une option.';
            } else if (type === 'file') {
                if (!field.files || !field.files.length) return rule.label + ' : ce champ est obligatoire.';
            } else if (!value) {
                return rule.label + ' : ce champ est obligatoire.';
            }
        }

        /* stop here if empty + not required */
        if (!value && type !== 'file') return null;

        /* email */
        if (rule.email && value && !EMAIL_RE.test(value)) {
            return rule.label + ' : adresse email invalide.';
        }

        /* téléphone tunisien */
        if (rule.tel && value) {
            var digits = value.replace(/[\s\-().+]/g, '').replace(/^216/, '');
            if (!TEL_TN_RE.test(digits)) return rule.label + ' : numéro invalide (8 chiffres, commence par 2-9).';
        }

        /* immatriculation tunisienne */
        if (rule.immat && value) {
            if (!IMMAT_TN_RE.test(value.trim())) return rule.label + ' : format invalide (ex: 123 TUN ou TUN 1234).';
        }

        /* money (Symfony MoneyType = type="text") */
        if (rule.money && value) {
            var n = parseFloat(value.replace(',', '.'));
            if (isNaN(n)) return rule.label + ' : montant invalide.';
            if (rule.gt !== undefined && n <= rule.gt) return rule.label + ' : doit être supérieur à ' + rule.gt + '.';
            if (rule.min !== undefined && n < rule.min) return rule.label + ' : doit être ≥ ' + rule.min + '.';
        }

        /* integer (Symfony IntegerType = type="number") */
        if (rule.integer && value) {
            var ni = parseFloat(value);
            if (isNaN(ni) || !Number.isInteger(ni)) return rule.label + ' : nombre entier requis.';
            if (rule.gt !== undefined && ni <= rule.gt) return rule.label + ' : doit être supérieur à ' + rule.gt + '.';
            if (rule.min !== undefined && ni < rule.min) return rule.label + ' : doit être ≥ ' + rule.min + '.';
        }

        /* number / decimal (Symfony NumberType) */
        if (rule.number && value) {
            var nd = parseFloat(value.replace(',', '.'));
            if (isNaN(nd)) return rule.label + ' : nombre invalide.';
            if (rule.gt !== undefined && nd <= rule.gt) return rule.label + ' : doit être supérieur à ' + rule.gt + '.';
            if (rule.min !== undefined && nd < rule.min) return rule.label + ' : doit être ≥ ' + rule.min + '.';
        }

        /* image file */
        if (rule.image && type === 'file' && field.files && field.files.length) {
            var f = field.files[0];
            if (!f.type.startsWith('image/')) return rule.label + ' : seules les images sont acceptées.';
            if (rule.maxSize && f.size > rule.maxSize) {
                return rule.label + ' : le fichier ne doit pas dépasser ' + Math.round(rule.maxSize / 1048576) + ' Mo.';
            }
        }

        return null;
    }

    /* ── Guess label from nearest <label> ── */
    function guessLabel(field) {
        var wrap = field.closest('div');
        if (wrap) {
            var lbl = wrap.querySelector('label');
            if (lbl) return lbl.textContent.trim();
        }
        return '';
    }

    /* ── Validate all fields in a form ── */
    function validateForm(form) {
        var fields = form.querySelectorAll('input, select, textarea');
        var errors = [];
        var first = null;

        fields.forEach(function (f) {
            f.classList.remove('firma-field-error');
            var err = checkField(f);
            if (err) {
                errors.push(err);
                f.classList.add('firma-field-error');
                if (!first) first = f;
            }
        });

        if (errors.length) {
            showValidationToast(errors);
            if (first) {
                first.focus();
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        return errors.length === 0;
    }

    /* ── Show errors in a single toast popup ── */
    function showValidationToast(errors) {
        var html;
        if (errors.length === 1) {
            html = errors[0];
        } else {
            html = '<strong>' + errors.length + ' erreurs :</strong>'
                 + '<ul>';
            errors.forEach(function (e) { html += '<li>' + e + '</li>'; });
            html += '</ul>';
        }
        if (typeof window.firmaToast === 'function') {
            window.firmaToast(html, 'danger', 5000);
        }
    }

    /* ── Init: hook all novalidate forms ── */
    function init() {
        document.querySelectorAll('form[novalidate]').forEach(function (form) {
            if (form.getAttribute('data-no-firma-validate') === 'true') return;

            /* Capture phase → fires BEFORE other submit handlers (e.g. Stripe) */
            form.addEventListener('submit', function (e) {
                if (!validateForm(form)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }, true);

            /* Live clear red border on user input */
            form.addEventListener('input', function (e) {
                if (e.target.matches('input, textarea')) e.target.classList.remove('firma-field-error');
            });
            form.addEventListener('change', function (e) {
                if (e.target.matches('select, input[type="file"]')) e.target.classList.remove('firma-field-error');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
