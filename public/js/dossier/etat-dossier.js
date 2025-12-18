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

        // Gestion annulation document (uniquement pour nouveaux documents)
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

        // Appliquer l'état initial au chargement
        const radioIncomplet = document.querySelector('input[name="dossier[incomplet]"]:checked');
        if (radioIncomplet && radioIncomplet.value === '1') {
            // Dossier déjà incomplet au chargement → masquer radios et horsDelai
            const inputComplet = document.querySelector('#inputComplet');
            const horsDelaiField = document.querySelector('#field-horsDelai');
            
            if (inputComplet) {
                inputComplet.style.display = 'none';
            }
            if (horsDelaiField) {
                horsDelaiField.style.display = 'none';
            }
            
            // Si aucun document existant, en afficher un
            const existingDocs = document.querySelectorAll('.docManquant');
            if (existingDocs.length === 0) {
                addDocumentManquant(false); // false = pas de bouton annulation
            }
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
            // Avec bouton annulation car c'est un nouveau document
            addDocumentManquant(true); // true = avec bouton annulation

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
     * @param {boolean} showCancelButton - true si on affiche le bouton annulation (nouveau document), false sinon (document existant)
     * 
     * NOTE: Pour l'instant, utilise un template HTML en dur (mock)
     * TODO: Remplacer par appel AJAX vers /dossier/formdocmanquant quand endpoint créé
     */
    function addDocumentManquant(showCancelButton = true) {
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
        const dateToday = year + '-' + month + '-' + day; // Format ISO pour input type="date"
        
        // Bouton annulation uniquement si nouveau document (pas sur documents existants)
        const cancelButtonHtml = showCancelButton ? `
        <button type='button' class='btn btn-sm btn-danger cancelIncomplet' id='cancelIncomplet_${documentCounter}'>
            <i class='glyphicon glyphicon-remove'></i> Annuler
        </button>` : '';
        
        const html = `
<div id='infosIncomplet_${documentCounter}' class='doc-manquant-bloc' style='margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;'>
    <div style='margin-bottom: 10px;'>
        <strong>Dossier incomplet le</strong>
        <input class='form-control' style='display: inline-block; width: 150px; margin: 0 5px;' type='date' name='dateDocManquant[]' id='dateDocManquant_${documentCounter}' value='${dateToday}' />
        &nbsp;&nbsp;
        ${cancelButtonHtml}
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
        <input class='form-control' style='display: inline-block; width: 150px; margin: 0 5px;' type='date' name='dateReceptionDocManquant[]' id='dateReceptionDocManquant_${documentCounter}' />
        &nbsp;&nbsp;
        <button type='button' class='btn btn-sm btn-success validDocManquant'>
            <i class='glyphicon glyphicon-lock'></i> Valider la réception de document
        </button>
    </div>
</div>`;
        
        const container = document.querySelector('#dossierIncomplet');
        if (container) {
            container.insertAdjacentHTML('beforeend', html);
        }
    }

    /**
     * Gère l'annulation d'un document manquant (nouveau document non validé)
     * Legacy: lignes 350-357
     */
    function handleCancelDocument(button) {
        const container = button.closest('[id^="infosIncomplet_"]');
        if (container) {
            container.remove();
            documentCounter--;
        }
        
        // Réafficher les radios (legacy : ligne 352)
        const inputComplet = document.querySelector('#inputComplet');
        if (inputComplet) {
            inputComplet.style.display = '';
        }
        
        // Repasser à "Complet" (legacy : ligne 353)
        const radioComplet = document.querySelector('input[name="dossier[incomplet]"][value="0"]');
        if (radioComplet) {
            radioComplet.checked = true;
            radioComplet.classList.remove('hideavis');
        }
        
        // Réafficher les avis (legacy : lignes 354-355)
        // afficherCacherAvis(0); - sera géré en Phase 2
    }

    /**
     * Gère la validation de la réception d'un document
     * Legacy: lignes 319-336
     * 
     * Repasse automatiquement le dossier en "Complet" et réaffiche les avis
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
        }
        if (dateReception) {
            dateReception.readOnly = true;
        }
        if (textarea) {
            textarea.readOnly = true;
        }

        // Masquer le bouton Valider et afficher bouton "Annuler la validation" (legacy : lignes 328-329)
        button.style.display = 'none';
        
        // Créer le bouton "Annuler la validation"
        const cancelValidationBtn = document.createElement('button');
        cancelValidationBtn.type = 'button';
        cancelValidationBtn.className = 'btn btn-sm btn-warning cancelDateRecep';
        cancelValidationBtn.innerHTML = '<i class="glyphicon glyphicon-repeat"></i> Annuler la validation';
        button.parentNode.insertBefore(cancelValidationBtn, button.nextSibling);
        
        // Listener sur le bouton d'annulation de validation (legacy : lignes 338-348)
        cancelValidationBtn.addEventListener('click', function() {
            // Remettre en éditable (legacy : lignes 339-342)
            if (dateDocManquant) {
                dateDocManquant.readOnly = false;
            }
            if (dateReception) {
                dateReception.readOnly = false;
                dateReception.value = ''; // Vider la date
            }
            if (textarea) {
                textarea.readOnly = false;
            }
            
            // Masquer ce bouton et réafficher "Valider"
            cancelValidationBtn.style.display = 'none';
            button.style.display = '';
            
            // Repasser à "Incomplet" (legacy : lignes 343-344)
            const radioIncomplet = document.querySelector('input[name="dossier[incomplet]"][value="1"]');
            if (radioIncomplet) {
                radioIncomplet.checked = true;
                radioIncomplet.classList.add('hideavis');
            }
            
            // Masquer les radios et Hors délai (legacy : ligne 345)
            const inputComplet = document.querySelector('#inputComplet');
            if (inputComplet) {
                inputComplet.style.display = 'none';
            }
            
            // Masquer les avis (legacy : ligne 346)
            // afficherCacherAvis(0); - sera géré en Phase 2
        });

        // Retirer la classe hideavis pour réafficher les avis (legacy : ligne 330)
        const radiosIncomplet = document.querySelectorAll('input[name="dossier[incomplet]"]');
        radiosIncomplet.forEach(radio => radio.classList.remove('hideavis'));
        
        // Repasser automatiquement à "Complet" (legacy : lignes 332-333)
        const inputComplet = document.querySelector('#inputComplet');
        const horsDelaiField = document.querySelector('#field-horsDelai');
        const radioComplet = document.querySelector('input[name="dossier[incomplet]"][value="0"]');
        
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
        }
        
        // Réafficher les avis
        // afficherCacherAvis(); - sera géré en Phase 2
    }
})();
