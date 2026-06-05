describe('test', () => {
    beforeEach(() => {
        cy.login()
    })

    it('Check', () => {
        cy.visit('/rechercher/etablissement')
        cy.get('input[type="submit"][name="Rechercher"]').click()
        cy.get('ul.recherche_liste').contains('a').first().click()

        cy.contains('a', 'Descriptif').click()
        cy.contains('a', 'Modifier le descriptif').click()

        cy.get('.panel-formulaire-perso').first().find('input[type="hidden"][id$="_opened"]').first().invoke('val').then((valueBefore) => {
            cy.get('.panel-formulaire-perso').first().find('.panel-title__link').first().click()
            cy.contains('button', 'Enregistrer').click()

            cy.contains('Modifier le descriptif technique').click()
            cy.get('.panel-formulaire-perso').first().find('input[type="hidden"][id$="_opened"]').first().invoke('val').then((valueAfter) => {
                expect(valueAfter).not.to.equal(valueBefore)
            })
        })
    })
})