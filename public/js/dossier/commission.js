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
        const changeBtn = document.querySelector('.btn-change-commission');
        
        if (!commissionSelect || !changeBtn) return;

        // Désactiver le select si commission déjà sélectionnée au chargement
        if (commissionSelect.value && commissionSelect.value !== '') {
            commissionSelect.disabled = true;
        }

        // Event : Clic sur "Choisir une autre commission"
        changeBtn.addEventListener('click', handleChangeCommission);

        // Event : Changement de commission dans le select
        commissionSelect.addEventListener('change', handleCommissionChange);
    }

    /**
     * Gère le clic sur "Choisir une autre commission"
     * Comportement legacy : vide dates + enable select + masque bouton
     */
    function handleChangeCommission() {
        const commissionSelect = document.getElementById('dossier_commission');
        const changeBtn = document.querySelector('.btn-change-commission');
        
        // Vider les dates visite/commission (comme legacy ligne 172-175)
        const dateVisite = document.getElementById('dossier_dateVisite');
        const dateCommission = document.getElementById('dossier_dateCommission');
        
        if (dateVisite) dateVisite.value = '';
        if (dateCommission) dateCommission.value = '';
        
        // TODO Phase 2: Vider aussi les affectations
        // - ID_AFFECTATION_DOSSIER_VISITE
        // - ID_AFFECTATION_DOSSIER_COMMISSION
        
        // Enable le select commission
        if (commissionSelect) {
            commissionSelect.disabled = false;
            commissionSelect.focus();
        }
        
        // Masquer le bouton
        if (changeBtn) {
            changeBtn.style.display = 'none';
        }
    }

    /**
     * Gère le changement de commission
     * Disable select + affiche bouton si commission sélectionnée
     */
    function handleCommissionChange(e) {
        const commissionSelect = e.target;
        const changeBtn = document.querySelector('.btn-change-commission');
        
        if (!changeBtn) return;
        
        if (commissionSelect.value && commissionSelect.value !== '') {
            // Commission sélectionnée : disable select + affiche bouton
            commissionSelect.disabled = true;
            changeBtn.style.display = '';
        } else {
            // Aucune commission : enable select + masque bouton
            commissionSelect.disabled = false;
            changeBtn.style.display = 'none';
        }
        
        // TODO Phase 2 : Mettre à jour le calendrier date visite
        // updateDateVisiteCalendar(commissionSelect.value);
    }

    /**
     * TODO Phase 2 : Mettre à jour le calendrier de date de visite selon la commission
     * 
     * @param {string} commissionId - ID de la commission sélectionnée
     */
    function updateDateVisiteCalendar(commissionId) {
        // À implémenter avec fullcalendar
        // - Charger dates disponibles pour cette commission
        // - Filtrer par type de commission
        // - Gérer affectations (dossieraffectation)
        console.log('TODO: Implémenter calendrier fullcalendar pour commission', commissionId);
    }

    // Initialiser au chargement du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCommission);
    } else {
        initCommission();
    }
})();

