/**
 * Gestion du bouton "Effacer la date" (visite / commission)
 *
 * Permet de vider explicitement une date de visite ou de commission (et son
 * affectation associée), sans avoir à repasser par le select "Commission
 * concernée". Dans le legacy, seul le bouton "Choisir une autre commission"
 * permettait de vider les dates, ce qui n'était pas intuitif pour un simple
 * effacement de date.
 */
(function() {
    'use strict';

    function initClearDateButtons() {
        document.querySelectorAll('.clearDate').forEach(function(button) {
            button.addEventListener('click', handleClearDate);
        });
    }

    function handleClearDate(e) {
        const button = e.currentTarget;
        const dateField = document.getElementById(button.dataset.clearField);
        const affectationField = document.getElementById(button.dataset.affectationField);
        const calendarType = button.dataset.calendarType;

        if (dateField) dateField.value = '';
        if (affectationField) affectationField.value = '';

        // Supprimer l'alerte des dates liées (affichée sous la date de visite)
        if (calendarType === 'visite') {
            document.querySelectorAll('.dates-liees__wrapper').forEach(function(div) {
                div.remove();
            });
        }

        // Masquer le calendrier associé s'il était affiché
        document.dispatchEvent(new CustomEvent('calendar:hide', { detail: { type: calendarType } }));

        // Le bouton n'a plus d'utilité tant qu'aucune date n'est sélectionnée
        button.classList.add('hidden');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initClearDateButtons);
    } else {
        initClearDateButtons();
    }
})();
