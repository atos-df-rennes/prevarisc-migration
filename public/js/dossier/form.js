/**
 * Orchestrateur du formulaire d'édition/création de dossier
 * 
 * Initialise et coordonne les modules :
 * - type-nature.js : Gestion Type/Nature et affichage conditionnel
 * - avis.js : Gestion des avis et dérogations
 * - documents-urbanisme.js : Préfixage automatique des documents
 * - today-buttons.js : Boutons "Aujourd'hui" pour les dates
 */

import { initTypeNatureModule } from './modules/type-nature.js';
import { initAvisModule } from './modules/avis.js';
import { initDocumentsUrbanismeModule } from './modules/documents-urbanisme.js';
import { initTodayButtonsModule } from './modules/today-buttons.js';

(function() {
    'use strict';

    /**
     * Configuration des champs par nature
     * Sera initialisé depuis les attributs data du formulaire
     */
    let fieldsConfig = {};

    /**
     * Initialisation au chargement du DOM
     */
    document.addEventListener('DOMContentLoaded', function() {
        initForm();
    });

    /**
     * Initialise le formulaire et ses modules
     */
    function initForm() {
        const form = document.querySelector('#editDossier, #newDossier');
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

        // Initialiser TomSelect (transverse)
        initTomSelectFields();

        // Initialiser les modules
        initTypeNatureModule(fieldsConfig);
        initAvisModule(fieldsConfig);
        initDocumentsUrbanismeModule();
        initTodayButtonsModule();
    }

    /**
     * Initialise TomSelect sur les champs multiselect avec autocomplete AJAX
     * Fonction transverse utilisée par plusieurs modules
     */
    function initTomSelectFields() {
        const tomSelectFields = document.querySelectorAll('.tomselect-ajax');

        tomSelectFields.forEach(field => {
            // Vérifier le type de champ
            const isServiceInstructeur = field.id === 'dossier_serviceInstructeurSelection';
            const isPreventionnistes = field.id === 'dossier_preventionnistes';
            
            if (isServiceInstructeur) {
                const libelleField = document.querySelector('#dossier_serviceInstructeur');
                const typeField = document.querySelector('#dossier_typeServiceInstructeur');
                const initialOption = {
                    value: (typeField.value || 'libre') + ':' + libelleField.value,
                    libelle: libelleField.value,
                    typeServiceInstructeur: typeField.value || null,
                    type: typeField.value === 'servInstCommune' ? 'Commune' : '',
                };

                // Configuration spécifique pour service instructeur avec autocomplete
                new TomSelect(field, {
                    maxItems: 1,
                    valueField: 'value',
                    labelField: 'libelle',
                    searchField: 'libelle',
                    options: libelleField.value ? [initialOption] : [],
                    items: libelleField.value ? [initialOption.value] : [],
                    placeholder: 'Sélectionnez ou saisissez un service instructeur',
                    preload: true,
                    shouldLoad: function(query) {
                        // Déclencher le preload (query vide) ou dès 2 caractères,
                        // cohérent avec le backend qui bloque les requêtes à 1 caractère
                        return query.length === 0 || query.length >= 2;
                    },
                    
                    load: function(query, callback) {
                        const groupementsPromise = fetch(Routing.generate('api_groupements_search', {q: query, limit: 50}))
                            .then(response => response.json())
                            .then(json => json.map(item => ({
                                value: 'servInstGrp:' + item.libelle,
                                libelle: item.libelle,
                                type: item.type.libelle || '',
                                typeServiceInstructeur: 'servInstGrp',
                            })))
                            .catch(() => []);

                        const communesPromise = fetch(Routing.generate('api_adresse_get_communes', {q: query, limit: 50}))
                            .then(response => response.json())
                            .then(json => (json.response || []).map(row => ({
                                value: 'servInstCommune:' + row.LIBELLE_COMMUNE,
                                libelle: row.LIBELLE_COMMUNE,
                                type: 'Commune',
                                typeServiceInstructeur: 'servInstCommune',
                            })))
                            .catch(() => []);

                        Promise.all([groupementsPromise, communesPromise])
                            .then(([groupements, communes]) => {
                                callback(groupements.concat(communes));
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
                            value: 'libre:' + input,
                            libelle: input,
                            type: null,
                            typeServiceInstructeur: null,
                        };
                    },

                    onChange: function(value) {
                        const option = this.options[value];
                        libelleField.value = option ? option.libelle : '';
                        typeField.value = option ? option.typeServiceInstructeur || '' : '';
                    }
                });
            } else if (isPreventionnistes) {
                // Configuration pour préventionnistes avec autocomplete AJAX
                // Conforme au legacy (lignes 81-129)
                
                // TomSelect va automatiquement charger les options déjà présentes dans le <select>
                // En édition, Symfony génère : <option value="1">Nom Prénom</option>
                // On doit donc adapter valueField pour correspondre
                new TomSelect(field, {
                    plugins: ['remove_button'],
                    // Pour les options HTML existantes (édition)
                    valueField: 'value',  // Correspond à l'attribut "value" des <option>
                    labelField: 'text',   // Correspond au texte des <option>
                    searchField: ['text'],
                    placeholder: 'Rechercher un préventionniste (min. 3 caractères)',
                    maxItems: null, // Multiselect illimité
                    
                    load: function(query, callback) {
                        if (!query || query.length < 3) {
                            callback();
                            return;
                        }

                        fetch(Routing.generate('api_utilisateurs_search', {q: query, limit: 100}))
                            .then(response => response.json())
                            .then(json => {
                                // Transformer le format API vers format compatible avec valueField/labelField
                                callback(json.map(item => ({
                                    value: item.id,
                                    text: item.displayLabel
                                })));
                            })
                            .catch(() => {
                                callback();
                            });
                    },
                    
                    render: {
                        option: function(item, escape) {
                            return '<div><span class="title">' + escape(item.text) + '</span></div>';
                        },
                        item: function(item, escape) {
                            return '<div>' + escape(item.text) + '</div>';
                        }
                    }
                });
            } else {
                // Configuration pour les autres TomSelect (fallback simple)
                new TomSelect(field, {
                    plugins: ['remove_button'],
                    persist: false,
                    create: false,
                    maxItems: null
                });
            }
        });
    }
})();
