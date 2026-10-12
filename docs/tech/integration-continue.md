# Integration continue

Le workflow `.github/workflows/ci.yml` s'execute sur toutes les pull requests et
sur les push vers `develop` et `main`. Une nouvelle execution sur la meme PR
annule l'execution precedente ; les executions sur push ne sont pas annulees.

## Controles

Les sept jobs sont independants et s'executent en parallele sur `ubuntu-latest`
avec PHP 8.5, sans Castor ni service de base de donnees :

| Job | Commande |
| --- | --- |
| PHPStan | `tools/vendor/bin/phpstan --memory-limit=-1 --no-progress` |
| Rector | `tools/vendor/bin/rector --dry-run --no-progress-bar` |
| PHP-CS-Fixer | `tools/vendor/bin/php-cs-fixer fix --dry-run --diff` |
| Twig-CS-Fixer | `tools/vendor/bin/twig-cs-fixer check` |
| PHPUnit | `php vendor/bin/phpunit --testdox --exclude-group functional` |
| Git conflict markers | `tools/vendor/bin/swiss-knife check-conflicts .` avec exclusions des dependances et fichiers generes |
| Composer audit | `composer audit --locked` a la racine et dans `tools/` |

Les outils de style et Rector ne modifient pas le code. Chaque controle est
bloquant. L'audit ignore les paquets abandonnes, comme la CI historique, mais
echoue en cas de vulnerabilite connue. Les deux projets Composer sont audites,
meme si le premier audit echoue.

Les tests qui dependent d'une base de donnees ou de donnees de reference portent
le groupe PHPUnit `functional`, y compris ceux dans `tests/Repository/`.
Cypress, les controles du titre et des labels de PR, ainsi que le deploiement
ne font pas partie de ce workflow.

## Environnement

L'action composite `.github/actions/setup/action.yml` configure PHP et ses
extensions puis installe uniquement les dependances necessaires a chaque job
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

Le workflow dispose uniquement de `contents: read`. Le checkout ne conserve
pas les identifiants Git. Il n'utilise pas `pull_request_target` ni de secret
pour executer le code des PR.

## Validation locale

En dehors de GitHub Actions, conserver les commandes Castor de
`prevarisc-infra` : `castor symfony:validate` ou les taches ciblees.
La CI ne remplace pas la validation locale et n'impose aucun changement aux
infrastructures clientes.
