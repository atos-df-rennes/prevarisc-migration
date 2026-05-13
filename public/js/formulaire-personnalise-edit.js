/* =============================================================================
 * formulaire-personnalise-edit.js — Formulaire personnalisé (édition)
 *
 * Module ES6 — chargé via <script type="module" src="...">
 *
 * Fonctionnalités :
 *   - Bouton d'enregistrement avec état de chargement
 *   - Bouton "Aujourd'hui" pour les champs date (event delegation)
 * ============================================================================= */

import { initLoadingButton } from './modules/loading-button.js';

initLoadingButton('#js-btn-enregistrer-descriptif', 'Enregistrement...');

// Bouton "Aujourd'hui" : event delegation pour gérer les lignes ajoutées dynamiquement
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-today[data-target]');
    if (!btn) return;
    e.preventDefault();
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    const today = new Date();
    input.value = [
        today.getFullYear(),
        String(today.getMonth() + 1).padStart(2, '0'),
        String(today.getDate()).padStart(2, '0'),
    ].join('-');
});
