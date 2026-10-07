describe("Indicateur d'état Plat'AU dans l'en-tête", () => {
    beforeEach(() => {
        cy.login()
    })

    it('Le fragment AJAX contient l\'état et l\'heure de dernière vérification uniquement', () => {
        cy.request('/accueil/platau-healthcheck').then((response) => {
            expect(response.headers['content-type']).to.contain('text/html')
            expect(response.headers['cache-control']).to.contain('private')
            expect(response.body).to.match(/platau-status-(ok|warning|error)/)
            expect(response.body).to.contain('Dernière vérification')
            expect(response.body).not.to.contain('Prochaine vérification')
        })
    })

    it('Affiche l\'état en cache directement, sans appel AJAX', () => {
        // Remplit le cache serveur
        cy.request('/accueil/platau-healthcheck')

        cy.intercept('GET', '**/accueil/platau-healthcheck').as('platauStatus')
        cy.visit('/accueil')
        cy.get('#platau-status').should('not.have.attr', 'data-url')
        cy.get('#platau-status-dot').invoke('attr', 'class').should('match', /platau-status-(ok|warning|error)/)
        cy.get('#platau-status > a').click()
        cy.get('#platau-status-checked-at').should('be.visible').and('contain', 'Dernière vérification')
        cy.get('@platauStatus.all').should('have.length', 0)
    })
})
