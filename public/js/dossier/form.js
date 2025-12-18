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
        initDocumentsUrbanismeHandlers();
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
        
        // Initialiser TomSelect sur les multiselect
        initTomSelectFields();

        // Afficher les champs appropriés au chargement (mode édition)
        updateFieldsVisibility();
        
        // Initialiser les labels d'avis au chargement
        initAvisLabelsOnLoad();
        
        // Appliquer la logique d'affichage des avis selon les checkboxes cochées (au chargement)
        applyHideAvisLogicOnLoad();
        
        // Afficher facteur/échéancier si avis commission = Défavorable (au chargement)
        applyFacteurEcheancierDisplayOnLoad();
        
        // Afficher les boutons calendrier si commission sélectionnée (au chargement)
        showCalendarButtonsOnLoad();
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
            const commissionRow = findFieldRow('commission');
            
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
        
        // Gestion spéciale du champ analyseDeRisque selon le type (comportement legacy)
        applyAnalyseDeRisqueDisplay();
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
            'datePrefecture',
            'dateVisite',
            'documentsUrbanisme',
            'serviceInstructeur',
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
            'chargeSecurite',
            'commission',
            'dateLimitePrescription',
            'dateSecretariat',
            'incomplet',
            'horsDelai',
            'justificationDerogation',
            'mesuresCompensatoires',
            'mesuresComplementaires',
            'reglesDerogation',
            'dateReceptionRvrat',
            'nePeutSePrononcer',
            'operationSdis',
            'rcci',
            'rex',
            'numeroIntervention',
            'dateIntervention',
            'dureeIntervention',
            'dateSignature'
            // Note: analyseDeRisque n'est pas géré dynamiquement (affiché uniquement si valeur existe)
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
     * Reproduit la logique legacy de afficheChamps() lignes 980-997 et afficherCacherAvis()
     * 
     * Logique legacy (ordre de priorité) :
     * 1. Si "incomplet" coché → masquer AVIS, AVIS_COMMISSION, HORSDELAI
     * 2. Sinon si "horsdelai" coché → masquer AVIS, AVIS_COMMISSION, DATECOMM
     * 3. Sinon si "absquorum" ou "npsp" coché → masquer AVIS_COMMISSION, DATECOMM
     * 4. Sinon afficher selon type (standard)
     */
    function applyHideAvisLogicOnLoad() {
        const typeSelect = document.querySelector('#dossier_type');
        const natureSelect = document.querySelector('#dossier_nature');
        if (!typeSelect || !natureSelect) {
            return;
        }

        const typeDossier = typeSelect.value;
        const natureDossier = natureSelect.value;
        
        // Vérifier si les champs sont dans la configuration de la nature
        const natureFields = fieldsConfig[natureDossier] || [];
        const hasAvisInNature = natureFields.includes('avis');
        const hasAvisCommissionInNature = natureFields.includes('avisCommission');
        
        // Récupérer les checkboxes (legacy: lignes 980-984)
        const incompletCheckbox = document.querySelector('input[name="dossier[incomplet]"]:checked');
        const horsdelaiCheckbox = document.querySelector('.horsdelai:checked');
        const absquorumCheckbox = document.querySelector('.absquorum:checked');
        const npspCheckbox = document.querySelector('.npsp:checked');
        
        const avisRow = findFieldRow('avis');
        const avisCommissionRow = findFieldRow('avisCommission');
        const dateCommissionRow = findFieldRow('dateCommission');
        const horsDelaiRow = findFieldRow('horsDelai');

        // Logique par ordre de priorité (legacy: lignes 986-997)
        if (incompletCheckbox && incompletCheckbox.value === '1') {
            // Cas 1: Incomplet → masquer AVIS, AVIS_COMMISSION, HORSDELAI (ligne 987-989)
            if (avisRow) avisRow.style.display = 'none';
            if (avisCommissionRow) avisCommissionRow.style.display = 'none';
            if (horsDelaiRow) horsDelaiRow.style.display = 'none';
        } else if (horsdelaiCheckbox) {
            // Cas 2: Hors délai → masquer AVIS, AVIS_COMMISSION, DATECOMM (ligne 990-993)
            if (avisRow) avisRow.style.display = 'none';
            if (avisCommissionRow) avisCommissionRow.style.display = 'none';
            if (dateCommissionRow) dateCommissionRow.style.display = 'none';
        } else if (absquorumCheckbox || npspCheckbox) {
            // Cas 3: Absence quorum ou NPSP → masquer AVIS_COMMISSION, DATECOMM (ligne 994-996)
            if (avisCommissionRow) avisCommissionRow.style.display = 'none';
            if (dateCommissionRow) dateCommissionRow.style.display = 'none';
            // L'avis du rapporteur reste affiché si dans la nature
            if (avisRow && hasAvisInNature) avisRow.style.display = '';
        } else {
            // Cas 4: Aucune checkbox spéciale → afficher selon type ET nature (standard)
            if (typeDossier === '1') { // Étude
                if (avisRow && hasAvisInNature) avisRow.style.display = '';
                if (avisCommissionRow && hasAvisCommissionInNature) avisCommissionRow.style.display = '';
            } else if (typeDossier === '2') { // Visite
                if (avisRow && hasAvisInNature) avisRow.style.display = '';
                if (avisCommissionRow && hasAvisCommissionInNature) avisCommissionRow.style.display = '';
            } else if (typeDossier === '3') { // Groupe de visites
                if (avisRow && hasAvisInNature) avisRow.style.display = '';
                if (avisCommissionRow && hasAvisCommissionInNature) avisCommissionRow.style.display = '';
            }
        }
    }

    /**
     * Affiche facteur de criticité et échéancier de travaux au chargement si avis commission = Défavorable
     * Phase 1: Affichage statique selon valeur au chargement
     * Phase 2: Sera dynamique (réagira au changement de avisCommission)
     */
    function applyFacteurEcheancierDisplayOnLoad() {
        const avisCommissionSelect = document.querySelector('#dossier_avisCommission');
        if (!avisCommissionSelect) {
            return;
        }

        // Cette logique ne s'applique que pour les visites (type 2/3, natures 20-29)
        const typeSelect = document.querySelector('#dossier_type');
        const natureSelect = document.querySelector('#dossier_nature');
        
        if (!typeSelect || !natureSelect) {
            return;
        }

        const typeDossier = typeSelect.value;
        const natureDossier = natureSelect.value;
        
        // Vérifier si c'est une visite (type 2 ou 3) avec nature entre 20 et 29
        const isVisite = (typeDossier === '2' || typeDossier === '3') && 
                         (parseInt(natureDossier) >= 20 && parseInt(natureDossier) <= 29);
        
        if (!isVisite) {
            // Pour les études et autres types, ne pas afficher ces champs
            const facteurRow = findFieldRow('facteurDangerosite');
            const echeancierRow = findFieldRow('echeancierTravaux');
            if (facteurRow) facteurRow.style.display = 'none';
            if (echeancierRow) echeancierRow.style.display = 'none';
            return;
        }

        const selectedAvisId = avisCommissionSelect.value;
        const facteurRow = findFieldRow('facteurDangerosite');
        const echeancierRow = findFieldRow('echeancierTravaux');

        // ID 2 = Défavorable
        if (selectedAvisId === '2') {
            if (facteurRow) facteurRow.style.display = '';
            if (echeancierRow) echeancierRow.style.display = '';
        } else {
            if (facteurRow) facteurRow.style.display = 'none';
            if (echeancierRow) echeancierRow.style.display = 'none';
        }
    }

    /**
     * Affiche le champ analyseDeRisque pour les types 1, 2 et 3 (comportement legacy JS)
     * Legacy: voir prevarisc/application/views/scripts/dossier/index.phtml lignes 1000-1011
     * 
     * Le champ ANOMALIE n'est jamais dans la liste des champs par nature ($listeChamps),
     * mais il est affiché via JavaScript selon le type de dossier :
     * - Type 1 (Étude) : affiche ANOMALIE
     * - Type 2 (Visite) : affiche ANOMALIE
     * - Type 3 (Groupe de visite) : affiche ANOMALIE
     */
    function applyAnalyseDeRisqueDisplay() {
        const typeSelect = document.querySelector('#dossier_type');
        if (!typeSelect) {
            return;
        }

        const typeDossier = typeSelect.value;
        const analyseDeRisqueRow = findFieldRow('analyseDeRisque');

        if (!analyseDeRisqueRow) {
            return;
        }

        // Afficher pour les types 1, 2 et 3
        if (typeDossier === '1' || typeDossier === '2' || typeDossier === '3') {
            analyseDeRisqueRow.style.display = '';
        } else {
            analyseDeRisqueRow.style.display = 'none';
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

    /**
     * Initialise TomSelect sur les champs multiselect (Phase 1 - sans autocomplete)
     * Phase 2: Ajout de l'autocomplete AJAX
     */
    function initTomSelectFields() {
        const tomSelectFields = document.querySelectorAll('.tomselect-ajax');

        tomSelectFields.forEach(field => {
            // Pour Phase 1, TomSelect simple sans AJAX
            // Les options doivent être déjà présentes dans le select
            new TomSelect(field, {
                plugins: ['remove_button'],
                persist: false,
                create: false,
                maxItems: null  // Pas de limite
            });
        });
    }

    /**
     * Gestion spécifique des documents d'urbanisme
     * Préfixe automatiquement les nouveaux documents selon la nature du dossier
     */
    function initDocumentsUrbanismeHandlers() {
        // Écouter l'événement personnalisé dispatché par add-collection-widget.js
        document.addEventListener('collection:item-added', function(e) {
            const { list, element } = e.detail;
            
            // Vérifier si c'est la collection des documents d'urbanisme
            if (list.id !== 'dossier_documentsUrbanisme') {
                return;
            }
            
            // Récupérer la nature sélectionnée
            const natureSelect = document.querySelector('#dossier_nature');
            if (!natureSelect) {
                return;
            }
            
            const natureId = natureSelect.value;
            const prefix = getDocumentUrbanismePrefix(natureId);
            
            if (prefix) {
                const input = element.querySelector('input[type="text"]');
                if (input && !input.value) {
                    input.value = prefix + ' ';
                    // Positionner le curseur après le préfixe
                    input.focus();
                    input.setSelectionRange(prefix.length + 1, prefix.length + 1);
                }
            }
        });
    }

    /**
     * Retourne le préfixe à appliquer selon la nature du dossier
     * @param {string} natureId - ID de la nature
     * @returns {string|null} - Préfixe ou null si aucun
     */
    function getDocumentUrbanismePrefix(natureId) {
        // Mapping des natures vers leurs préfixes
        const prefixMapping = {
            '1': 'PC',  // Permis de Construire
            '14': 'PA',
            '30': 'DP',
            '62': 'CU'
        };
        
        return prefixMapping[natureId] || null;
    }

    /**
     * Affiche les boutons "Afficher calendrier" si une commission est sélectionnée (au chargement)
     * Legacy: voir index.phtml lignes 367-371 - fonction showCalendar()
     * 
     * Note: Cette fonction sera utile quand les boutons calendrier seront implémentés (Phase future)
     * Pour l'instant, les boutons n'existent pas encore dans le template
     */
    function showCalendarButtonsOnLoad() {
        const commissionSelect = document.querySelector('#dossier_commission');
        if (!commissionSelect || !commissionSelect.value) {
            return;
        }

        // Si une commission est sélectionnée, afficher les boutons calendrier (quand ils existeront)
        const showVisiteButton = document.querySelector('#showVisite');
        const showCommissionButton = document.querySelector('#showCommission');
        
        if (showVisiteButton) {
            showVisiteButton.style.display = '';
        }
        if (showCommissionButton) {
            showCommissionButton.style.display = '';
        }
    }
})();
