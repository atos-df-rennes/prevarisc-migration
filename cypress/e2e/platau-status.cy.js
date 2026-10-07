describe("Indicateur d'état Plat'AU dans l'en-tête", () => {
    const stub = (status, extra = {}) => {
        cy.intercept('GET', '**/accueil/platau-healthcheck', {
            success: status !== 'error',
            status,
            message: status === 'error' ? "Plat'AU est en erreur" : "La connexion à Plat'AU a fonctionnée.",
            version: null,
            maintenance: null,
            checkedAt: '2026-01-01T15:52:00+01:00',
            nextCheckAt: '2026-01-01T15:57:00+01:00',
            ...extra,
        }).as('platauStatus')
    }

    beforeEach(() => {
        cy.login()
    })

    it('Affiche une pastille verte et les horaires de vérification', () => {
        stub('ok')
        cy.visit('/accueil')
        cy.wait('@platauStatus')
        cy.get('#platau-status-dot').should('have.class', 'platau-status-ok')
        cy.get('#platau-status > a').click()
        cy.get('#platau-status-times').should('be.visible').and('contain', 'Prochaine vérification')
    })

    it('Affiche une pastille orange en cas de maintenance', () => {
        stub('warning', { maintenance: { message: '<strong>Maintenance en cours</strong>' } })
        cy.visit('/accueil')
        cy.wait('@platauStatus')
        cy.get('#platau-status-dot').should('have.class', 'platau-status-warning')
    })

    it('Affiche une pastille rouge en cas d\'erreur', () => {
        stub('error')
        cy.visit('/accueil')
        cy.wait('@platauStatus')
        cy.get('#platau-status-dot').should('have.class', 'platau-status-error')
    })
})
