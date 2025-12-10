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

        // Attacher les événements
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
            'dateReception',
            'dateVisite',
            'avisCommission',
            'avisDiffere',
            'facteurDangerosite',
            'preventionnistes',
            'demandeur',
            'lieuReunion',
            'observationDossier',
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

})();
