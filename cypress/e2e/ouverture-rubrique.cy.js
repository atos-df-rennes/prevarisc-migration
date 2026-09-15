// Tests fonctionnels — Mémorisation de l'état d'ouverture des rubriques d'un descriptif personnalisé
// URL testée : /etablissement/{id}/descriptif/modifier
// Objectif : vérifier que l'état ouverte/repliée choisi par l'utilisateur (collapse Bootstrap)
// est persisté à l'enregistrement du formulaire et réappliqué à la ré-ouverture de la page
// d'édition, indépendamment de la valeur par défaut administrable (RG-FOR-028).
describe('Descriptif personnalisé — mémorisation de l\'état d\'ouverture des rubriques', () => {
    beforeEach(() => {
        cy.login()
    })

    /**
     * Récupère l'identifiant du premier établissement retourné par la recherche,
     * puis navigue vers la page d'édition de son descriptif personnalisé.
     */
    function naviguerVersEditionDescriptif() {
        cy.request('/rechercher/etablissement?Rechercher=Rechercher').then((res) => {
            const match = res.body.match(/\/etablissement\/(\d+)\/informations/)
            expect(match, 'Au moins un établissement doit exister en base').to.not.be.null

            const etablissementId = parseInt(match[1], 10)
            cy.visit(`/etablissement/${etablissementId}/descriptif/modifier`)
        })
    }

    it('Bascule le collapse d\'une rubrique et enregistre le nouvel état', () => {
        naviguerVersEditionDescriptif()

        cy.get('body').then(($body) => {
            const rubriquePanels = $body.find('.panel-formulaire-perso')

            if (0 === rubriquePanels.length) {
                cy.log('Aucune rubrique configurée pour cet établissement — test ignoré')

                return
            }

            const premierPanel = rubriquePanels.first()
            const rubriqueId = premierPanel.attr('id').replace('afficher_rubrique-', '')
            const toggle = () => cy.get(`#afficher_rubrique-${rubriqueId} .panel-title__link`)
            const checkboxCachee = () => cy.get(`[data-opened-checkbox="${rubriqueId}"]`)

            // État initial (dépend de la préférence déjà enregistrée / valeur par défaut admin)
            checkboxCachee().then(($checkbox) => {
                const etaitOuverte = $checkbox.is(':checked')

                // Bascule le collapse Bootstrap : aria-expanded est la source de vérité,
                // la checkbox cachée n'est renseignée qu'au submit.
                toggle().click()
                toggle().should('have.attr', 'aria-expanded', etaitOuverte ? 'false' : 'true')

                // Enregistre le formulaire avec le nouvel état
                cy.get('#js-btn-enregistrer-descriptif').click()
                cy.url().should('include', '/descriptif')
                cy.url().should('not.include', '/modifier')
            })
        })
    })

    it('Rouvre la page d\'édition et vérifie que l\'état d\'ouverture persiste', () => {
        naviguerVersEditionDescriptif()

        cy.url().then((editUrl) => {
            cy.get('body').then(($body) => {
                const rubriquePanels = $body.find('.panel-formulaire-perso')

                if (0 === rubriquePanels.length) {
                    cy.log('Aucune rubrique configurée pour cet établissement — test ignoré')

                    return
                }

                const premierPanel = rubriquePanels.first()
                const rubriqueId = premierPanel.attr('id').replace('afficher_rubrique-', '')
                const checkboxCachee = () => cy.get(`[data-opened-checkbox="${rubriqueId}"]`)

                checkboxCachee().then(($checkbox) => {
                    const etatAvantFermeture = $checkbox.is(':checked')

                    // Force l'état inverse via le collapse, enregistre, puis recharge la page d'édition
                    cy.get(`#afficher_rubrique-${rubriqueId} .panel-title__link`)
                        .click()
                        .should('have.attr', 'aria-expanded', etatAvantFermeture ? 'false' : 'true')
                    cy.get('#js-btn-enregistrer-descriptif').click()
                    cy.url().should('not.include', '/modifier')

                    // Revisite explicitement (nouvelle requête serveur, pas de bfcache) la page d'édition
                    cy.visit(editUrl)

                    // La page rechargée doit refléter l'état sauvegardé (et non la valeur par défaut admin)
                    checkboxCachee().should(etatAvantFermeture ? 'not.be.checked' : 'be.checked')
                    cy.get(`#rubrique-edit-body-${rubriqueId}`).should(
                        etatAvantFermeture ? 'not.have.class' : 'have.class',
                        'in'
                    )
                })
            })
        })
    })
})
