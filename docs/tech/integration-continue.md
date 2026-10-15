# Integration continue

Le workflow `.github/workflows/ci.yml` s'execute sur toutes les pull requests et
sur les push vers `develop`, `main` et `release/**`. Une nouvelle execution
sur la meme PR annule l'execution precedente ; les executions sur push ne sont
pas annulees. Sur les push, tous les controles sont executes.

Sur une pull request, un job leger detecte les fichiers modifies avec des
filtres propres a chaque controle. PHPStan analyse toujours l'ensemble de
`src/`, mais ne se lance que si `src/**/*.php`, sa configuration ou ses
dependances ont change. Rector garde son analyse complete de `src/` et `tests/`
et se lance pour les changements PHP, de configuration ou de dependances.
PHPUnit se lance pour les changements dans `src/`, `tests/` ou sa configuration.

PHP-CS-Fixer et Twig-CS-Fixer recoivent uniquement les fichiers PHP/Twig
modifies. Ils analysent tout leur perimetre configure lorsque leur configuration,
leurs dependances, le workflow CI ou l'action composite partagee changent. Les
fichiers supprimes sont ignores. Sur les push vers les branches suivies, tous
les controles s'executent et les deux formateurs analysent leur perimetre
complet. Les jobs de conflits et d'audit restent toujours actifs, quelle que
soit la liste de fichiers modifies.

## Controles

Les jobs de controle s'executent en parallele sur `ubuntu-latest`
avec PHP 8.5, sans Castor ni service de base de donnees :

PHPUnit s'execute aussi sur `windows-latest`, avec la meme suite hors groupe
`functional`. Les deux variantes vont jusqu'au bout meme si l'une echoue
(`fail-fast: false`), et leurs resultats sont bloquants pour le job `CI`.
Les autres controles restent uniquement sur Ubuntu.

| Job | Commande |
| --- | --- |
| PHPStan | `tools/vendor/bin/phpstan --memory-limit=-1 --no-progress` |
| Rector | `tools/vendor/bin/rector --dry-run --no-progress-bar` |
| PHP-CS-Fixer | `tools/vendor/bin/php-cs-fixer fix --dry-run --diff [fichiers modifies]` sur PR, tout le perimetre configure sinon |
| Twig-CS-Fixer | `tools/vendor/bin/twig-cs-fixer check [fichiers modifies]` sur PR, tout le perimetre configure sinon |
| PHPUnit | `php vendor/bin/phpunit --testdox --exclude-group functional` |
| Git conflict markers | `tools/vendor/bin/swiss-knife check-conflicts .` avec exclusions des dependances et fichiers generes |
| Composer audit | `composer audit --locked` a la racine et dans `tools/` |

Les outils de style et Rector ne modifient pas le code. Les controles executes
sont bloquants. Le job `CI` agrege tous les jobs : il echoue si l'un d'eux echoue
ou est annule, et accepte les jobs ignores par les filtres. L'audit ignore les
paquets abandonnes, comme la CI historique, mais echoue en cas de vulnerabilite
connue. Les deux projets Composer sont audites, meme si le premier audit echoue.

Les tests qui dependent d'une base de donnees ou de donnees de reference portent
le groupe PHPUnit `functional`, y compris ceux dans `tests/Repository/`.
Cypress, les controles du titre et des labels de PR, ainsi que le deploiement
ne font pas partie de ce workflow.

## Surveillance hebdomadaire des dependances

Le workflow `.github/workflows/dependencies.yml` s'execute chaque lundi a
06:17 UTC (`17 6 * * 1`) sur la branche par defaut du depot, sans declenchement
sur PR ou push. GitHub peut retarder une execution planifiee.
Il peut aussi etre lance manuellement depuis GitHub Actions
(`workflow_dispatch`), notamment pour verifier une mise a jour sans attendre
le prochain lundi. Le workflow doit etre present sur la branche par defaut
pour que ces declencheurs soient disponibles.

Le job `dependencies` est uniquement informatif (`continue-on-error: true`).
Il execute `tools/vendor/bin/swiss-knife breakpoint --limit 5` a la racine,
puis `vendor/bin/swiss-knife breakpoint --limit 5` dans `tools/`.
Il signale les dependances directes en retard de mise a jour pour les deux
projets Composer, avec le seuil de cinq paquets utilise par la tache Castor
`symfony:dependencies`. Le second controle s'execute meme si le premier depasse
le seuil, a condition que l'installation ait reussi. Les echecs restent visibles
dans les logs du job, mais ne font pas echouer le workflow. Ce controle ne
remplace pas l'audit de securite.

## Environnement

L'action composite `.github/actions/setup/action.yml` configure PHP et ses
extensions avec `date.timezone=Europe/Paris`, comme le conteneur local,
puis installe uniquement les dependances necessaires a chaque job
depuis les fichiers `composer.lock` (application et/ou `tools/`).
Les scripts Composer sont conserves : ils construisent notamment le conteneur
Symfony dev utilise par PHPStan et generent les routes JavaScript.

Aucun fichier `.env` n'est genere. Les variables necessaires sont definies dans
le bloc `env:` du workflow : `APP_ENV`, `APP_DEBUG`, `APP_SECRET`, `DATABASE_URL`
et `APP_RUNTIME_OPTIONS`. Les valeurs sont propres a la CI, sans secret de
production ; l'URL de base contient une version MariaDB explicite et ne suppose
aucun serveur accessible. Les autres reglages utilisent les valeurs par defaut
des configurations Symfony.

`APP_RUNTIME_OPTIONS='{"disable_dotenv":true}'` desactive le chargement Dotenv
pour `bin/console`. Le job PHPUnit utilise `APP_ENV=test` ; son bootstrap charge
uniquement `.env.test` lorsque `.env` est absent. Avec un `.env` local, le
chargement habituel reste inchange.

## Cache et permissions

`ramsey/composer-install` gere le cache des telechargements Composer, separe
entre application et outils. Les dependances sont toujours installees depuis
les locks, y compris apres restauration du cache.

Les caches de resultats sont restaures avec `actions/cache` :

- PHPStan : `var/cache/phpstan` ;
- Rector : `var/cache/rector` ;
- PHP-CS-Fixer : `.php-cs-fixer.cache` ;
- Twig-CS-Fixer : `.twig-cs-fixer.cache`.

Les cles incluent le systeme, PHP 8.5, les locks/configurations pertinents et le
SHA du commit. Un prefixe permet de reutiliser les resultats d'un commit
precedent ; les outils reanalysent les fichiers modifies. Le conteneur Symfony
et les caches PHPUnit ne sont pas partages entre executions.

Le workflow dispose uniquement de `contents: read` ; le job de detection
dispose en plus de `pull-requests: read` pour lire la liste des fichiers de la
PR via l'API GitHub. Le checkout ne conserve pas les identifiants Git. Le
workflow n'utilise pas `pull_request_target` ni de secret pour executer le code
des PR.

## Validation locale

En dehors de GitHub Actions, conserver les commandes Castor de
`prevarisc-infra` : `castor symfony:validate` ou les taches ciblees.
La CI ne remplace pas la validation locale et n'impose aucun changement aux
infrastructures clientes.
