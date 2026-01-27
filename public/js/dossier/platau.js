document.addEventListener('DOMContentLoaded', function() {
    // Bouton Retry PEC
    const retryExportPec = document.querySelector('#retry-export-pec');
    if (retryExportPec) {
        retryExportPec.addEventListener('click', function(e) {
            e.preventDefault();
            handleRetryExport('pec', this);
        });
    }

    // Bouton Retry Avis
    const retryExportAvis = document.querySelector('#retry-export-avis');
    if (retryExportAvis) {
        retryExportAvis.addEventListener('click', function(e) {
            e.preventDefault();
            handleRetryExport('avis', this);
        });
    }

    // Gestion de l'affichage des pièces jointes Plat'AU
    initGestionPiecesJointesPlatau();
});

/**
 * Gère le clic sur un bouton retry export (PEC ou Avis)
 * @param {string} type - Type d'export ('pec' ou 'avis')
 * @param {HTMLElement} button - Bouton cliqué
 */
function handleRetryExport(type, button) {
    // 1. Mettre à jour le champ hidden du formulaire
    const hiddenFieldName = type === 'pec' ? 'dossier[platauRetryPec]' : 'dossier[platauRetryAvis]';
    const hiddenField = document.querySelector(`input[name="${hiddenFieldName}"]`);
    if (hiddenField) {
        hiddenField.value = '1';
    }

    // 2. Récupérer le nouveau label depuis l'attribut data
    const nouveauLabel = button.getAttribute('data-label-nouveau');
    
    // 3. Changer le statut affiché
    const statutId = type === 'pec' ? 'statut-pec' : 'statut-avis';
    const statutElement = document.getElementById(statutId);
    if (statutElement && nouveauLabel) {
        updateStatutLabel(statutElement, nouveauLabel);
    }

    // 4. Afficher le bon badge (alerte si pièces disponibles, info sinon)
    const badgeAlerte = document.getElementById('badge-alerte-platau');
    const badgeInfo = document.getElementById('badge-info-platau');
    const fieldPiecesJointes = document.getElementById('field-piecesJointesPlatau');
    
    // Déterminer s'il y a des pièces jointes à sélectionner
    const hasPiecesJointes = fieldPiecesJointes && fieldPiecesJointes.querySelectorAll('input[type="checkbox"]').length > 0;
    
    if (hasPiecesJointes && badgeAlerte && badgeAlerte.classList.contains('hidden')) {
        // Afficher badge d'alerte (pièces à sélectionner)
        badgeAlerte.classList.remove('hidden');
    } else if (!hasPiecesJointes && badgeInfo && badgeInfo.classList.contains('hidden')) {
        // Afficher badge informatif (aucune pièce disponible)
        badgeInfo.classList.remove('hidden');
    }

    // 5. Afficher le bloc pièces jointes (si caché)
    if (fieldPiecesJointes && fieldPiecesJointes.classList.contains('hidden')) {
        // Retirer classe hidden directement (ne pas utiliser toggle qui inverserait)
        fieldPiecesJointes.classList.remove('hidden');
        
        // Déplacer le bloc après l'élément de référence selon le type
        const afterElement = (type === 'pec') 
            ? document.getElementById('field-incomplet') 
            : document.getElementById('field-avisCommission');
        
        if (afterElement && afterElement.nextElementSibling !== fieldPiecesJointes) {
            afterElement.parentNode.insertBefore(fieldPiecesJointes, afterElement.nextElementSibling);
        }
    }

    // 6. Désactiver le bouton et changer son texte
    button.disabled = true;
    button.innerHTML = '<span class="glyphicon glyphicon-ok"></span> Renvoi demandé';
    button.classList.remove('btn-warning');
    button.classList.add('btn-default');
}

/**
 * Met à jour le label de statut affiché
 * @param {HTMLElement} statutElement - Élément <span id="statut-pec"> ou <span id="statut-avis">
 * @param {string} nouveauLabel - Nouveau texte à afficher
 */
function updateStatutLabel(statutElement, nouveauLabel) {
    // Changer le texte du span
    statutElement.textContent = nouveauLabel;
    
    // Changer la couleur : retirer les classes existantes et ajouter text-warning
    statutElement.classList.remove('text-muted', 'text-success', 'text-info', 'text-danger');
    statutElement.classList.add('text-warning');
}

/**
 * Initialise la gestion de l'affichage du bloc de pièces jointes Plat'AU
 * Affiche/masque le bloc selon les conditions (avis commission, incomplet, etc.)
 */
