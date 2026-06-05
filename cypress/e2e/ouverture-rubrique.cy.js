describe('test', () => {
    beforeEach(() => {
        cy.login()
    })

    it('Check', () => {
        cy.visit('/rechercher/etablissement')
        cy.get('input[type="submit"][name="Rechercher"]').click()
        cy.get('ul.recherche_liste').contains('a').first().click()
        cy.contains('Descriptif technique').click()
        cy.contains('Modifier le descriptif technique').click()
        cy.get('input[type="hidden"][id^="descriptif_rubrique_"]').first().invoke('val').then((val1) => {
            cy.get('[id^="afficher_rubrique-"]').first().within(() => {
                cy.get('a').click();
            })
            cy.get('button[id="js-btn-enregistrer-descriptif"]').click()
            cy.contains('Modifier le descriptif technique').click()
            cy.get('[id^="afficher_rubrique-"]').first().within(() => {
                cy.get('input[type="hidden"]').first().invoke('val').then((val2) => {
                    expect(val2).not.to.equal(val1)
                })
            })
        })
    })
})