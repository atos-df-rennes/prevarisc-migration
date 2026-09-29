---
applyTo: "composer.json,composer.lock,package.json,package-lock.json"
---

# Dependency upgrades

An upgrade is not complete when tests merely pass on the new version. Also:

- Remove or replace deprecated API usage introduced by the new version; do not merely silence deprecation notices.
- Update call sites to current idioms rather than backward-compatibility shims kept "just in case".
- If a deprecation cannot reasonably be removed in this pass, flag it explicitly in the report or pull request.

The goal is to keep the codebase ready for the next version, not merely working on the current one.
