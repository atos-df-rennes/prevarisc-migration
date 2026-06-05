describe('Ouverture de rubrique', () => {
    beforeEach(() => {
        cy.login()
    })

    it('Le changement d\'état d\'ouverture d\'une rubrique doit être persisté', () => {
        cy.visit('/rechercher/etablissement')
        cy.get('input[type="submit"][name="Rechercher"]').click()
        cy.get('ul.recherche_liste').contains('a').first().click()

        cy.contains('a', 'Descriptif').click()
        cy.contains('a', 'Modifier le descriptif').click()

        cy.get('.panel-formulaire-perso').first()
            .find('.panel-title__link').first()
            .invoke('attr', 'class')
            .then((classBefore) => {
                const wasCollapsed = classBefore.includes('collapsed')
                const linkAssertion = wasCollapsed ? 'not.have.class' : 'have.class'
                const panelAssertion = wasCollapsed ? 'have.class' : 'not.have.class'

                cy.get('.panel-formulaire-perso').first().find('.panel-title__link').first().click()
                cy.contains('button', 'Enregistrer').click()

                // Vérification sur la page d'affichage
                cy.get('.panel-formulaire-perso').first().find('.panel-title__link').first()
                    .should(linkAssertion, 'collapsed')
                cy.get('.panel-formulaire-perso').first().find('.panel-collapse').first()
                    .should(panelAssertion, 'in')

                // Retour sur la page de modification
                cy.contains('Modifier le descriptif technique').click()

                // Vérification sur la page de modification
                cy.get('.panel-formulaire-perso').first().find('.panel-title__link').first()
                    .should(linkAssertion, 'collapsed')
                cy.get('.panel-formulaire-perso').first().find('.panel-collapse').first()
                    .should(panelAssertion, 'in')
            })
    })
})
