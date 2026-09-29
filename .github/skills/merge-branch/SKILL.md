---
name: merge-branch
description: Resolve cross-version release branch merge conflicts in the Prevarisc application while preserving current PHP and Symfony conventions
---

# Resolving cross-version application merges

Use this guide after a merge has already been started and conflicts need resolution. The Castor orchestration for merge-upwards lives in the sibling infrastructure repository; this skill covers conflict analysis and application-level compatibility.

## Safety and context

- Confirm the current branch and merge state before editing. Never switch or check out a branch without explicit user permission.
- Work only in this repository. Keep the frozen Zend reference read-only.
- Do not commit unless requested.
- Preserve the target branch's modern PHP/Symfony style while applying the source branch's functional fixes.

## Identify all files in the merge

```bash
git status --short
git merge-base HEAD MERGE_HEAD
git diff --name-only --diff-filter=ACMRT "$(git merge-base HEAD MERGE_HEAD)" MERGE_HEAD
```

Review every added or modified file from the source branch, not only files with textual conflicts.

## Resolve using the target branch conventions

- Keep PHP attributes and remove duplicated legacy annotations.
- When adding a constructor dependency, follow the existing promoted, typed, `readonly` property convention; verify it is used.
- Apply functional fixes such as nullability changes without reverting the target branch's modernized syntax.
- For configuration files, retain the target branch's version and target-specific settings while incorporating relevant functional changes.

## Verify before finalizing

Check for unresolved paths and conflict markers:

```bash
git diff --name-only --diff-filter=U
grep -rl "^<<<<<<<\|^=======\|^>>>>>>>" --include="*.php" --include="*.twig" --include="*.json" . | grep -v vendor
```

Run `castor symfony:validate` from the sibling infra repository when available, or follow `validate-application` for project-local checks. Distinguish failures introduced by the merge from known baseline failures and report any validation that could not be run.

If the user requests a commit, follow the repository's Conventional Commit format and include its required Copilot co-author trailer.
