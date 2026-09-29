---
name: phpstan-fix
description: Correct PHPStan level 10 findings in the Prevarisc application without changing business behavior
---

# Correcting PHPStan level 10 findings

## Run the analysis

When the sibling infrastructure repository and Docker environment are available, run `castor symfony:analyse` from the root of `prevarisc-infra`. For project-local command details, see `validate-application`.

## PHP 8.5 conventions

Prefer native types and modern PHP features already used by the project:

- Typed and promoted `readonly` properties.
- Return and parameter types, plus precise PHPDoc for generics and structured arrays.
- PHP attributes instead of Doctrine, route, or PHPUnit annotations.
- Union types, enums, named arguments, `match`, and nullsafe access where they clarify the existing logic.

## Common corrections

### Missing property type

Promote an injected dependency rather than adding an untyped property:

```php
public function __construct(private readonly DossierRepository $dossierRepository)
{
}
```

### Missing parameter or return type

Add a native type where possible and document array element types:

```php
/** @param array<int, string> $items */
public function processItems(array $items): void
{
}
```

### Nullable values

Check nullable repository results before dereferencing them, and represent nullable entity fields consistently in the Doctrine attribute and PHP property type.

### Iterable and collection types

Document iterable key/value types (`array<string, mixed>`, `Dossier[]`) where PHP has no native generic type. Prefer bounded repository queries and pagination; do not introduce unpaginated `findAll()` calls.

## Process

1. Run the analysis and group errors by file.
2. Correct types or control flow without changing business behavior.
3. Avoid suppressions unless there is no sound type-level fix.
4. Re-run the analysis and report any remaining findings.
