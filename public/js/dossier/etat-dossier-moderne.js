/**
 * Gestion moderne de l'état du dossier avec Symfony Collection Forms
 * 
 * @description
 * Gère le workflow complet des documents manquants :
 * - Ajout dynamique de nouveaux documents (prototype Symfony)
 * - Validation de réception avec retour automatique à "Complet"
 * - Modification de documents validés
 * - Conservation de l'historique
 * - Gestion conditionnelle des boutons selon l'état
 * 
 * @workflow
 * 1. Complet → Clic "Incomplet" → 1 document manquant ajouté
 * 2. User remplit infos + saisit date réception
 * 3. Clic "Valider la réception" → Retour "Complet" automatique
 * 4. Re-clic "Incomplet" → Nouveau document (historique des incomplets)
 * 
 * @règle
 * 1 seul document "actif" (sans date réception) à la fois
 * 
 * @version 1.0.0 - Production ready
 * @author Migration Prevarisc Team
 */
(function() {
    'use strict';

    // ===================================================================
    // CONSTANTES
    // ===================================================================
    
    const SELECTORS = {
        RADIO_INCOMPLET: 'input[name="dossier[incomplet]"]',
        DOSSIER_INCOMPLET: '#dossierIncomplet',
        INPUT_COMPLET: '#inputComplet',
        FIELD_HORS_DELAI: '#field-horsDelai',
        DOC_MANQUANT_ITEM: '.doc-manquant-item',
        DATE_RECEPTION: '.date-reception',
        DATE_INCOMPLET: '.date-incomplet',
        BTN_VALIDATE: '.validate-reception',
        BTN_MODIFIER: '.modifier-reception',
        BTN_CANCEL: '.cancel-incomplet',
        BTN_TODAY_INCOMPLET: '.btn-today-incomplet',
        BTN_TODAY_RECEPTION: '.btn-today-reception'
    };

    // ===================================================================
    // FONCTIONS UTILITAIRES
    // ===================================================================

    /**
     * Met à jour la visibilité des boutons "Aujourd'hui" selon l'état readonly des champs
     * @param {HTMLElement} docItem - L'élément DOM du document manquant
     */
    function updateTodayButtonsVisibility(docItem) {
        const todayBtns = docItem.querySelectorAll(`${SELECTORS.BTN_TODAY_INCOMPLET}, ${SELECTORS.BTN_TODAY_RECEPTION}`);
        todayBtns.forEach(btn => {
            if (btn.classList.contains('btn-today-incomplet')) {
                btn.style.display = 'none'; // Date incomplet toujours readonly
            } else if (btn.classList.contains('btn-today-reception')) {
                btn.style.display = ''; // Date réception éditable
            }
        });
    }

    /**
     * Crée un bouton dynamiquement
     * @param {string} type - Type de bouton ('valider', 'modifier', 'annuler')
     * @param {HTMLElement} container - Conteneur où ajouter le bouton
     * @returns {HTMLElement} Le bouton créé
     */
    function createButton(type, container) {
        const button = document.createElement('button');
        button.type = 'button';
        
        const configs = {
            valider: {
                className: 'btn btn-sm btn-success validate-reception',
                html: '<i class="glyphicon glyphicon-ok"></i> Valider la réception',
                style: 'margin-left: 10px;'
            },
            modifier: {
                className: 'btn btn-sm btn-warning modifier-reception',
                html: '<i class="glyphicon glyphicon-pencil"></i> Modifier',
                style: 'margin-left: 10px;'
            },
            annuler: {
                className: 'btn btn-sm btn-danger cancel-incomplet',
                html: '<i class="glyphicon glyphicon-remove"></i> Annuler',
                style: 'margin-left: 10px;'
            }
        };
        
        const config = configs[type];
        if (config) {
            button.className = config.className;
            button.innerHTML = config.html;
            button.style.cssText = config.style;
            container.appendChild(button);
        }
        
        return button;
    }

    /**
     * Retourne la date du jour au format YYYY-MM-DD
     * @returns {string}
     */
    function getTodayDate() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // ===================================================================
    // INITIALISATION
    // ===================================================================

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

        // Gestion des boutons (délégation d'événements)
        document.addEventListener('click', function(e) {
            const target = e.target.closest('.validate-reception, .cancel-incomplet, .modifier-reception, .btn-today-incomplet, .btn-today-reception');
            if (!target) return;
            
            // Router vers le bon handler selon la classe du bouton
            if (target.classList.contains('validate-reception')) {
                handleValidateReception(target);
            } else if (target.classList.contains('cancel-incomplet')) {
                handleCancelIncomplet(target);
            } else if (target.classList.contains('modifier-reception')) {
                handleModifierReception(target);
            } else if (target.classList.contains('btn-today-incomplet')) {
                handleTodayIncomplet(target);
            } else if (target.classList.contains('btn-today-reception')) {
                handleTodayReception(target);
            }
        });

        // Appliquer l'état initial au chargement
        const radioIncomplet = document.querySelector('input[name="dossier[incomplet]"]:checked');
        if (radioIncomplet) {
            applyInitialState(radioIncomplet.value);
        }
        
        // Mettre à jour la visibilité des boutons selon le contexte
        updateButtonsVisibility();
    }

    /**
     * Applique l'état initial au chargement
     */
    function applyInitialState(value) {
        const inputComplet = document.querySelector('#inputComplet');
        const horsDelaiField = document.querySelector('#field-horsDelai');
        const dossierIncomplet = document.querySelector('#dossierIncomplet');

        // Marquer les documents validés comme readonly, laisser les non validés éditables
        markValidatedDocumentsReadonly();

        if (value === '1') {
            // Dossier incomplet au chargement
            if (inputComplet) {
                inputComplet.style.display = 'none';
            }
            if (horsDelaiField) {
                horsDelaiField.style.display = 'none';
            }

            // Afficher la zone documents incomplets
            if (dossierIncomplet) {
                dossierIncomplet.style.display = '';
            }
        } else {
            // Dossier complet au chargement
            // Afficher l'historique s'il y a des documents
            const allDocs = dossierIncomplet ? dossierIncomplet.querySelectorAll('.doc-manquant-item') : [];
            if (dossierIncomplet && allDocs.length > 0) {
                dossierIncomplet.style.display = '';
            } else if (dossierIncomplet) {
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
            // Clic sur "Incomplet"
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

            // Ajouter un NOUVEAU document manquant (éditable)
            addDocumentFromPrototype();

            // Classe hideavis pour logique masquage avis (Phase 2)
            e.target.classList.add('hideavis');

        } else {
            // Complet sélectionné manuellement (ne devrait pas arriver via UI normale)
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
     * Gère la validation de réception (clic bouton "Valider la réception")
     */
    function handleValidateReception(button) {
        const docItem = button.closest('.doc-manquant-item');
        if (!docItem) return;

        // Récupérer l'input date de réception
        const dateReceptionInput = docItem.querySelector('.date-reception');
        if (!dateReceptionInput) return;

        // Vérifier qu'une date est saisie
        if (!dateReceptionInput.value) {
            alert('Veuillez saisir une date de réception avant de valider.');
            dateReceptionInput.focus();
            return;
        }

        // Marquer le document comme readonly (validé)
        markDocumentAsReadonly(docItem);
        
        // Enlever le marqueur "nouveau" si présent
        delete docItem.dataset.isNew;

        // Retour automatique à "Complet"
        returnToCompletState();
        
        // Mettre à jour la visibilité des boutons
        updateButtonsVisibility();
    }

    /**
     * Gère l'annulation (clic bouton "Annuler")
     * Conforme legacy : supprime le bloc et retourne à "Complet"
     */
    function handleCancelIncomplet(button) {
        const docItem = button.closest('.doc-manquant-item');
        if (!docItem) return;

        // Supprimer le document du DOM (sans confirmation car c'est un nouveau non sauvegardé)
        docItem.remove();

        // Retour à "Complet"
        returnToCompletState();
        
        // Mettre à jour la visibilité des boutons (notamment "Modifier" sur dernier validé)
        updateButtonsVisibility();
    }

    /**
     * Marque un document comme readonly (historique validé)
     */
    function markDocumentAsReadonly(docItem) {
        // Mettre tous les champs en readonly
        const inputs = docItem.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.readOnly = true;
            input.disabled = false; // Garder enabled pour envoi formulaire
        });

        // Masquer les boutons "Valider", "Annuler" et "Aujourd'hui"
        const validateBtn = docItem.querySelector('.validate-reception');
        const cancelBtn = docItem.querySelector('.cancel-incomplet');
        const todayBtns = docItem.querySelectorAll('.btn-today-incomplet, .btn-today-reception');
        
        if (validateBtn) validateBtn.style.display = 'none';
        if (cancelBtn) cancelBtn.style.display = 'none';
        todayBtns.forEach(btn => btn.style.display = 'none');
    }

    /**
     * Marque SEULEMENT les documents validés comme readonly (au chargement)
     * Les documents sans date de réception restent éditables (sauf date incomplet)
     */
    function markValidatedDocumentsReadonly() {
        const dossierIncomplet = document.querySelector('#dossierIncomplet');
        if (!dossierIncomplet) return;

        const allDocs = dossierIncomplet.querySelectorAll('.doc-manquant-item');
        allDocs.forEach(doc => {
            const dateReceptionInput = doc.querySelector('.date-reception');
            
            if (dateReceptionInput && dateReceptionInput.value) {
                // Document validé → tout en readonly + masquer boutons "Aujourd'hui"
                markDocumentAsReadonly(doc);
            } else {
                // Document non validé → éditable sauf date incomplet
                const inputs = doc.querySelectorAll('input, textarea');
                inputs.forEach(input => {
                    input.readOnly = input.classList.contains('date-incomplet');
                });
                
                // Gérer boutons "Aujourd'hui" selon l'état readonly des champs
                updateTodayButtonsVisibility(doc);
            }
        });
        
        // Mettre à jour la visibilité des boutons après marquage
        updateButtonsVisibility();
    }

    /**
     * Retour automatique à l'état "Complet"
     */
    function returnToCompletState() {
        const inputComplet = document.querySelector('#inputComplet');
        const horsDelaiField = document.querySelector('#field-horsDelai');
        const radioComplet = document.querySelector('input[name="dossier[incomplet]"][value="0"]');
        const dossierIncomplet = document.querySelector('#dossierIncomplet');
        
        // Réafficher les radios
        if (inputComplet) {
            inputComplet.style.display = '';
        }
        
        // Réafficher le champ "Hors délai"
        if (horsDelaiField) {
            horsDelaiField.style.display = '';
        }
        
        // Cocher "Complet"
        if (radioComplet) {
            radioComplet.checked = true;
            radioComplet.classList.remove('hideavis');
        }
        
        // Vérifier s'il y a des documents validés (historique)
        if (dossierIncomplet) {
            const validatedDocs = dossierIncomplet.querySelectorAll('.doc-manquant-item');
            let hasValidatedDocs = false;
            
            validatedDocs.forEach(doc => {
                const dateReceptionInput = doc.querySelector('.date-reception');
                if (dateReceptionInput && dateReceptionInput.value) {
                    hasValidatedDocs = true;
                }
            });
            
            // Afficher la zone SI historique existe, sinon masquer
            if (hasValidatedDocs) {
                dossierIncomplet.style.display = '';
            } else {
                dossierIncomplet.style.display = 'none';
            }
        }
    }

    /**
     * Gère le clic sur bouton "Aujourd'hui" pour date incomplet
     */
    function handleTodayIncomplet(button) {
        const docItem = button.closest('.doc-manquant-item');
        if (!docItem) return;
        
        const dateInput = docItem.querySelector('.date-incomplet');
        if (dateInput) {
            dateInput.value = getTodayDate();
        }
    }

    /**
     * Gère le clic sur bouton "Aujourd'hui" pour date réception
     */
    function handleTodayReception(button) {
        const docItem = button.closest('.doc-manquant-item');
        if (!docItem) return;
        
        const dateInput = docItem.querySelector('.date-reception');
        if (dateInput) {
            dateInput.value = getTodayDate();
        }
    }

    /**
     * Ajoute un nouveau document depuis le prototype Symfony
     */
    async function addDocumentFromPrototype() {
        const dossierIncomplet = document.querySelector('#dossierIncomplet');
        if (!dossierIncomplet) return;

        const prototype = dossierIncomplet.dataset.prototype;
        if (!prototype) {
            console.warn('Prototype collection non trouvé');
            return;
        }

        let index = parseInt(dossierIncomplet.dataset.index) || 0;

        // Remplacer __name__ par l'index actuel
        const newForm = prototype.replace(/__name__/g, index);

        // Créer un conteneur temporaire pour parser le HTML
        const temp = document.createElement('div');
        temp.innerHTML = newForm;

        // Ajouter EN PREMIER (prepend) pour ordre décroissant
        const newElement = temp.firstElementChild;
        if (newElement) {
            // Insérer en premier (avant l'historique si existe, ou comme premier enfant)
            const firstChild = dossierIncomplet.firstElementChild;
            if (firstChild) {
                dossierIncomplet.insertBefore(newElement, firstChild);
            } else {
                dossierIncomplet.appendChild(newElement);
            }

            // Incrémenter l'index
            dossierIncomplet.dataset.index = index + 1;

            // **AJOUTER LE BOUTON ANNULER EN JS** (car absent du prototype FormType)
            const dateIncompletContainer = newElement.querySelector('.date-incomplet-container');
            
            if (dateIncompletContainer) {
                createButton('annuler', dateIncompletContainer);
            } else {
                console.warn('❌ Conteneur date-incomplet-container NON TROUVÉ dans le nouveau document');
            }

            // Pré-remplir la date du jour pour "Dossier incomplet le"
            const newDateInput = newElement.querySelector(`input[name*="[date]"]`);
            if (newDateInput) {
                newDateInput.value = getTodayDate();
            }
            
            // Pré-remplir la liste des documents manquants depuis la BDD
            const documentManquantTextarea = newElement.querySelector('textarea[name*="[documentManquant]"]');
            if (documentManquantTextarea) {
                try {
                    const response = await fetch('/api/document-manquant/liste');
                    if (response.ok) {
                        const documents = await response.json();
                        // Formater la liste comme dans le legacy : un document par ligne
                        documentManquantTextarea.value = documents.map(doc => doc.libelle).join('\n');
                    }
                } catch (error) {
                    console.error('Erreur lors de la récupération des documents manquants:', error);
                }
            }
            
            // MARQUER ce document comme "nouveau" pour différencier de l'historique
            newElement.dataset.isNew = 'true';
            
            // Mettre à jour la visibilité des boutons après ajout
            updateButtonsVisibility();
        }
    }

    /**
     * Gère le bouton "Modifier" (permet de revenir en arrière sur la validation)
     * Conforme legacy : repasse le dossier en incomplet, rend les champs éditables sauf date incomplet
     */
    function handleModifierReception(button) {
        const docItem = button.closest('.doc-manquant-item');
        if (!docItem) return;

        // Remettre tous les champs en éditable SAUF la date de dossier incomplet
        const inputs = docItem.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            // La date de dossier incomplet reste readonly
            input.readOnly = !!(input.name && input.name.includes('[date]') && !input.name.includes('[dateReception]'));
        });

        // Vider la date de réception
        const dateReceptionInput = docItem.querySelector('input[name*="[dateReception]"]');
        if (dateReceptionInput) {
            dateReceptionInput.value = '';
        }

        // Retour à l'état "Incomplet"
        const radioIncomplet = document.querySelector('input[name="dossier[incomplet]"][value="1"]');
        const inputComplet = document.querySelector('#inputComplet');
        const horsDelaiField = document.querySelector('#field-horsDelai');
        const dossierIncomplet = document.querySelector('#dossierIncomplet');

        if (radioIncomplet) {
            radioIncomplet.checked = true;
            radioIncomplet.classList.add('hideavis');
        }

        if (inputComplet) {
            inputComplet.style.display = 'none';
        }

        if (horsDelaiField) {
            horsDelaiField.style.display = 'none';
        }

        if (dossierIncomplet) {
            dossierIncomplet.style.display = '';
        }

        // Masquer "Modifier"
        button.style.display = 'none';
        
        // Créer ou afficher "Valider la réception"
        let validateBtn = docItem.querySelector('.validate-reception');
        const todayBtns = docItem.querySelectorAll('.btn-today-incomplet, .btn-today-reception');

        if (!validateBtn) {
            // Le bouton n'existe pas (rendu avec "Modifier") → le créer
            const buttonContainer = button.parentElement;
            validateBtn = createButton('valider', buttonContainer);
        } else {
            // Le bouton existe déjà → l'afficher
            validateBtn.style.display = '';
        }
        
        // Afficher SEULEMENT le bouton "Aujourd'hui" pour date réception (pas date incomplet qui est readonly)
        updateTodayButtonsVisibility(docItem);
        
        // PAS de bouton "Annuler" sur documents existants (seulement sur nouveaux)
        // Le bouton "Annuler" est uniquement ajouté dans addDocumentFromPrototype()
    }

    /**
     * Met à jour la visibilité des boutons selon les règles legacy :
     * - Bouton "Annuler" : seulement sur le NOUVEAU document ajouté dynamiquement (data-is-new="true")
     * - Bouton "Modifier" : seulement sur le dernier document VALIDÉ (avec date réception)
     * - Si dernier document NON validé (déjà en base) : champs éditables + bouton "Valider réception"
     */
    function updateButtonsVisibility() {
        const dossierIncomplet = document.querySelector('#dossierIncomplet');
        if (!dossierIncomplet) return;

        const allDocs = dossierIncomplet.querySelectorAll('.doc-manquant-item');
        
        // Séparer les documents validés et non validés
        let validatedDocs = [];
        let unvalidatedDocs = [];
        
        allDocs.forEach(doc => {
            const dateReceptionInput = doc.querySelector('input[name*="[dateReception]"]');
            if (dateReceptionInput && dateReceptionInput.value) {
                validatedDocs.push(doc);
            } else {
                unvalidatedDocs.push(doc);
            }
        });

        // Règle 1 : Bouton "Annuler" UNIQUEMENT sur documents sans date réception (template Twig le gère déjà)

        // Règle 2 : Bouton "Modifier" seulement sur le DERNIER document validé (si aucun doc non validé)
        allDocs.forEach(doc => {
            const modifierBtn = doc.querySelector('.modifier-reception');
            if (modifierBtn) {
                modifierBtn.style.display = 'none';
            }
        });
        
        // Si pas de document non validé ET qu'il y a des documents validés → "Modifier" sur le PREMIER validé (plus récent)
        if (unvalidatedDocs.length === 0 && validatedDocs.length > 0) {
            const firstValidated = validatedDocs[0]; // Premier dans le DOM = plus récent (tri controller)
            let modifierBtn = firstValidated.querySelector('.modifier-reception');
            
            if (!modifierBtn) {
                // Le bouton n'existe pas (nouveau doc validé dynamiquement) → le créer
                const validateBtn = firstValidated.querySelector('.validate-reception');
                if (validateBtn) {
                    const buttonContainer = validateBtn.parentElement;
                    modifierBtn = createButton('modifier', buttonContainer);
                    modifierBtn.style.display = '';
                }
            } else {
                // Le bouton existe → l'afficher
                modifierBtn.style.display = '';
            }
        }
        
        // Règle 3 : Si un document non validé existe (déjà en base, pas nouveau) → "Valider réception" visible
        unvalidatedDocs.forEach(doc => {
            const validateBtn = doc.querySelector('.validate-reception');
            const isNew = doc.dataset.isNew === 'true';
            
            if (validateBtn) {
                // Toujours afficher "Valider" sur les docs non validés (nouveau ou pas)
                validateBtn.style.display = '';
            }
            
            // Si pas nouveau (déjà en base) → rendre les champs éditables sauf date incomplet
            if (!isNew) {
                const inputs = doc.querySelectorAll('input, textarea');
                inputs.forEach(input => {
                    input.readOnly = !!(input.name && input.name.includes('[date]') && !input.name.includes('[dateReception]'));
                });
            }
        });
    }

    // ===================================================================
    // GESTION ACCORDÉON HISTORIQUE
    // ===================================================================

    /**
     * Gère l'icône de l'accordéon historique (ouvert/fermé)
     */
    function initHistoriqueAccordion() {
        const histoPanel = document.querySelector('#historique-documents');
        if (!histoPanel) return;

        histoPanel.addEventListener('show.bs.collapse', function() {
            const icon = document.querySelector('[data-target="#historique-documents"] i');
            if (icon) {
                icon.classList.remove('glyphicon-folder-close');
                icon.classList.add('glyphicon-folder-open');
            }
        });

        histoPanel.addEventListener('hide.bs.collapse', function() {
            const icon = document.querySelector('[data-target="#historique-documents"] i');
            if (icon) {
                icon.classList.remove('glyphicon-folder-open');
                icon.classList.add('glyphicon-folder-close');
            }
        });
    }

    // Initialiser l'accordéon au chargement
    document.addEventListener('DOMContentLoaded', function() {
        initHistoriqueAccordion();
    });
})();
