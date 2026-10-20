---
applyTo: "tests/**,**/*Test.php"
---

# Testing

Test business logic with unit tests, complex interactions with integration tests, and critical HTTP flows with functional PHPUnit tests. Avoid testing trivial getters/setters or framework internals.

Group related scenarios in a test class with multiple test methods or data providers instead of creating one class per scenario. Browser-driven end-to-end UI tests belong in `cypress/`; `tests/Functional/` contains lower-level PHPUnit `WebTestCase` tests.

Use `castor symfony:test` for the default PHPUnit suite, which excludes the `functional` group. See `validate-application` for the full validation workflow.

The `database` group is included by default and requires the dedicated test
database configured in `.env.test`, with permission to rebuild its schema.
Use `KernelTestCase` / `WebTestCase` with Foundry's `#[ResetDatabase]`
attribute and `#[Group('database')]`. Do not use the deprecated
`ResetDatabase` or `Factories` traits. Foundry builds the ORM schema;
global stories, not migrations, provide reference data. DAMA rolls back each
test transaction.

Keep factories in `tests/Factory/` and stories in `tests/Story/`, never in
`src/`, because Foundry is a development-only dependency. Use one global
story per reference table with explicit reference IDs and values from the
migrations, including subsequent additions, using `App\Constant\*` where
available. When reference values are absent from migrations, an explicitly
user-supplied reference list may be used; document its provenance and mark
any fixture-local IDs as distinct from production IDs.
Do not add tests that merely read back the values inserted by a
story; test application behavior using those fixtures instead.
Named states are optional shortcuts,
not required for every row; use repository lookups by ID for large reference
tables. Use factories for scenario-specific data. Do not add tests of
Foundry/DAMA internals such as transaction rollback.

Do not migrate historical `functional` tests incidentally; they remain
excluded until converted to isolated `database` scenarios. See
`docs/tech/tests-php.md` for setup, conventions and the migration roadmap.
