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
     * Vide dates + affectations + masque calendriers (legacy lignes 150-168, 170-191)
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

        // Masquer calendriers s'ils sont affichés (legacy lignes 177-186)
        hideCalendarsIfVisible();
    }

    /**
     * Masque les calendriers s'ils sont visibles
     * Correspond au comportement legacy lignes 177-186
     */
    function hideCalendarsIfVisible() {
        const calendar = document.getElementById('calendar');
        const calendarVisite = document.getElementById('calendarVisite');

        // Masquer calendrier commission
        if (calendar && calendar.offsetParent !== null) {
            calendar.style.display = 'none';
            calendar.innerHTML = '';
            
            const calendarParent = calendar.closest('li');
            if (calendarParent) calendarParent.style.display = 'none';

            // Inverser boutons Afficher/Masquer
            toggleCalendarButtons('commission', false);
        }

        // Masquer calendrier visite
        if (calendarVisite && calendarVisite.offsetParent !== null) {
            calendarVisite.style.display = 'none';
            calendarVisite.innerHTML = '';
            
            const calendarVisiteParent = calendarVisite.closest('li');
            if (calendarVisiteParent) calendarVisiteParent.style.display = 'none';

            // Inverser boutons Afficher/Masquer
            toggleCalendarButtons('visite', false);
        }
    }

    /**
     * Inverse l'affichage des boutons Afficher/Masquer calendrier
     * 
     * @param {string} type - Type de calendrier ('commission' ou 'visite')
     * @param {boolean} isVisible - true si calendrier visible, false sinon
     */
    function toggleCalendarButtons(type, isVisible) {
        const showButtons = document.querySelectorAll(`[data-calendar-type="${type}"].showCalendar`);
        const hideButtons = document.querySelectorAll(`[data-calendar-type="${type}"].hideCalendar`);

        showButtons.forEach(btn => {
            if (isVisible) {
                btn.classList.add('hidden');
            } else {
                btn.classList.remove('hidden');
            }
        });

        hideButtons.forEach(btn => {
            if (isVisible) {
                btn.classList.remove('hidden');
            } else {
                btn.classList.add('hidden');
            }
        });
    }

    // Initialiser au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCommission);
    } else {
        initCommission();
    }
})();

