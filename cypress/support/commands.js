// Login using credentials from cy.env() (overridable with CYPRESS_username / CYPRESS_password)
// Cypress.env() is deprecated since Cypress 15.10; cy.env() is the safer, non-browser-exposed replacement
Cypress.Commands.add('login', (username, password) => {
    cy.env(['username', 'password']).then((envCredentials) => {
        const resolvedUsername = username || envCredentials.username
        const resolvedPassword = password || envCredentials.password

        cy.session([resolvedUsername, resolvedPassword], () => {
            cy.visit('/session/login')
            cy.get('[name=_username]').type(resolvedUsername)
            cy.get('[name=_password]').type(resolvedPassword)
            cy.contains('button', 'Connexion').click()
            cy.title().should('eq', 'Tableau de bord')
        })
    })
})

// Submit establishment form and verify redirection
// Handles the defaults_values POST and auto-confirms the confirmation modal if it appears
Cypress.Commands.add('submitEstablishment', (expectedLibelle) => {
    cy.intercept('POST', '**/defaults_values*').as('defaultsValues')
    
    cy.window().then(win => {
        win.$('#confirm-modal').on('shown.bs.modal', function () {
            win.$(this).find('input[type="submit"]').trigger('click')
        })
    })
    
    cy.get("form#etablissement input[type='submit']").click()
    cy.wait('@defaultsValues')
    
    cy.url({ timeout: 15000 }).should('match', /\/etablissement\/\d+/)
    if (expectedLibelle) {
        cy.get('h2.page-header, h2').should('contain', expectedLibelle)
    }
})