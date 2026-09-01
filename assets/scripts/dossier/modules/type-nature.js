/**
 * Module de gestion Type/Nature
 * Responsabilité : Gestion des changements Type/Nature et affichage conditionnel des champs
 */

/**
 * Configuration des champs par nature
 * Sera initialisé depuis les attributs data du formulaire
 * Les noms de champs sont déjà en format Symfony (camelCase)
 */
let fieldsConfig = {};

/**
 * Initialise le module Type/Nature
 * @param {Object} config - Configuration des champs par nature
 */
export function initTypeNatureModule(config) {
    fieldsConfig = config || {};
    
    attachEvents();
    
    // Afficher les champs appropriés au chargement (mode édition)
    updateFieldsVisibility();
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
    
    // Émettre événement personnalisé
    document.dispatchEvent(new CustomEvent('dossier:typeChanged', {
        detail: { typeId: typeId }
    }));
}

/**
 * Gestionnaire du changement de nature
 */
function onNatureChange(event) {
    updateFieldsVisibility();
    
    const natureSelect = document.querySelector('#dossier_nature');
    const typeSelect = document.querySelector('#dossier_type');
    
    if (natureSelect && typeSelect) {
        const natureId = parseInt(natureSelect.value, 10);
        const typeName = typeSelect.options[typeSelect.selectedIndex]?.text || '';
        
        // Émettre événement personnalisé
        document.dispatchEvent(new CustomEvent('dossier:natureChanged', {
            detail: { 
                natureId: natureId,
                typeName: typeName
            }
        }));
    }
}

/**
 * Met à jour la visibilité des champs selon la nature sélectionnée
 */
function updateFieldsVisibility() {
    const natureSelect = document.querySelector('#dossier_nature');
    if (!natureSelect || !natureSelect.value) {
        hideAllConditionalFields();
        applyAnalyseDeRisqueDisplay();
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
    
    // Émettre événement de mise à jour des champs
    document.dispatchEvent(new CustomEvent('dossier:fieldsUpdated', {
        detail: { visibleFields: fieldsToShow }
    }));
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
 * @param {string} fieldId - ID du champ
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
 * Charge les natures disponibles pour un type donné (via AJAX)
 * @param {string} typeId - ID du type de dossier
 */
function loadNaturesForType(typeId) {
    if (!typeId) {
        return;
    }

    // URL de l'endpoint API
    const url = Routing.generate('api_dossier_natures_by_type', {type: typeId});

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

    natureSelect.dispatchEvent(new Event('change'));
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
    const natureSelect = document.querySelector('#dossier_nature');
    if (!typeSelect || !natureSelect) {
        return;
    }

    const typeDossier = typeSelect.value;
    const analyseDeRisqueRow = findFieldRow('analyseDeRisque');

    if (!analyseDeRisqueRow) {
        return;
    }

    const natureDossier = natureSelect.value;

    // Afficher pour les types 1, 2 et 3 si une nature est sélectionnée
    if (
        '' === natureDossier
        || !['1', '2', '3'].includes(typeDossier)
    ) {
        analyseDeRisqueRow.style.display = 'none';
    } else {
        analyseDeRisqueRow.style.display = '';
    }
}

// Exposer les fonctions si nécessaire pour le debug
window.DossierTypeNature = {
    updateFieldsVisibility: updateFieldsVisibility,
    fieldsConfig: function() { return fieldsConfig; }
};
