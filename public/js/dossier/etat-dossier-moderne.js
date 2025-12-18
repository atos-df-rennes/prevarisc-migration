/**
 * Gestion moderne de l'état du dossier avec Symfony Collection Forms
 * 
 * Utilise le pattern Symfony standard pour les collections dynamiques
 * au lieu d'AJAX custom comme dans le legacy
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        initEtatDossierModerne();
    });

    /**
     * Initialise la gestion moderne de l'état du dossier
     */
    function initEtatDossierModerne() {
        const radiosIncomplet = document.querySelectorAll('input[name="dossier[incomplet]"]');
        const dossierIncomplet = document.querySelector('#dossierIncomplet');
        
        if (!radiosIncomplet.length || !dossierIncomplet) {
            return;
        }

        // Listener sur les radios
        radiosIncomplet.forEach(radio => {
            radio.addEventListener('change', handleIncompletChangeModerne);
        });

        // Gestion suppression documents
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-document-manquant') || e.target.closest('.remove-document-manquant')) {
                const button = e.target.classList.contains('remove-document-manquant') ? e.target : e.target.closest('.remove-document-manquant');
                handleRemoveDocument(button);
            }
        });

        // Appliquer l'état initial au chargement
        const radioIncomplet = document.querySelector('input[name="dossier[incomplet]"]:checked');
        if (radioIncomplet) {
            applyInitialState(radioIncomplet.value);
        }

        // Setup du bouton "Ajouter un document" si besoin
        setupAddDocumentButton();
    }

    /**
     * Applique l'état initial au chargement
     */
    function applyInitialState(value) {
        const inputComplet = document.querySelector('#inputComplet');
        const horsDelaiField = document.querySelector('#field-horsDelai');
        const dossierIncomplet = document.querySelector('#dossierIncomplet');

        if (value === '1') {
            // Dossier incomplet au chargement
            if (inputComplet) {
                inputComplet.style.display = 'none';
            }
            if (horsDelaiField) {
                horsDelaiField.style.display = 'none';
            }

            // Si aucun document, ajouter un automatiquement
            const existingDocs = dossierIncomplet.querySelectorAll('.doc-manquant-item');
            if (existingDocs.length === 0) {
                addDocumentFromPrototype();
            }
        } else {
            // Dossier complet au chargement
            if (dossierIncomplet) {
                dossierIncomplet.style.display = 'none';
            }
        }
    }

    /**
     * Gère le changement d'état (moderne - sans AJAX)
     */
    function handleIncompletChangeModerne(e) {
        const value = e.target.value;
        const inputComplet = document.querySelector('#inputComplet');
        const horsDelaiField = document.querySelector('#field-horsDelai');
        const dossierIncomplet = document.querySelector('#dossierIncomplet');

        if (value === '1') {
            // Incomplet sélectionné
            if (horsDelaiField) {
                horsDelaiField.style.display = 'none';
            }

            if (inputComplet) {
                inputComplet.style.display = 'none';
            }

            // Afficher la zone documents
            if (dossierIncomplet) {
                dossierIncomplet.style.display = '';
            }

            // Ajouter un document si aucun n'existe
            const existingDocs = dossierIncomplet.querySelectorAll('.doc-manquant-item');
            if (existingDocs.length === 0) {
                addDocumentFromPrototype();
            }

            // Classe hideavis pour logique masquage avis (Phase 2)
            e.target.classList.add('hideavis');

        } else {
            // Complet sélectionné
            e.target.classList.remove('hideavis');

            if (horsDelaiField) {
                horsDelaiField.style.display = '';
            }

            // Masquer la zone documents
            if (dossierIncomplet) {
                dossierIncomplet.style.display = 'none';
            }
        }
    }

    /**
     * Configure le bouton "Ajouter un document" (pattern Symfony standard)
     */
    function setupAddDocumentButton() {
        const dossierIncomplet = document.querySelector('#dossierIncomplet');
        if (!dossierIncomplet) return;

        // Vérifier si le bouton existe déjà
        if (dossierIncomplet.querySelector('.add-document-button')) {
            return;
        }

        // Créer le bouton d'ajout
        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'btn btn-sm btn-success add-document-button';
        addButton.innerHTML = '<i class="glyphicon glyphicon-plus"></i> Ajouter un document manquant';
        addButton.style.marginBottom = '15px';
        
        addButton.addEventListener('click', function() {
            addDocumentFromPrototype();
        });

        dossierIncomplet.insertBefore(addButton, dossierIncomplet.firstChild);
    }

    /**
     * Ajoute un nouveau document depuis le prototype Symfony
     */
    function addDocumentFromPrototype() {
        const dossierIncomplet = document.querySelector('#dossierIncomplet');
        if (!dossierIncomplet) return;

        const prototype = dossierIncomplet.dataset.prototype;
        let index = parseInt(dossierIncomplet.dataset.index) || 0;

        // Remplacer __name__ par l'index actuel
        const newForm = prototype.replace(/__name__/g, index);

        // Créer un conteneur temporaire pour parser le HTML
        const temp = document.createElement('div');
        temp.innerHTML = newForm;

        // Ajouter avant le bouton d'ajout
        const addButton = dossierIncomplet.querySelector('.add-document-button');
        if (addButton) {
            dossierIncomplet.insertBefore(temp.firstElementChild, addButton.nextSibling);
        } else {
            dossierIncomplet.appendChild(temp.firstElementChild);
        }

        // Incrémenter l'index
        dossierIncomplet.dataset.index = index + 1;

        // Pré-remplir la date du jour
        const newDateInput = dossierIncomplet.querySelector(`[name="dossier[documentsManquants][${index}][date]"]`);
        if (newDateInput) {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            newDateInput.value = `${year}-${month}-${day}`;
        }
    }

    /**
     * Supprime un document
     */
    function handleRemoveDocument(button) {
        const docItem = button.closest('.doc-manquant-item');
        if (docItem) {
            docItem.remove();

            // Si plus aucun document, réafficher les radios et cocher "Complet"
            const dossierIncomplet = document.querySelector('#dossierIncomplet');
            const remainingDocs = dossierIncomplet.querySelectorAll('.doc-manquant-item');
            
            if (remainingDocs.length === 0) {
                const inputComplet = document.querySelector('#inputComplet');
                const horsDelaiField = document.querySelector('#field-horsDelai');
                const radioComplet = document.querySelector('input[name="dossier[incomplet]"][value="0"]');
                
                if (inputComplet) {
                    inputComplet.style.display = '';
                }
                if (horsDelaiField) {
                    horsDelaiField.style.display = '';
                }
                if (radioComplet) {
                    radioComplet.checked = true;
                }
                if (dossierIncomplet) {
                    dossierIncomplet.style.display = 'none';
                }
            }
        }
    }
})();
