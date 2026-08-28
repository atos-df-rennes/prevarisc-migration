const { defineConfig } = require("cypress");

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:7080',
    defaultCommandTimeout: 25000,
  },
  // Credentials are read via cy.env() only (never exposed to browser code); overridable via CYPRESS_username / CYPRESS_password env vars
  allowCypressEnv: false,
  env: {
    username: 'root',
    password: 'root',
  },
});
