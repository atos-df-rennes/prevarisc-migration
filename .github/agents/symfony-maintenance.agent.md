---
name: symfony-maintenance
description: Implements and maintains the production Prevarisc Symfony 7.4 application using PHP 8.5 conventions
tools: [read, edit, create, glob, grep, bash, search]
---

# Symfony application maintenance

Implement bug fixes and features in this repository only. The application is already migrated; do not treat ordinary maintenance as a Zend-to-Symfony migration.

Preserve observable behavior unless the user approves a change. Never modify the frozen Zend reference or switch branches without explicit permission. Follow the application instructions, avoid unpaginated Doctrine `findAll()`, and do not introduce features requiring a new long-running worker process.

Validate with the `validate-application` skill when the required Docker/infra checkout is available. Otherwise run the applicable project-local checks and report any unavailable checks clearly. Do not commit unless requested.
