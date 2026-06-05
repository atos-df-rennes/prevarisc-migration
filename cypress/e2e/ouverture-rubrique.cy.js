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
//        cy.get('[id^="afficher_rubrique-"]').first().within(() => {
//            cy.get('input[type="hidden"]').first().invoke('val').then((val1) => {
//                cy.get('[id^="afficher_rubrique-"]').first().within(() => {
//                    cy.get('a');
//                })
//                cy.get('button[id="js-btn-enregistrer-descriptif"]').click()
//                cy.contains('Modifier le descriptif technique').click()
//                cy.get('[id^="afficher_rubrique-"]').first().within(() => {
//                    cy.get('input[type="hidden"]').first().invoke('val').then((val2) => {
//                        expect(val2).not.to.equal(val1)
//                    })
//                })
//            })
//        })
//    })
//})


//        cy.get('input[type="hidden"]').first().invoke('val').then((val1) => {
//            cy.get('[id^="afficher_rubrique-"]').first().within(() => {
//                cy.get('a')
//            })
//            cy.get('button[id="js-btn-enregistrer-descriptif"]').click()
//            cy.contains('Modifier le descriptif technique').click()
//            cy.get('[id^="afficher_rubrique-"]').first().within(() => {
//                cy.get('a');cy.get('a');
//            })
//            cy.get('input[type="hidden"]').first().invoke('val').then((val2) => {
//                expect(val2).not.to.equal(val1)
//            })
//            })
//        })
        //cy.url().should('include', '/modifier')
        //cy.get('input[type="hidden"]').first().invoke('val').as('ouverture')
//        cy.get('[id^="afficher_rubrique-"]').first().within(() => {
//            cy.get('a').click()
//        })
//        cy.get('button[id="js-btn-enregistrer-descriptif"]').click()
//        cy.log('@ouverture')
//        if ('@ouverture' === "1") {
//            cy.log("1")
//            cy.get('div[id^="afficher_rubrique-"]').first().within(() => {
//                cy.get('div[id^="rubrique-body-"]').first().should('not.have.class', 'in')
//            })
//        } else {
//            cy.log("0")
//            cy.get('div[id^="afficher_rubrique-"]').first().within(() => {
//                cy.get('div[id^="rubrique-body-"]').first().should('have.class', 'in')
//            });
//        }