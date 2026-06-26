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
            const isServiceInstructeur = field.id === 'dossier_serviceInstructeur';
            const isPreventionnistes = field.id === 'dossier_preventionnistes';
            
            if (isServiceInstructeur) {
                // Configuration spécifique pour service instructeur avec autocomplete
                new TomSelect(field, {
                    maxItems: 1,
                    valueField: 'libelle',
                    labelField: 'libelle',
                    searchField: 'libelle',
                    placeholder: 'Sélectionnez ou saisissez un service instructeur',
                    preload: true,
                    shouldLoad: function(query) {
                        // Déclencher le preload (query vide) ou dès 2 caractères,
                        // cohérent avec le backend qui bloque les requêtes à 1 caractère
                        return query.length === 0 || query.length >= 2;
                    },
                    
                    load: function(query, callback) {
                        fetch('/api/groupements/search?q=' + encodeURIComponent(query) + '&limit=50')
                            .then(response => response.json())
                            .then(json => {
                                callback(json.map(item => ({
                                    libelle: item.libelle,
                                    type: item.type.libelle || '',
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

                        fetch('/api/utilisateurs/search?q=' + encodeURIComponent(query) + '&limit=100')
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

            new TomSelect ('#dossier_commune', {
                plugins: ['remove_button'],
                maxItems: 1,
                create: false,
                shouldLoad: function(query) {
                    return query.length > 1;
                },
                load: function (query, callback) {
                    const url = Routing.generate('commune_autocomplete', {search: encodeURIComponent(query)});
                    fetch (url)
                        .then(response => response.json())
                        .then(json => {
                            callback(json.map(contact => {
                                let nameParts = []
                                nameParts.push(contact.libelle)

                                return {
                                    id: contact.id,
                                    libelle: nameParts.join(" ")
                                }
                            }))
                        }).catch(() => {
                        callback()
                    })
                }
            })
        });
    }
})();
