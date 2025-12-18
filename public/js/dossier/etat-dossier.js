/**
 * Gestion de l'état du dossier (Complet / Incomplet) et des documents manquants.
 * 
 * Comportement legacy (index.phtml lignes 290-357) :
 * - Radio "Incomplet" → Affiche zone d'ajout documents manquants (AJAX)
 * - Radio "Complet" → Masque la zone
 * - Validation document → Lecture seule + affichage message réception
 * - Annulation → Supprime le bloc document
 */

(function() {
    'use strict';

    let documentCounter = 0;

    document.addEventListener('DOMContentLoaded', function() {
        initEtatDossier();
    });

    /**
     * Initialise la gestion de l'état du dossier
     */
    function initEtatDossier() {
        const radiosIncomplet = document.querySelectorAll('input[name="dossier[incomplet]"]');
        
        if (!radiosIncomplet.length) {
            return;
        }

        // Compter les documents existants
        documentCounter = document.querySelectorAll('.docManquant').length;

        // Listener sur les radios
        radiosIncomplet.forEach(radio => {
            radio.addEventListener('change', handleIncompletChange);
        });

        // Gestion annulation document
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('cancelIncomplet') || e.target.closest('.cancelIncomplet')) {
                const button = e.target.classList.contains('cancelIncomplet') ? e.target : e.target.closest('.cancelIncomplet');
                handleCancelDocument(button);
            }
        });

        // Gestion validation document
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('validDocManquant') || e.target.closest('.validDocManquant')) {
                const button = e.target.classList.contains('validDocManquant') ? e.target : e.target.closest('.validDocManquant');
                handleValidDocument(button);
            }
        });

        // Appliquer l'état initial
        const radioIncomplet = document.querySelector('input[name="dossier[incomplet]"]:checked');
        if (radioIncomplet && radioIncomplet.value === '1') {
            // Si déjà incomplet au chargement, ne rien faire (documents existants déjà affichés)
        }
    }

    /**
     * Gère le changement de l'état du dossier (Complet / Incomplet)
     */
    function handleIncompletChange(e) {
        const value = e.target.value;
        const inputComplet = document.querySelector('#inputComplet');
        const horsDelaiField = document.querySelector('#field-horsDelai');

        if (value === '1') {
            // Incomplet sélectionné
            
            // Masquer le champ "Hors délai" (legacy : ligne 296)
            if (horsDelaiField) {
                horsDelaiField.style.display = 'none';
            }

            // Masquer les radios (legacy : ligne 298)
            if (inputComplet) {
                inputComplet.style.display = 'none';
            }

            // Ajouter un formulaire de document manquant via AJAX
            addDocumentManquant();

            // Ajouter classe hideavis (legacy : ligne 293)
            e.target.classList.add('hideavis');
            
            // Déclencher le masquage des avis (sera géré dans form.js Phase 2)
            // afficherCacherAvis();

        } else {
            // Complet sélectionné
            
            // Retirer classe hideavis (legacy : ligne 314)
            e.target.classList.remove('hideavis');
            
            // Réafficher "Hors délai" si nécessaire
            if (horsDelaiField) {
                horsDelaiField.style.display = '';
            }

            // Déclencher l'affichage des avis (sera géré dans form.js Phase 2)
            // afficherCacherAvis();
        }
    }

    /**
     * Ajoute un nouveau formulaire de document manquant (AJAX)
     * Legacy: lignes 299-311
     * 
     * NOTE: Pour l'instant, utilise un template HTML en dur (mock)
     * TODO: Remplacer par appel AJAX vers /dossier/formdocmanquant quand endpoint créé
     */
    function addDocumentManquant() {
        documentCounter++;
        
        // const dossierId = document.querySelector('input[name="dossier[id]"]')?.value || '';
        
        // TODO: Décommenter quand l'endpoint sera créé
        /*
        fetch('/dossier/formdocmanquant', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'numDoc=' + documentCounter + '&idDossier=' + encodeURIComponent(dossierId)
        })
        .then(response => response.text())
        .then(html => {
            const container = document.querySelector('#dossierIncomplet');
            if (container) {
                container.insertAdjacentHTML('beforeend', html);
                initDatepickers();
                initTodayButtons();
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'ajout du document manquant:', error);
        });
        */
        
        // MOCK TEMPORAIRE : HTML généré en dur pour tester l'UX
        const today = new Date();
        const day = String(today.getDate()).padStart(2, '0');
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const year = today.getFullYear();
        const dateToday = day + '/' + month + '/' + year;
        
        const html = `
<div id='infosIncomplet_${documentCounter}' class='doc-manquant-bloc' style='margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;'>
    <div style='margin-bottom: 10px;'>
        <strong>Dossier incomplet le</strong>
        <input class='date form-control' style='display: inline-block; width: 120px; margin: 0 5px;' type='text' name='dateDocManquant[]' id='dateDocManquant_${documentCounter}' value='${dateToday}' />
        <button type='button' class='today btn btn-sm btn-default'><i class='glyphicon glyphicon-calendar'></i> Aujourd'hui</button>
        &nbsp;&nbsp;
        <button type='button' class='btn btn-sm btn-danger cancelIncomplet' id='cancelIncomplet_${documentCounter}'>
            <i class='glyphicon glyphicon-remove'></i> Annuler
        </button>
    </div>
    
    <div style='margin-bottom: 10px;'>
        <label>Documents manquants :</label>
        <textarea name='docManquant[]' class='docManquant form-control' id='docManquant_${documentCounter}' rows='4' placeholder='Liste des documents manquants...'>Plan de masse
Plan des niveaux
Coupe verticale
Notice de sécurité
Attestation de conformité</textarea>
    </div>
    
    <div>
        <strong>Date de réception des pièces</strong>
        <input class='date form-control' style='display: inline-block; width: 120px; margin: 0 5px;' type='text' name='dateReceptionDocManquant[]' id='dateReceptionDocManquant_${documentCounter}' />
        <button type='button' class='today btn btn-sm btn-default'><i class='glyphicon glyphicon-calendar'></i> Aujourd'hui</button>
        &nbsp;&nbsp;
        <button type='button' class='btn btn-sm btn-success validDocManquant'>
            <i class='glyphicon glyphicon-lock'></i> Valider la réception de document
        </button>
    </div>
</div>`;
        
        const container = document.querySelector('#dossierIncomplet');
        if (container) {
            container.insertAdjacentHTML('beforeend', html);
            initTodayButtons();
        }
    }

    /**
     * Gère l'annulation d'un document manquant
     * Legacy: lignes 344-354
     */
    function handleCancelDocument(button) {
        const container = button.closest('[id^="infosIncomplet_"]');
        if (container) {
            container.remove();
            documentCounter--;
        }
    }

    /**
     * Gère la validation de la réception d'un document
     * Legacy: lignes 319-342
     */
    function handleValidDocument(button) {
        const container = button.closest('[id^="infosIncomplet_"]');
        if (!container) return;

        // Récupérer les champs
        const dateDocManquant = container.querySelector('input[name="dateDocManquant[]"]');
        const dateReception = container.querySelector('input[name="dateReceptionDocManquant[]"]');
        const textarea = container.querySelector('textarea.docManquant');

        // Vérifier que la date de réception est renseignée
        if (!dateReception || !dateReception.value) {
            alert('Veuillez saisir la date de réception des pièces');
            dateReception?.focus();
            return;
        }

        // Mettre en lecture seule (legacy : lignes 325-327)
        if (dateDocManquant) {
            dateDocManquant.readOnly = true;
            dateDocManquant.classList.remove('date');
        }
        if (dateReception) {
            dateReception.readOnly = true;
            dateReception.classList.remove('date');
        }
        if (textarea) {
            textarea.readOnly = true;
        }

        // Masquer le bouton Valider (legacy : ligne 328)
        button.style.display = 'none';

        // Afficher un message de confirmation (au lieu du bouton "Annuler")
        const confirmMsg = document.createElement('span');
        confirmMsg.className = 'text-success';
        confirmMsg.innerHTML = '<i class="glyphicon glyphicon-ok"></i> Document reçu le ' + dateReception.value;
        button.parentNode.insertBefore(confirmMsg, button.nextSibling);

        // Retirer la classe hideavis pour réafficher les avis (legacy : ligne 330)
        const radiosIncomplet = document.querySelectorAll('input[name="dossier[incomplet]"]');
        radiosIncomplet.forEach(radio => radio.classList.remove('hideavis'));
    }

    /**
     * Initialise les datepickers sur les champs date (appelé après AJAX)
     */
    function initDatepickers() {
        // Sera géré par le plugin datepicker global si présent
        // Ou à implémenter selon la bibliothèque utilisée
    }

    /**
     * Initialise les boutons "Aujourd'hui" sur les nouveaux champs
     */
    function initTodayButtons() {
        const buttons = document.querySelectorAll('.today:not(.initialized)');
        buttons.forEach(button => {
            button.classList.add('initialized');
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const input = this.previousElementSibling;
                if (input && input.type === 'text') {
                    const today = new Date();
                    const day = String(today.getDate()).padStart(2, '0');
                    const month = String(today.getMonth() + 1).padStart(2, '0');
                    const year = today.getFullYear();
                    input.value = day + '/' + month + '/' + year;
                }
            });
        });
    }
})();
