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
        const typeId = parseInt(event.target.value, 10);

        // Réinitialiser la nature
        const natureSelect = document.querySelector('#dossier_nature');
        if (natureSelect) {
            // Vider la sélection de nature
            natureSelect.value = '';
            
            // Charger les natures pour ce type via AJAX
            if (typeId) {
                loadNaturesForType(typeId);
            }
        }

        // Masquer tous les champs conditionnels
        hideAllConditionalFields();
        
        // Afficher/masquer sections selon type (logique legacy lignes 242-288)
        applyTypeSpecificVisibility(typeId);
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
            'dateCommission',
            'documentsUrbanisme',
            'serviceInstructeur',
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
     * @param {string} fieldName - Nom du champ (depuis la configuration JSON)
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
     * @param {string} fieldId - ID du champ (peut être legacy UPPERCASE ou format Symfony dossier_*)
     * @returns {HTMLElement|null}
     */
    function findFieldRow(fieldId) {
        // Extraire le nom du champ (sans préfixe dossier_)
        var fieldName = fieldId.replace('dossier_', '');
        
        // Chercher par ID de ligne direct (format Symfony)
        var row = document.querySelector('#field-' + fieldName);
        if (row) {
            return row;
        }

        // Sinon chercher le champ et remonter au parent li
        var field = document.querySelector('#' + fieldId);
        if (!field) {
            console.debug('Champ non trouvé:', fieldId);
            return null;
        }

        // Remonter jusqu'à la <li> parente
        var parent = field.closest('li.row');
        if (!parent) {
            console.debug('Pas de <li> parent pour:', fieldId);
        }
        return parent;
    }

    /**
     * Applique la visibilité spécifique selon le type de dossier (logique legacy)
     * 
     * Règles :
     * - Type 1 (Étude) : Tous les champs visibles
     * - Type 2 (Visite commission) : Pas de champs commission séparés
     * - Type 3 (Groupe visite) : Pas de champs commission séparés  
     * - Type 4+ (Autres) : Pas de commission ni visite
     * 
     * @param {number} typeId - ID du type de dossier
     */
    function applyTypeSpecificVisibility(typeId) {
        // Les champs commission/visite sont gérés dynamiquement par fieldsConfig
        // Cette fonction ne force que des masquages spécifiques par type
        
        // Note: La logique principale est dans fieldsConfig (backend DossierFieldsService)
        // Le masquage/affichage des champs se fait via updateFieldsVisibility()
        // qui est appelé lors du changement de nature
        
        console.debug('Type de dossier changé:', typeId);
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
     * 2. Sinon si "horsdelai" coché → masquer AVIS, AVIS_COMMISSION, dateCommission
     * 3. Sinon si "absquorum" ou "npsp" coché → masquer AVIS_COMMISSION, dateCommission
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
            // Cas 2: Hors délai → masquer AVIS, AVIS_COMMISSION, dateCommission (ligne 990-993)
            if (avisRow) avisRow.style.display = 'none';
            if (avisCommissionRow) avisCommissionRow.style.display = 'none';
            if (dateCommissionRow) dateCommissionRow.style.display = 'none';
        } else if (absquorumCheckbox || npspCheckbox) {
            // Cas 3: Absence quorum ou NPSP → masquer AVIS_COMMISSION, dateCommission (ligne 994-996)
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
            // Vérifier si c'est le service instructeur (input text avec autocomplete)
            const isServiceInstructeur = field.id === 'dossier_serviceInstructeur';
            
            if (isServiceInstructeur) {
                // Configuration spécifique pour service instructeur avec autocomplete
                new TomSelect(field, {
                    maxItems: 1,
                    valueField: 'libelle',
                    labelField: 'libelle',
                    searchField: 'libelle',
                    placeholder: 'Sélectionnez ou saisissez un service instructeur',
                    
                    load: function(query, callback) {
                        if (!query || query.length < 2) {
                            callback();
                            return;
                        }

                        fetch('/api/groupements/search?q=' + encodeURIComponent(query) + '&limit=50')
                            .then(response => response.json())
                            .then(json => {
                                callback(json.map(item => ({
                                    libelle: item.libelle,
                                    type: item.type || '',
                                })));
                            })
                            .catch(() => {
                                callback();
                            });
                    },
                    
                    render: {
                        option: function(item, escape) {
                            let html = '<div>';
                            html += '<span class="title">' + escape(item.libelle) + '</span>';
                            if (item.type) {
                                html += ' <small class="text-muted">(' + escape(item.type) + ')</small>';
                            }
                            html += '</div>';
                            return html;
                        },
                        item: function(item, escape) {
                            return '<div>' + escape(item.libelle) + '</div>';
                        },
                        option_create: function(data, escape) {
                            return '<div class="create">Ajouter <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                        }
                    },
                    
                    createFilter: function(input) {
                        return input.length >= 2;
                    },
                    
                    create: function(input) {
                        return {
                            libelle: input,
                            type: null,
                        };
                    }
                });
            } else {
                // Configuration pour les autres TomSelect (préventionnistes, etc.)
                // Pour Phase 1, TomSelect simple sans AJAX
                // Les options doivent être déjà présentes dans le select
                new TomSelect(field, {
                    plugins: ['remove_button'],
                    persist: false,
                    create: false,
                    maxItems: null  // Pas de limite
                });
            }
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
            '1': 'PC',
            '2': 'AT',
            '14': 'PA',
            '30': 'DP',
            '62': 'CU'
        };
        
        return prefixMapping[natureId] || null;
    }
})();
