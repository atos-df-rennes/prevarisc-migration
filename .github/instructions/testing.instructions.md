---
applyTo: "tests/**,**/*Test.php"
---

# Testing

Test business logic with unit tests, complex interactions with integration tests, and critical HTTP flows with functional PHPUnit tests. Avoid testing trivial getters/setters or framework internals.

Group related scenarios in a test class with multiple test methods or data providers instead of creating one class per scenario. Browser-driven end-to-end UI tests belong in `cypress/`; `tests/Functional/` contains lower-level PHPUnit `WebTestCase` tests.

Use `castor symfony:test` for the default PHPUnit suite, which excludes the `functional` group. See `validate-application` for the full validation workflow.
