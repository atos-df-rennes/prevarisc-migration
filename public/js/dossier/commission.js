/**
 * Gestion du champ Commission concernée
 * 
 * Comportement legacy :
 * - Bouton "Choisir une autre commission" dans le template (pas créé en JS)
 * - Clic bouton : vide dates visite/commission + enable select + masque bouton
 * - Changement commission : disable select + affiche bouton
 */
(function() {
    'use strict';

    /**
     * Initialise la gestion de la commission
     */
    function initCommission() {
        const commissionSelect = document.getElementById('dossier_commission');

        if (!commissionSelect) return;

        // Event : Changement de commission dans le select
        commissionSelect.addEventListener('change', handleCommissionChange);
    }

    /**
     * Gère le changement de commission
     * Vide dates + affectations + déclenche masquage calendriers via événement custom
     */
    function handleCommissionChange(e) {
        // Vider les dates visite/commission (legacy lignes 172-174)
        const dateVisite = document.getElementById('dossier_dateVisite');
        const dateCommission = document.getElementById('dossier_dateCommission');

        if (dateVisite) dateVisite.value = '';
        if (dateCommission) dateCommission.value = '';

        // Supprimer alertes dates liées
        const alertDatesLieesDiv = document.querySelectorAll('.dates-liees__wrapper');
        alertDatesLieesDiv.forEach(div => div.remove());

        // Vider les affectations (legacy lignes 151-152, 173-175)
        const affectationCommission = document.getElementById('dossier_idAffectationDossierCommission');
        const affectationVisite = document.getElementById('dossier_idAffectationDossierVisite');
        
        if (affectationCommission) affectationCommission.value = '';
        if (affectationVisite) affectationVisite.value = '';

        // Déclencher masquage des calendriers via événement custom
        // calendar-init.js écoute cet événement et gère le masquage proprement
        // Les boutons "Afficher" ne sont pas encore visibles à ce stade (showShowButton=false)
        document.dispatchEvent(new CustomEvent('calendar:hideAll'));

        // Afficher le bouton "Afficher" uniquement si une commission est sélectionnée
        const hasCommission = !!e.target.value;
        document.querySelectorAll('[data-calendar-type].showCalendar').forEach(function(btn) {
            btn.classList.toggle('hidden', !hasCommission);
        });
    }

    // Initialiser au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCommission);
    } else {
        initCommission();
    }
})();

