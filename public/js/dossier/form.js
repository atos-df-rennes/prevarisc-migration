/**
 * Gestion du formulaire d'édition/création de dossier
 * 
 * Gère l'affichage dynamique des champs selon le type et la nature sélectionnés.
 * Utilise les métadonnées des champs fournies par le serveur.
 */

(function() {
    'use strict';

    /**
     * Configuration des champs par nature
     * Sera initialisé depuis les attributs data du formulaire
     * Les noms de champs sont déjà en format Symfony (camelCase)
     */
    let fieldsConfig = {};

    /**
     * Initialisation au chargement du DOM
     */
    document.addEventListener('DOMContentLoaded', function() {
        initForm();
    });

    /**
     * Initialise le formulaire
     */
    function initForm() {
        const form = document.querySelector('#editDossier');
        if (!form) {
            console.warn('Formulaire dossier non trouvé');
            return;
        }

        // Récupérer la configuration depuis l'attribut data
        const fieldsConfigElement = document.querySelector('[data-fields-config]');
        if (fieldsConfigElement) {
            try {
                fieldsConfig = JSON.parse(fieldsConfigElement.dataset.fieldsConfig);
            } catch (e) {
                console.error('Erreur de parsing de la configuration des champs', e);
                fieldsConfig = {};
            }
        }

        // Attacher les événements (préparé pour Phase 2)
        attachEvents();
        
        // Initialiser les boutons "Aujourd'hui"
        initTodayButtons();

        // Afficher les champs appropriés au chargement (mode édition)
        updateFieldsVisibility();
        
        // Initialiser les labels d'avis au chargement
        initAvisLabelsOnLoad();
        
        // Appliquer la logique d'affichage des avis selon les checkboxes cochées (au chargement)
        applyHideAvisLogicOnLoad();
    }

    /**
     * PHASE 2 - Comportements conditionnels spécifiques (désactivés pour Phase 1)
     * Ces fonctions seront réactivées lors de la Phase 2 pour gérer les interactions dynamiques
     */
    /* 
    function initConditionalBehaviors() {
        handleAvisCommissionChange();
        handleCommissionForVisites();
    }
    */

    /**
     * PHASE 2 - Gestion de l'affichage des champs conditionnels (désactivé pour Phase 1)
     */
    /*
    function handleAvisCommissionChange() {
        const avisCommissionSelect = document.querySelector('#dossier_avisCommission');
        if (!avisCommissionSelect) {
            return;
        }

        function toggleFacteurEcheancier() {
            const selectedAvisId = avisCommissionSelect.value;
            const facteurRow = findFieldRow('dossier_facteurDangerosite');
            const echeancierRow = findFieldRow('dossier_echeancierTravaux');

            // ID 2 = Défavorable
            if (selectedAvisId === '2') {
                if (facteurRow) facteurRow.style.display = '';
                if (echeancierRow) echeancierRow.style.display = '';
            } else {
                if (facteurRow) facteurRow.style.display = 'none';
                if (echeancierRow) echeancierRow.style.display = 'none';
                // Réinitialiser les valeurs
                const facteurInput = document.querySelector('#dossier_facteurDangerosite');
                const echeancierInput = document.querySelector('#dossier_echeancierTravaux');
                if (facteurInput) facteurInput.value = '';
                if (echeancierInput) echeancierInput.value = '';
            }
        }

        avisCommissionSelect.addEventListener('change', toggleFacteurEcheancier);
        // Déclencher au chargement
        toggleFacteurEcheancier();
    }
    */

    /**
     * Désactive le champ commission pour les visites (types 2 et 3)
     */
    function handleCommissionForVisites() {
        const typeSelect = document.querySelector('#dossier_type');
        const commissionSelect = document.querySelector('#dossier_commission');
        
        if (!typeSelect || !commissionSelect) {
            return;
        }

        function toggleCommission() {
            const typeId = typeSelect.value;
            const commissionRow = findFieldRow('dossier_commission');
            
            // Types 2 et 3 = Visites
            if (typeId === '2' || typeId === '3') {
                commissionSelect.disabled = true;
                commissionSelect.classList.add('disabled-field');
                
                // Ajouter message d'aide s'il n'existe pas
                if (commissionRow && !commissionRow.querySelector('.commission-help')) {
                    const helpText = document.createElement('small');
                    helpText.className = 'form-text text-muted commission-help';
                    helpText.textContent = 'La commission est liée à la date de visite. Modifier la date de visite pour changer de commission.';
                    const widgetDiv = commissionRow.querySelector('.right.form');
                    if (widgetDiv) {
                        widgetDiv.appendChild(helpText);
                    }
                }
            } else {
                commissionSelect.disabled = false;
                commissionSelect.classList.remove('disabled-field');
                
                // Retirer message d'aide
                if (commissionRow) {
                    const helpText = commissionRow.querySelector('.commission-help');
                    if (helpText) {
                        helpText.remove();
                    }
                }
            }
        }

        typeSelect.addEventListener('change', toggleCommission);
        // Initial
        toggleCommission();
    }

    /**
     * Attache les gestionnaires d'événements
     */
    function attachEvents() {
        const typeSelect = document.querySelector('#dossier_type');
        const natureSelect = document.querySelector('#dossier_nature');

        if (typeSelect) {
            typeSelect.addEventListener('change', onTypeChange);
        }

        if (natureSelect) {
            natureSelect.addEventListener('change', onNatureChange);
        }
    }

    /**
     * Gestionnaire du changement de type
     */
    function onTypeChange(event) {
        const typeId = event.target.value;

        // Réinitialiser la nature
        const natureSelect = document.querySelector('#dossier_nature');
        if (natureSelect) {
            // Charger les natures pour ce type via AJAX
            loadNaturesForType(typeId);
        }

        // Masquer tous les champs conditionnels
        hideAllConditionalFields();
    }

    /**
     * Gestionnaire du changement de nature
     */
    function onNatureChange(event) {
        updateFieldsVisibility();
    }

    /**
     * Met à jour la visibilité des champs selon la nature sélectionnée
     */
    function updateFieldsVisibility() {
        const natureSelect = document.querySelector('#dossier_nature');
        if (!natureSelect || !natureSelect.value) {
            hideAllConditionalFields();
            return;
        }

        const natureId = natureSelect.value;
        const fieldsToShow = fieldsConfig[natureId] || [];

        // Masquer tous les champs
        hideAllConditionalFields();

        // Afficher les champs appropriés (noms déjà en format Symfony)
        fieldsToShow.forEach(function(fieldName) {
            showField(fieldName);
        });
    }

    /**
     * Masque tous les champs conditionnels
     */
    function hideAllConditionalFields() {
        // Liste des champs potentiellement conditionnels
        const conditionalFields = [
            'dateInsert',
            'objet',
            'dateReunion',
            'dateReponse',
            'dateReception',
            'dateMairie',
            'dateEnvoiTransit',
            'dateTransfertCommission',
            'dateReceptionCommission',
            'dateVisite',
            'dateCommission',
            'avis',
            'avisCommission',
            'avisDiffere',
            'absenceDeQuorum',
            'facteurDangerosite',
            'echeancierTravaux',
            'preventionnistes',
            'demandeur',
            'lieuReunion',
            'observations',
            'descriptifGeneral',
            'descriptifEffectifs',
            'coordonneesSsi',
            'commission',
            'dateLimitePrescription',
            // Ajouter d'autres champs au fur et à mesure
        ];

        conditionalFields.forEach(function(fieldName) {
            hideField(fieldName);
        });
    }

    /**
     * Affiche un champ du formulaire
     * @param {string} fieldName - Nom du champ (correspond au nom de la propriété dans Dossier)
     */
    function showField(fieldName) {
        const fieldId = 'dossier_' + fieldName;
        const fieldRow = findFieldRow(fieldId);
        
        if (fieldRow) {
            fieldRow.style.display = '';
        }
    }

    /**
     * Masque un champ du formulaire
     * @param {string} fieldName - Nom du champ
     */
    function hideField(fieldName) {
        const fieldId = 'dossier_' + fieldName;
        const fieldRow = findFieldRow(fieldId);
        
        if (fieldRow) {
            fieldRow.style.display = 'none';
        }
    }

    /**
     * Trouve la ligne (<li>) contenant un champ
     * @param {string} fieldId - ID du champ
     * @returns {HTMLElement|null}
     */
    function findFieldRow(fieldId) {
        // D'abord chercher par ID de ligne direct
        let row = document.querySelector('#field-' + fieldId.replace('dossier_', ''));
        if (row) {
            return row;
        }

        // Sinon chercher le champ et remonter au parent li
        const field = document.querySelector('#' + fieldId);
        if (!field) {
            console.debug('Champ non trouvé:', fieldId);
            return null;
        }

        // Remonter jusqu'à la <li> parente
        let parent = field.closest('li.row');
        if (!parent) {
            console.debug('Pas de <li> parent pour:', fieldId);
        }
        return parent;
    }

    /**
     * Charge les natures disponibles pour un type donné (via AJAX)
     * @param {string} typeId - ID du type de dossier
     */
    function loadNaturesForType(typeId) {
        if (!typeId) {
            return;
        }

        // URL de l'endpoint API (à créer)
        const url = '/api/dossier/natures-by-type/' + typeId;

        fetch(url)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des natures');
                }
                return response.json();
            })
            .then(function(data) {
                updateNatureSelect(data);
            })
            .catch(function(error) {
                console.error('Erreur:', error);
            });
    }

    /**
     * Met à jour le select des natures
     * @param {Array} natures - Liste des natures {id, libelle}
     */
    function updateNatureSelect(natures) {
        const natureSelect = document.querySelector('#dossier_nature');
        if (!natureSelect) {
            return;
        }

        // Vider le select (sauf le placeholder)
        const placeholderOption = natureSelect.querySelector('option[value=""]');
        natureSelect.innerHTML = '';
        if (placeholderOption) {
            natureSelect.appendChild(placeholderOption);
        }

        // Ajouter les nouvelles options
        natures.forEach(function(nature) {
            const option = document.createElement('option');
            option.value = nature.id;
            option.textContent = nature.libelle;
            natureSelect.appendChild(option);
        });

        // Activer le select
        natureSelect.disabled = false;
    }

    // Exposer les fonctions si nécessaire pour le debug
    window.DossierForm = {
        updateFieldsVisibility: updateFieldsVisibility,
        fieldsConfig: function() { return fieldsConfig; }
    };

    /**
     * PHASE 2 - Comportements des checkboxes (désactivé pour Phase 1)
     * Seront réactivés lors de la Phase 2
     */
    /*
    function initCheckboxBehaviors() {
        const absenceQuorumCheckbox = document.querySelector('#dossier_absenceDeQuorum');
        if (absenceQuorumCheckbox) {
            absenceQuorumCheckbox.addEventListener('change', handleHideAvisCheckboxes);
        }
    }

    function handleHideAvisCheckboxes() {
        // Logique à implémenter en Phase 2
    }
    */

    /**
     * Initialise les labels d'avis au chargement de la page (Phase 1 - statique)
     * Note: En Phase 2, cette fonction deviendra dynamique (réagira aux changements de type/nature)
     */
    function initAvisLabelsOnLoad() {
        const typeSelect = document.querySelector('#dossier_type');
        const natureSelect = document.querySelector('#dossier_nature');
        
        if (!typeSelect || !natureSelect) {
            return;
        }

        const typeDossier = typeSelect.value;
        const natureDossier = natureSelect.value;
        let addText = '';

        if (typeDossier === '1') { // Étude
            if (['19', '7', '17', '16'].includes(natureDossier)) {
                addText = ' à l\'exploitation';
            } else {
                addText = ' à l\'étude';
            }
        } else if (typeDossier === '2' || typeDossier === '3') { // Visite ou Groupe de visites
            if (['20', '25'].includes(natureDossier)) {
                addText = ' à la réception des travaux';
            } else if (['21', '23', '24', '26', '28', '29'].includes(natureDossier)) {
                addText = ' à l\'exploitation';
            } else {
                addText = ' à l\'exploitation';
            }
        }

        // Mettre à jour les options des selects avis (une seule fois au chargement)
        const avisSelect = document.querySelector('#dossier_avis');
        const avisCommissionSelect = document.querySelector('#dossier_avisCommission');

        [avisSelect, avisCommissionSelect].forEach(select => {
            if (!select) return;
            
            Array.from(select.options).forEach(option => {
                if (option.value === '' || option.value === '0') return;
                
                // Extraire le texte de base (sans suffixe)
                let baseText = option.text.replace(/ à l'.*$/, '').replace(/ à la.*$/, '');
                
                if (option.value === '6') {
                    // "Pas d'avis" - pas de suffixe
                    option.text = baseText;
                } else {
                    option.text = baseText + addText;
                }
            });
        });
    }

    /**
     * Applique la logique d'affichage/masquage des avis au chargement (Phase 1 - statique)
     * Reproduit la fonction afficherCacherAvis() du legacy mais sans les listeners dynamiques
     * 
     * Logique legacy :
     * - Si aucune checkbox .hideavis cochée → affiche selon type
     * - Si au moins une checkbox .hideavis cochée :
     *   - Si c'est autre que absquorum/npsp → cache AVIS rapporteur
     *   - Si c'est seulement absquorum/npsp → affiche AVIS rapporteur
     *   - Dans tous les cas → cache AVIS_COMMISSION
     */
    function applyHideAvisLogicOnLoad() {
        const typeSelect = document.querySelector('#dossier_type');
        if (!typeSelect) {
            return;
        }

        const typeDossier = typeSelect.value;
        
        // Sélectionner toutes les checkboxes .hideavis cochées
        const hideAvisCheckboxes = document.querySelectorAll('.hideavis:checked');
        
        const avisRow = findFieldRow('dossier_avis');
        const avisCommissionRow = findFieldRow('dossier_avisCommission');

        if (hideAvisCheckboxes.length === 0) {
            // Aucune checkbox hideavis cochée → afficher selon type
            if (typeDossier === '1') { // Étude
                if (avisRow) avisRow.style.display = '';
                if (avisCommissionRow) avisCommissionRow.style.display = '';
            } else if (typeDossier === '2') { // Visite
                if (avisCommissionRow) avisCommissionRow.style.display = '';
            } else if (typeDossier === '3') { // Groupe de visites
                if (avisRow) avisRow.style.display = '';
                if (avisCommissionRow) avisCommissionRow.style.display = '';
            }
        } else {
            // Au moins une checkbox hideavis est cochée
            
            // Vérifier si ce sont d'autres checkboxes que absquorum/npsp
            let hasOtherThanAbsquorumNpsp = false;
            hideAvisCheckboxes.forEach(checkbox => {
                if (!checkbox.classList.contains('absquorum') && !checkbox.classList.contains('npsp')) {
                    hasOtherThanAbsquorumNpsp = true;
                }
            });
            
            if (hasOtherThanAbsquorumNpsp) {
                // Cache l'avis du rapporteur
                if (avisRow) {
                    avisRow.style.display = 'none';
                }
            } else {
                // Seulement absquorum/npsp → affiche l'avis du rapporteur
                if (avisRow) {
                    avisRow.style.display = '';
                }
            }
            
            // Dans tous les cas, cache l'avis de la commission
            if (avisCommissionRow) {
                avisCommissionRow.style.display = 'none';
            }
        }
    }

    /**
     * Initialise les boutons "Aujourd'hui" pour les champs date
     */
    function initTodayButtons() {
        const todayButtons = document.querySelectorAll('.btn-today');
        
        todayButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target');
                if (targetId) {
                    const dateInput = document.querySelector('#' + targetId);
                    if (dateInput) {
                        // Format YYYY-MM-DD pour input type="date"
                        const today = new Date();
                        const yyyy = today.getFullYear();
                        const mm = String(today.getMonth() + 1).padStart(2, '0');
                        const dd = String(today.getDate()).padStart(2, '0');
                        dateInput.value = `${yyyy}-${mm}-${dd}`;
                    }
                }
            });
        });
    }

})();
