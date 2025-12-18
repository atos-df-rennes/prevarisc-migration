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
                const button = e.target.classList.contains('validate-reception') ? e.target : e.target.closest('.validate-reception');
                handleValidateReception(button);
            }
            
            // Bouton "Annuler"
            if (e.target.classList.contains('cancel-incomplet') || e.target.closest('.cancel-incomplet')) {
                const button = e.target.classList.contains('cancel-incomplet') ? e.target : e.target.closest('.cancel-incomplet');
                handleCancelIncomplet(button);
            }
        });

        // Appliquer l'état initial au chargement
        const radioIncomplet = document.querySelector('input[name="dossier[incomplet]"]:checked');
        if (radioIncomplet) {
            applyInitialState(radioIncomplet.value);
        }
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

            // Vérifier s'il existe un document actif (sans date réception)
            const activeDoc = getActiveDocument();
            
            if (!activeDoc) {
                // Aucun document actif → En ajouter un
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

            // Ajouter un nouveau document manquant
            // (le dernier doit avoir une date réception pour arriver ici après retour "Complet")
            addDocumentFromPrototype();

            // Classe hideavis pour logique masquage avis (Phase 2)
            e.target.classList.add('hideavis');

        } else {
            // Complet sélectionné manuellement
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

        // Valider le document
        markDocumentAsValidated(docItem, dateReceptionInput.value);

        // Retour automatique à "Complet"
        returnToCompletState();
    }

    /**
     * Gère l'annulation (clic bouton "Annuler")
     */
    function handleCancelIncomplet(button) {
        const docItem = button.closest('.doc-manquant-item');
        if (!docItem) return;

        // Vérifier si c'est un nouveau document (pas en BDD)
        // Un document en BDD a forcément un input avec un ID existant
        const dateInput = docItem.querySelector('input[name*="[date]"]');
        
        // Confirmer la suppression
        if (!confirm('Êtes-vous sûr de vouloir annuler ce signalement de document manquant ?')) {
            return;
        }

        // Supprimer le document du DOM
        docItem.remove();

        // Retour à "Complet"
        returnToCompletState();
    }

    /**
     * Marque un document comme validé (date réception renseignée)
     */
    function markDocumentAsValidated(docItem, dateValue) {
        // Désactiver tous les champs du document
        const inputs = docItem.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.readOnly = true;
            input.disabled = false; // Garder enabled pour envoi formulaire
        });

        // Masquer les boutons
        const validateBtn = docItem.querySelector('.validate-reception');
        const cancelBtn = docItem.querySelector('.cancel-incomplet');
        if (validateBtn) validateBtn.style.display = 'none';
        if (cancelBtn) cancelBtn.style.display = 'none';

        // Afficher un message de confirmation
        const dateReceptionContainer = docItem.querySelector('.date-reception-container');
        if (dateReceptionContainer) {
            const existingMsg = dateReceptionContainer.querySelector('.reception-confirmed');
            if (!existingMsg) {
                const confirmMsg = document.createElement('div');
                confirmMsg.className = 'reception-confirmed text-success';
                confirmMsg.style.marginTop = '10px';
                
                // Formatter la date en français
                const [year, month, day] = dateValue.split('-');
                confirmMsg.innerHTML = `<i class="glyphicon glyphicon-ok"></i> Document reçu le ${day}/${month}/${year}`;
                
                dateReceptionContainer.appendChild(confirmMsg);
            }
        }
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
     * Récupère le document actif (sans date de réception)
     */
    function getActiveDocument() {
        const dossierIncomplet = document.querySelector('#dossierIncomplet');
        if (!dossierIncomplet) return null;

        const docs = dossierIncomplet.querySelectorAll('.doc-manquant-item');
        for (let doc of docs) {
            const dateReceptionInput = doc.querySelector('input[name*="[dateReception]"]');
            if (dateReceptionInput && !dateReceptionInput.value) {
                return doc;
            }
        }
        return null;
    }

    /**
     * Ajoute un nouveau document depuis le prototype Symfony
     */
    function addDocumentFromPrototype() {
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

            // Pré-remplir la date du jour pour "Dossier incomplet le"
            const newDateInput = newElement.querySelector(`input[name*="[date]"]`);
            if (newDateInput) {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                newDateInput.value = `${year}-${month}-${day}`;
            }
        }
    }
})();
