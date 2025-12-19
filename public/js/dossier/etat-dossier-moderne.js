/**
 * Gestion moderne de l'état du dossier avec Symfony Collection Forms
 * 
 * Workflow legacy :
 * 1. Complet → Clic "Incomplet" → 1 document manquant ajouté
 * 2. User remplit infos + saisit date réception
 * 3. Clic "Valider la réception" → Retour "Complet" automatique
 * 4. Re-clic "Incomplet" → Nouveau document (historique des incomplets)
 * 
 * Règle : 1 seul document "actif" (sans date réception) à la fois
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

        // Gestion des boutons (délégation d'événements)
        document.addEventListener('click', function(e) {
            // Bouton "Valider la réception"
            if (e.target.classList.contains('validate-reception') || e.target.closest('.validate-reception')) {
                e.preventDefault();
                const button = e.target.classList.contains('validate-reception') ? e.target : e.target.closest('.validate-reception');
                handleValidateReception(button);
            }
            
            // Bouton "Annuler" (cancelIncomplet)
            if (e.target.classList.contains('cancel-incomplet') || e.target.closest('.cancel-incomplet')) {
                e.preventDefault();
                const button = e.target.classList.contains('cancel-incomplet') ? e.target : e.target.closest('.cancel-incomplet');
                handleCancelIncomplet(button);
            }
            
            // Bouton "Modifier" (cancelDateRecep - repasser en éditable)
            if (e.target.classList.contains('modifier-reception') || e.target.closest('.modifier-reception')) {
                e.preventDefault();
                const button = e.target.classList.contains('modifier-reception') ? e.target : e.target.closest('.modifier-reception');
                handleModifierReception(button);
            }
            
            // Boutons "Aujourd'hui" pour date incomplet
            if (e.target.classList.contains('btn-today-incomplet') || e.target.closest('.btn-today-incomplet')) {
                e.preventDefault();
                const button = e.target.classList.contains('btn-today-incomplet') ? e.target : e.target.closest('.btn-today-incomplet');
                handleTodayIncomplet(button);
            }
            
            // Boutons "Aujourd'hui" pour date réception
            if (e.target.classList.contains('btn-today-reception') || e.target.closest('.btn-today-reception')) {
                e.preventDefault();
                const button = e.target.classList.contains('btn-today-reception') ? e.target : e.target.closest('.btn-today-reception');
                handleTodayReception(button);
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
                    if (input.classList.contains('date-incomplet')) {
                        // Date incomplet toujours readonly
                        input.readOnly = true;
                    } else {
                        // Autres champs éditables
                        input.readOnly = false;
                    }
                });
                
                // S'assurer que les boutons "Aujourd'hui" sont visibles pour documents non validés
                const todayBtns = doc.querySelectorAll('.btn-today-incomplet, .btn-today-reception');
                todayBtns.forEach(btn => btn.style.display = '');
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
        
        // Masquer la zone documents (mais conserver les documents validés dans le DOM pour la sauvegarde)
        if (dossierIncomplet) {
            dossierIncomplet.style.display = 'none';
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
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            dateInput.value = `${year}-${month}-${day}`;
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
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            dateInput.value = `${year}-${month}-${day}`;
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

        // Ajouter à la fin de la zone documents
        const newElement = temp.firstElementChild;
        if (newElement) {
            dossierIncomplet.appendChild(newElement);

            // Incrémenter l'index
            dossierIncomplet.dataset.index = index + 1;

            // **AJOUTER LE BOUTON ANNULER EN JS** (car absent du prototype FormType)
            const dateIncompletContainer = newElement.querySelector('.date-incomplet-container');
            console.log('DEBUG: Recherche .date-incomplet-container:', dateIncompletContainer);
            console.log('DEBUG: Contenu newElement:', newElement.innerHTML.substring(0, 300));
            
            if (dateIncompletContainer) {
                const annulerBtn = document.createElement('button');
                annulerBtn.type = 'button';
                annulerBtn.className = 'btn btn-sm btn-danger cancel-incomplet';
                annulerBtn.style.marginLeft = '10px';
                annulerBtn.innerHTML = '<i class="glyphicon glyphicon-remove"></i> Annuler';
                dateIncompletContainer.appendChild(annulerBtn);
                console.log('DEBUG: Bouton Annuler ajouté avec succès');
            } else {
                console.warn('❌ Conteneur date-incomplet-container NON TROUVÉ dans le nouveau document');
                console.log('DEBUG: Classes disponibles dans newElement:', 
                    Array.from(newElement.querySelectorAll('*'))
                        .filter(el => el.className)
                        .map(el => el.className)
                        .join(', ')
                );
            }

            // Pré-remplir la date du jour pour "Dossier incomplet le"
            const newDateInput = newElement.querySelector(`input[name*="[date]"]`);
            if (newDateInput) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                newDateInput.value = `${year}-${month}-${day}`;
            }
            
            // Pré-remplir la liste des documents manquants depuis la BDD
            const documentManquantTextarea = newElement.querySelector('textarea[name*="[documentManquant]"]');
            if (documentManquantTextarea) {
                try {
                    const response = await fetch('/api/document-manquant/liste');
                    if (response.ok) {
                        const documents = await response.json();
                        // Formater la liste comme dans le legacy : un document par ligne
                        const listeDocuments = documents.map(doc => doc.libelle).join('\n');
                        documentManquantTextarea.value = listeDocuments;
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
            if (input.name && input.name.includes('[date]') && !input.name.includes('[dateReception]')) {
                input.readOnly = true;
            } else {
                input.readOnly = false;
            }
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
        
        // Afficher "Valider la réception" ET "Aujourd'hui"
        const validateBtn = docItem.querySelector('.validate-reception');
        const todayBtns = docItem.querySelectorAll('.btn-today-incomplet, .btn-today-reception');
        
        if (validateBtn) {
            validateBtn.style.display = '';
        }
        todayBtns.forEach(btn => btn.style.display = '');
        
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
        
        // Si pas de document non validé ET qu'il y a des documents validés → "Modifier" sur le dernier validé
        if (unvalidatedDocs.length === 0 && validatedDocs.length > 0) {
            const lastValidated = validatedDocs[validatedDocs.length - 1];
            const modifierBtn = lastValidated.querySelector('.modifier-reception');
            if (modifierBtn) {
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
                    if (input.name && input.name.includes('[date]') && !input.name.includes('[dateReception]')) {
                        input.readOnly = true;
                    } else {
                        input.readOnly = false;
                    }
                });
            }
        });
    }
})();
