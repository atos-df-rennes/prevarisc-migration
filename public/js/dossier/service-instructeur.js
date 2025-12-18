/**
 * Initialisation TomSelect pour le service instructeur.
 * 
 * Autocomplete sur les groupements avec possibilité de saisie libre.
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initServiceInstructeurTomSelect();
    });

    /**
     * Initialise TomSelect sur le champ service instructeur.
     */
    function initServiceInstructeurTomSelect() {
        const selectElement = document.querySelector('#dossier_serviceInstructeur');
        if (!selectElement) {
            return;
        }

        // Récupérer la config depuis l'attribut data
        let config = {
            create: true,
            maxItems: 1,
            plugins: [],
        };

        if (selectElement.dataset.tomselectConfig) {
            try {
                const dataConfig = JSON.parse(selectElement.dataset.tomselectConfig);
                config = { ...config, ...dataConfig };
            } catch (e) {
                console.error('Erreur parsing config TomSelect:', e);
            }
        }

        // Configuration complète TomSelect
        const tomSelectConfig = {
            ...config,
            valueField: 'libelle',
            labelField: 'libelle',
            searchField: 'libelle',
            placeholder: 'Sélectionnez ou saisissez un service instructeur',
            
            // Fonction de chargement des données (autocomplete)
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

            // Template pour afficher les options
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
                }
            },

            // Permettre la création de nouvelles valeurs
            createFilter: function(input) {
                // Accepter toute saisie de plus de 2 caractères
                return input.length >= 2;
            },

            // Message lors de la création
            create: function(input) {
                return {
                    libelle: input,
                    type: null,
                };
            }
        };

        // Initialiser TomSelect
        new TomSelect(selectElement, tomSelectConfig);
    }
})();
