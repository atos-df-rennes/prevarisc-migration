// Tests fonctionnels — Modification d'un établissement (un test par genre)
// URL migrée : /etablissement/{id}/modifier
// Pré-requis : les tests 2.1 (ajout) doivent avoir été exécutés au préalable
// afin de garantir l'existence d'au moins un établissement par genre.
// Les libellés créés par 2.1 suivent le pattern "{Genre} E2E {timestamp}".
describe('Établissement — modification par genre', () => {
    beforeEach(() => {
        cy.login()
    })

    /**
     * Helper : recherche un établissement par son libellé partiel, navigue vers sa fiche,
     * puis clique sur « Modifier la fiche ».
     *
     * @param {string} searchTerm - Terme de recherche (préfixe du libellé, ex: "Site E2E")
     */
    function naviguerVersEdition(searchTerm) {
        cy.visit('/rechercher/etablissement')
        cy.get('input[name="label"].search-query').clear()
        cy.get('input[name="label"].search-query').type(searchTerm)
        cy.get('input[type="submit"][name="Rechercher"]').click()
        cy.get('ul.recherche_liste').contains('a', searchTerm).should('exist')
        cy.get('ul.recherche_liste').contains('a', searchTerm).first().click()
        cy.contains('Modifier la fiche').click()
        cy.url().should('include', '/modifier')
    }

    /**
     * Helper : soumet le formulaire de modification et vérifie le succès.
     * Intercepte l'appel defaults_values et auto-confirme la modale si elle apparaît.
     */

    // ——————————————————————————————————————————————————————————
    // Genre 1 : Site
    // ——————————————————————————————————————————————————————————
    it('Genre Site — modification du libellé et du téléphone', () => {
        naviguerVersEdition('Site E2E')

        const newLibelle = `Site Modifié ${Date.now()}`
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').type(newLibelle)
        cy.get('input[name="TELEPHONE_ETABLISSEMENT"]').clear()
        cy.get('input[name="TELEPHONE_ETABLISSEMENT"]').type('0299000099')

        cy.submitEstablishment(newLibelle)
        cy.contains('0299000099').should('exist')
    })

    // ——————————————————————————————————————————————————————————
    // Test complémentaire : lien de création rapide d'un établissement enfant
    // Bug corrigé : ce lien (présent dans le legacy) avait disparu de la migration,
    // empêchant de créer un nouvel établissement déjà rattaché au site en cours d'édition.
    // ——————————————————————————————————————————————————————————
    it('Genre Site — lien "Créer un établissement enfant" disponible en modification', () => {
        naviguerVersEdition('Site E2E')

        cy.url().then(editUrl => {
            const siteId = editUrl.match(/\/etablissement\/(\d+)\/modifier/)[1]

            cy.contains('a', 'Créer un établissement enfant')
                .should('be.visible')
                .and('have.attr', 'href')
                .and('include', '/etablissement/ajouter')

            cy.contains('a', 'Créer un établissement enfant').invoke('attr', 'href')
                .should('include', `pere=${siteId}`)
        })
    })

    /**
     * Helper : sélectionne un élément dans un TomSelect.
     * TomSelect remplace l'input natif par un .ts-control wrapper.
     */
    function tomSelectSearch(inputId, query) {
        cy.get('#' + inputId).siblings('.ts-wrapper').find('.ts-control input').type(query)
        cy.get('#' + inputId).siblings('.ts-wrapper').find('.ts-dropdown .ts-dropdown-content .option').first()
            .should('be.visible').click()
    }

    // ——————————————————————————————————————————————————————————
    // Genre 2 : ERP
    // ——————————————————————————————————————————————————————————
    it('Genre ERP — modification des effectifs et de la périodicité', () => {
        naviguerVersEdition('ERP E2E')

        const newLibelle = `ERP Modifié ${Date.now()}`
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').type(newLibelle)

        // Modifier les effectifs
        cy.get('input[name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS"]').type('750')
        cy.get('input[name="EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS"]').type('80')

        // Modifier la périodicité
        cy.get('input[name="PERIODICITE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="PERIODICITE_ETABLISSEMENTINFORMATIONS"]').type('24')

        // Ajouter un préventionniste (vérifie l'absence de crash template)
        // Prérequis : au moins un préventionniste actif doit exister dans la base
        cy.intercept('GET', '**/api/utilisateurs/search*').as('searchPrev')
        tomSelectSearch('preventionniste_autocomplete', 'Sect')
        cy.wait('@searchPrev')
        cy.get('table#preventionnistes tbody tr.preventionniste').should('have.length.gte', 1)

        cy.submitEstablishment(newLibelle)
    })

    // ——————————————————————————————————————————————————————————
    // Genre 3 : Cellule
    // ——————————————————————————————————————————————————————————
    it('Genre Cellule — modification du libellé et des effectifs', () => {
        naviguerVersEdition('Cellule E2E')

        const newLibelle = `Cellule Modifiée ${Date.now()}`
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').type(newLibelle)
        cy.get('input[name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS"]').type('300')

        cy.submitEstablishment(newLibelle)
    })

    // ——————————————————————————————————————————————————————————
    // Genre 4 : Habitation
    // ——————————————————————————————————————————————————————————
    it('Genre Habitation — modification du libellé et de la famille', () => {
        naviguerVersEdition('Habitation E2E')

        const newLibelle = `Habitation Modifiée ${Date.now()}`
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').type(newLibelle)

        // Changer la famille (sélection index 2 si disponible, sinon 1)
        cy.get('select[name="ID_FAMILLE"]').then($select => {
            const options = $select.find('option').length
            cy.get('select[name="ID_FAMILLE"]').select(options > 2 ? 2 : 1)
        })

        cy.submitEstablishment(newLibelle)
    })

    // ——————————————————————————————————————————————————————————
    // Genre 5 : IGH
    // ——————————————————————————————————————————————————————————
    it('Genre IGH — modification du libellé et de la périodicité', () => {
        naviguerVersEdition('IGH E2E')

        const newLibelle = `IGH Modifié ${Date.now()}`
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').type(newLibelle)
        cy.get('input[name="PERIODICITE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="PERIODICITE_ETABLISSEMENTINFORMATIONS"]').type('12')
        cy.get('input[name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS"]').type('1500')

        cy.submitEstablishment(newLibelle)
    })

    // ——————————————————————————————————————————————————————————
    // Genre 6 : BUP
    // ——————————————————————————————————————————————————————————
    it('Genre BUP — modification du libellé et des effectifs', () => {
        naviguerVersEdition('BUP E2E')

        const newLibelle = `BUP Modifié ${Date.now()}`
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').type(newLibelle)
        cy.get('input[name="EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS"]').type('120')

        cy.submitEstablishment(newLibelle)
    })

    // ——————————————————————————————————————————————————————————
    // Genre 7 : Camping
    // ——————————————————————————————————————————————————————————
    it('Genre Camping — modification des emplacements et vérification du total', () => {
        naviguerVersEdition('Camping E2E')

        const newLibelle = `Camping Modifié ${Date.now()}`
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').type(newLibelle)

        // Modifier les emplacements
        cy.get('input[name="EFFECTIFHABITATION_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFHABITATION_ETABLISSEMENTINFORMATIONS"]').type('60')
        cy.get('input[name="EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFCARAVANE__ETABLISSEMENTINFORMATIONS"]').type('40')
        cy.get('input[name="EFFECTIFEMPLACEMENTNU_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFEMPLACEMENTNU_ETABLISSEMENTINFORMATIONS"]').type('120')
        cy.get('input[name="EFFECTIFDIVERS_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFDIVERS_ETABLISSEMENTINFORMATIONS"]').type('10')

        // Vérifier le calcul dynamique du total
        cy.get('input[name="EFFECTIFTOTALCAMPING_ETABLISSEMENTINFORMATIONS"]').should('have.value', '230')

        cy.submitEstablishment(newLibelle)
    })

    // ——————————————————————————————————————————————————————————
    // Genre 8 : Manifestation temporaire
    // ——————————————————————————————————————————————————————————
    it('Genre Manifestation temporaire — modification des effectifs', () => {
        naviguerVersEdition('Manif E2E')

        const newLibelle = `Manif Modifiée ${Date.now()}`
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').type(newLibelle)
        cy.get('input[name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS"]').type('8000')
        cy.get('input[name="EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFPERSONNEL_ETABLISSEMENTINFORMATIONS"]').type('300')

        cy.submitEstablishment(newLibelle)
    })

    // ——————————————————————————————————————————————————————————
    // Genre 9 : IOP
    // ——————————————————————————————————————————————————————————
    it('Genre IOP — modification des effectifs', () => {
        naviguerVersEdition('IOP E2E')

        const newLibelle = `IOP Modifié ${Date.now()}`
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').type(newLibelle)
        cy.get('input[name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="EFFECTIFPUBLIC_ETABLISSEMENTINFORMATIONS"]').type('3000')

        cy.submitEstablishment(newLibelle)
    })

    // ——————————————————————————————————————————————————————————
    // Genre 10 : Zone
    // ——————————————————————————————————————————————————————————
    it('Genre Zone — modification du libellé et du classement', () => {
        naviguerVersEdition('Zone E2E')

        const newLibelle = `Zone Modifiée ${Date.now()}`
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').clear()
        cy.get('input[name="LIBELLE_ETABLISSEMENTINFORMATIONS"]').type(newLibelle)

        // Changer le classement (sélection index 2 si disponible, sinon 1)
        cy.get('select[name="ID_CLASSEMENT"]').then($select => {
            const options = $select.find('option').length
            cy.get('select[name="ID_CLASSEMENT"]').select(options > 2 ? 2 : 1)
        })

        cy.submitEstablishment(newLibelle)
    })

    // ——————————————————————————————————————————————————————————
    // Test CSRF : un token invalide ne provoque pas la sauvegarde
    // ——————————————————————————————————————————————————————————
    it('Soumission avec token CSRF invalide affiche une erreur', () => {
        // Utiliser "Modifié" car les tests précédents ont renommé les établissements "E2E"
        naviguerVersEdition('Modifié')

        // Altérer le token CSRF dans le DOM
        cy.get('input[name="_token"]').invoke('val', 'token_invalide_csrf')

        // Le JS déclenche un appel defaults_values puis affiche une modale de confirmation
        cy.intercept('POST', '**/defaults_values*').as('defaultsValues')

        cy.window().then(win => {
            win.$('#confirm-modal').on('shown.bs.modal', function () {
                win.$(this).find('input[type="submit"]').trigger('click')
            })
        })

        cy.get("form#etablissement input[type='submit']").click()
        cy.wait('@defaultsValues')

        // La page ne redirige pas, elle affiche un flash d'erreur
        cy.url({ timeout: 15000 }).should('include', '/modifier')
        cy.contains('Token CSRF invalide').should('exist')
    })
})