function initGestionPiecesJointesPlatau() {
    // Vérifie si c'est un dossier Plat'AU (présence des infos Plat'AU)
    if (!isDossierPlatau()) {
        return
    }

    const fieldPiecesJointes = document.getElementById('field-piecesJointesPlatau')
    const badgeAlerte = document.getElementById('badge-alerte-platau')

    // Si le champ n'existe pas, pas besoin d'aller plus loin
    if (!fieldPiecesJointes) {
        return
    }

    // Gestion bouton incomplet
    const btnIncomplet = document.querySelector('#dossier_incomplet_1')
    if (btnIncomplet) {
        btnIncomplet.addEventListener('change', function() {
            const fieldIncomplet = document.getElementById('field-incomplet')
            if (fieldIncomplet) {
                affichePiecesJointesPlatau(fieldIncomplet, fieldPiecesJointes, badgeAlerte)
            }
        })
    }

    // Délégation d'événements pour le bouton "Annuler" (ajouté dynamiquement)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.cancel-incomplet')) {
            masquePiecesJointesPlatau(fieldPiecesJointes, badgeAlerte)
        }
    })

    // Gestion changement d'avis de la commission
    const selectAvisCommission = document.getElementById('dossier_avisCommission')
    if (selectAvisCommission) {
        selectAvisCommission.addEventListener('change', function() {
            const avis = this.value
            const fieldAvisCommission = document.getElementById('field-avisCommission')

            if (avis && avis !== '0' && avis !== '') {
                if (fieldAvisCommission && fieldPiecesJointes.classList.contains('hidden')) {
                    affichePiecesJointesPlatau(fieldAvisCommission, fieldPiecesJointes, badgeAlerte)
                }
            } else {
                masquePiecesJointesPlatau(fieldPiecesJointes, badgeAlerte)
            }
        })
    }

    // Gestion du clic sur le lien du badge d'alerte
    const linkSelectPlatau = document.getElementById('select-platau')
    if (linkSelectPlatau) {
        linkSelectPlatau.addEventListener('click', function(e) {
            e.preventDefault()
            fieldPiecesJointes.scrollIntoView({ behavior: 'smooth', block: 'center' })
            fieldPiecesJointes.focus()
        })
    }

    // Affichage initial si statut en warning (dossier incomplet ou avis en attente)
    const statutPec = document.getElementById('statut-pec')
    if (statutPec && statutPec.classList.contains('text-warning')) {
        const fieldIncomplet = document.getElementById('field-incomplet')
        if (fieldIncomplet) {
            affichePiecesJointesPlatau(fieldIncomplet, fieldPiecesJointes, badgeAlerte)
        }
    }

    const statutAvis = document.getElementById('statut-avis')
    if (statutAvis && statutAvis.classList.contains('text-warning')) {
        const fieldAvisCommission = document.getElementById('field-avisCommission')
        if (fieldAvisCommission) {
            affichePiecesJointesPlatau(fieldAvisCommission, fieldPiecesJointes, badgeAlerte)
        }
    }
}

/**
 * Vérifie si le dossier est lié à Plat'AU
 * @return {boolean}
 */
function isDossierPlatau() {
    // Présence des informations Plat'AU dans la page (attribut data-platau-info)
    return !!document.querySelector('[data-platau-info]')
}

/**
 * Affiche le bloc de pièces jointes Plat'AU après un élément donné
 * @param {HTMLElement} afterElement Element après lequel insérer le bloc
 * @param {HTMLElement} fieldPiecesJointes Le bloc de pièces jointes
 * @param {HTMLElement} badgeAlerte Le badge d'alerte
 */
function affichePiecesJointesPlatau(afterElement, fieldPiecesJointes, badgeAlerte) {
    // Affiche le bloc de pièces jointes
    fieldPiecesJointes.classList.toggle('hidden')

    // Affiche le badge d'alerte si présent
    if (badgeAlerte) {
        badgeAlerte.classList.toggle('hidden')
    }

    // Déplace le bloc après l'élément spécifié si nécessaire
    if (afterElement && afterElement.nextElementSibling !== fieldPiecesJointes) {
        afterElement.parentNode.insertBefore(fieldPiecesJointes, afterElement.nextElementSibling)
    }
}

/**
 * Masque le bloc de pièces jointes Plat'AU
 * @param {HTMLElement} fieldPiecesJointes Le bloc de pièces jointes
 * @param {HTMLElement} badgeAlerte Le badge d'alerte
 */
function masquePiecesJointesPlatau(fieldPiecesJointes, badgeAlerte) {
    fieldPiecesJointes.classList.toggle('hidden')

    if (badgeAlerte) {
        badgeAlerte.classList.toggle('hidden')
    }
}