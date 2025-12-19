/**
 * Gestion du champ Commission concernée
 * 
 * Fonctionnalités :
 * - Bouton "Choisir une autre commission" (enable/disable select)
 * - Logique d'affichage conditionnel
 * - TODO Phase 2 : Calendrier fullcalendar pour dates visite/commission
 */

(function() {
    'use strict';

    /**
     * Initialise la gestion de la commission
     */
    function initCommission() {
        const commissionSelect = document.getElementById('dossier_commission');
        if (!commissionSelect) return;

        const fieldCommission = document.getElementById('field-commission');
        if (!fieldCommission) return;

        // Créer le bouton "Choisir une autre commission" si commission déjà sélectionnée
        createChangeCommissionButton();

        // Gérer le changement de commission
        commissionSelect.addEventListener('change', handleCommissionChange);
    }

    /**
     * Crée le bouton "Choisir une autre commission" si nécessaire
     */
    function createChangeCommissionButton() {
        const commissionSelect = document.getElementById('dossier_commission');
        const fieldCommission = document.getElementById('field-commission');
        
        if (!commissionSelect || !fieldCommission) return;

        // Vérifier si une commission est déjà sélectionnée (pas placeholder)
        const hasCommission = commissionSelect.value && commissionSelect.value !== '';

        if (hasCommission) {
            // Désactiver le select
            commissionSelect.disabled = true;

            // Créer le bouton s'il n'existe pas déjà
            let changeBtn = fieldCommission.querySelector('.btn-change-commission');
            
            if (!changeBtn) {
                changeBtn = document.createElement('button');
                changeBtn.type = 'button';
                changeBtn.className = 'btn btn-sm btn-warning btn-change-commission';
                changeBtn.style.marginLeft = '10px';
                changeBtn.innerHTML = '<i class="glyphicon glyphicon-edit"></i> Choisir une autre commission';
                
                // Ajouter après le select
                const formContainer = commissionSelect.parentElement;
                formContainer.appendChild(changeBtn);

                // Event listener pour activer le select
                changeBtn.addEventListener('click', function() {
                    commissionSelect.disabled = false;
                    commissionSelect.focus();
                    changeBtn.style.display = 'none';
                });
            }
        }
    }

    /**
     * Gère le changement de commission
     * TODO Phase 2 : Activer/désactiver calendrier date visite selon commission
     */
    function handleCommissionChange(e) {
        const commissionId = e.target.value;

        // Si commission sélectionnée, afficher le bouton "Changer"
        if (commissionId && commissionId !== '') {
            // Désactiver le select
            e.target.disabled = true;
            
            // Afficher le bouton "Changer" s'il existe
            const fieldCommission = document.getElementById('field-commission');
            const changeBtn = fieldCommission ? fieldCommission.querySelector('.btn-change-commission') : null;
            
            if (changeBtn) {
                changeBtn.style.display = '';
            } else {
                // Créer le bouton si première sélection
                createChangeCommissionButton();
            }
        }

        // TODO Phase 2 : Mettre à jour le calendrier date visite
        // updateDateVisiteCalendar(commissionId);
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
