document.addEventListener('DOMContentLoaded', function() {
    const retryExportPec = document.querySelector('#retry-export-pec')

    if (!!retryExportPec) {
        retryExportPec.addEventListener('click', function () {
            $.post(
                "/platau/retry-export-pec",
                { id: retryExportPec['attributes']['data-id'].value },
                () => {
                    location.reload()
                }
            )
        })
    }

    const retryExportAvis = document.querySelector('#retry-export-avis')

    if (!!retryExportAvis) {
        retryExportAvis.addEventListener('click', function () {
            $.post(
                "/platau/retry-export-avis",
                { id: retryExportAvis['attributes']['data-id'].value },
                () => {
                    location.reload()
                }
            )
        })
    }

    // Gestion de l'affichage des pièces jointes Plat'AU
    initGestionPiecesJointesPlatau()
})

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
    // Présence des informations Plat'AU dans la page
    return !!document.querySelector('.dossier__identifiant-platau')
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