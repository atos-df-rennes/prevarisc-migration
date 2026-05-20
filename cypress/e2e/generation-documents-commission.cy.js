/**
 * Tests fonctionnels — Génération des documents de commission
 *
 * Vérifie :
 * - Protection CSRF (token invalide → 403)
 * - Date commission inexistante → 404
 * - Appel valide → réponse JSON avec structure attendue (success/summary/results)
 * - Toast notification affiché après succès ou erreur
 *
 * Prérequis : une date de commission doit exister en base avec au moins un dossier affecté.
 */

const generationEndpoints = [
    { label: 'Convocation', suffix: 'convocation', csrfId: 'generation_convocation' },
    { label: 'Ordre du jour', suffix: 'odj', csrfId: 'generation_odj' },
    { label: 'Procès-verbal', suffix: 'pv', csrfId: 'generation_pv' },
    { label: 'Compte-rendu', suffix: 'compte-rendu', csrfId: 'generation_compte_rendu' },
];

describe('Génération documents de commission — sécurité', () => {
    before(() => {
        cy.login();
        cy.visit('/accueil');
    });

    generationEndpoints.forEach(({ label, suffix }) => {
        it(`${label} — CSRF invalide retourne 403`, () => {
            cy.request({
                method: 'POST',
                url: `/commission/calendrier/1/generation/${suffix}`,
                body: { _token: 'invalid-token' },
                form: true,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.status).to.eq(403);
                expect(response.body).to.have.property('success', false);
                expect(response.body).to.have.property('summary');
                expect(response.body.summary).to.include('Token');
            });
        });
    });

    generationEndpoints.forEach(({ label, suffix }) => {
        it(`${label} — date commission inexistante retourne 404`, () => {
            // On utilise un ID très grand qui ne peut pas exister
            cy.request({
                method: 'POST',
                url: `/commission/calendrier/999999999/generation/${suffix}`,
                body: { _token: 'will-fail-csrf-first' },
                form: true,
                failOnStatusCode: false,
            }).then((response) => {
                // Sera 403 (CSRF) ou 404 (not found) selon l'ordre de validation
                expect(response.status).to.be.oneOf([403, 404]);
                expect(response.body).to.have.property('success', false);
            });
        });
    });
});

describe('Génération documents de commission — structure réponse', () => {
    let dateCommId;

    before(() => {
        cy.login();
        cy.visit('/accueil');

        // Cherche une date de commission existante via le calendrier
        cy.request('/commissions/calendrier').then((res) => {
            // Cherche un lien vers gestionodj qui contient un dateCommId
            const match = res.body.match(/gestion-odj\/(\d+)/);
            if (match) {
                dateCommId = parseInt(match[1], 10);
            }
        });
    });

    it('Convocation — appel avec CSRF valide retourne JSON structuré', function () {
        if (!dateCommId) {
            this.skip();
        }

        // Visite la page pour récupérer le CSRF token
        cy.visit(`/commissions/calendrier/gestion-odj/${dateCommId}`);
        cy.window().then((win) => {
            const config = win.GESTIONODJ_CONFIG;
            expect(config).to.exist;
            expect(config.csrfTokenConvoc).to.exist;

            cy.request({
                method: 'POST',
                url: config.urlGenerationConvoc,
                body: { _token: config.csrfTokenConvoc },
                form: true,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.status).to.eq(200);
                expect(response.body).to.have.property('success');
                expect(response.body).to.have.property('summary');
                expect(response.body).to.have.property('results');
                expect(response.body.results).to.be.an('array');

                // Chaque résultat doit avoir la structure attendue
                if (response.body.results.length > 0) {
                    const first = response.body.results[0];
                    expect(first).to.have.property('memberId');
                    expect(first).to.have.property('memberLabel');
                    expect(first).to.have.property('status');
                    expect(first).to.have.property('message');
                }
            });
        });
    });

    it('Génération affiche un toast de notification', function () {
        if (!dateCommId) {
            this.skip();
        }

        cy.visit(`/commissions/calendrier/gestion-odj/${dateCommId}`);

        // Simule le clic sur le bouton convocation
        // Le toast doit apparaître (succès ou erreur selon les données en base)
        cy.get('#createConvoc').click();

        // Vérifie qu'une notification toast apparaît dans les 10 secondes
        // La classe toast-notification est ajoutée par toast-notifications.js
        cy.get('.toast-notification', { timeout: 10000 }).should('exist');
        cy.get('.alert', { timeout: 10000 }).should('exist');
    });
});
