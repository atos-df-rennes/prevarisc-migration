# Tests PHP

## Strategie

Les tests unitaires purs restent adaptes a la logique isolee. Pour les
interactions avec Doctrine, le groupe PHPUnit `database` utilise une base
dediee, des factories et des stories
[Foundry](https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html).
`dama/doctrine-test-bundle` annule la transaction de chaque test pour isoler
les scenarios.

Les dependances Foundry et DAMA sont en `require-dev` et leurs bundles sont
actifs uniquement en environnement `test`. Les factories et stories sont
rangees dans `tests/Factory/` et `tests/Story/` (`App\Tests\Factory` et
`App\Tests\Story`), jamais dans `src/` : elles ne doivent pas etre chargees
par le conteneur de production.

Les tests navigateur restent dans `cypress/`. Les tests HTTP PHPUnit de
`tests/Functional/` ne sont pas des tests de bout en bout navigateur.

## Base locale et commandes

`.env.test` definit une `DATABASE_URL` vers `PRV_prevarisc_v2_test`.
Une variable d'environnement deja exportee est prioritaire : verifier
qu'elle ne designe jamais la base de developpement ou de production avant
de lancer un test `database`. Foundry reconstruit le schema de la base cible.

Depuis la racine du depot d'infrastructure `prevarisc-infra`, creer la base
avec les commandes Symfony standard du conteneur :

```bash
docker compose --file compose.dev.yaml exec -w /var/www/html/prevarisc-migration app \
    php bin/console doctrine:database:create --env=test
```

L'utilisateur de la connexion doit avoir les droits necessaires pour creer
la base et reconstruire son schema. Si la creation echoue pour une question
de permissions, faire attribuer ces droits avant de continuer ; ne pas
remplacer la connexion par celle de la base de developpement.

Le schema provient du mapping ORM, pas des migrations. Foundry le recree
avec l'attribut `#[ResetDatabase]` en mode `schema`. Pour verifier initialement que tout
le mapping est exploitable, sur une base vide uniquement :

```bash
docker compose --file compose.dev.yaml exec -w /var/www/html/prevarisc-migration app \
    php bin/console doctrine:schema:create --env=test
```

Cette commande ne charge aucune donnee de reference. Les insertions de la
migration initiale ne sont pas rejouees : les stories sont l'unique source
des donnees de reference des tests.

La validation locale passe exclusivement par Castor, depuis la racine
d'infrastructure :

```bash
castor symfony:test
castor symfony:validate
```

Ces deux commandes incluent le groupe `database` et excluent le groupe
historique `functional`. Elles necessitent donc la base de test accessible
avec les droits de reconstruction du schema. Une erreur de connexion ou
de permissions est un prerequis manquant a corriger, pas une raison
d'ignorer le groupe. `castor symfony:test --all` inclut aussi les anciens
tests `functional`, dont la migration n'est pas realisee dans ce lot.

## Conventions de donnees

### Referentiels

Une story par table de reference est enregistree dans le `global_state`
Foundry. Elle est chargee apres la reconstruction du schema, avant les
transactions DAMA, et ses donnees restent disponibles pour chaque test.
Les valeurs et leur ordre de creation reprennent le seed de migration.
Les IDs doivent correspondre aux constantes de `App\Constant\*` ; un test
de concordance doit verifier ce contrat, sans supposer un contenu de base
preexistant.

Le premier referentiel est `AvisStory`, avec les acces nommes
`AvisStory::favorable()` et `AvisStory::defavorable()`. Ses valeurs sont
`Favorable` et `Défavorable`, avec l'orthographe exacte du seed.

### Donnees saisies

Les donnees propres a un scenario sont creees avec des factories Foundry.
Les valeurs par defaut sont generees par Faker ; le test surcharge
uniquement les champs utiles au scenario. Ne pas ajouter ces donnees aux
stories globales.

Les tests sur base utilisent `KernelTestCase` ou `WebTestCase`, l'attribut
Foundry `#[ResetDatabase]` et `#[Group('database')]`. Les anciens traits
`ResetDatabase` et `Factories` sont deprecies dans la version installee :
appeler directement les factories et stories.
Regrouper les scenarios lies dans une classe avec plusieurs methodes
ou un fournisseur de donnees. Le PoC couvre la concordance des avis et
l'absence, dans le test suivant, d'un avis ajoute dans un premier test.

Les extensions PHPUnit Foundry et DAMA sont activees. Le reset automatique
de tous les tests est desactive : seuls les tests marques
`#[ResetDatabase]` reconstruisent la base. PHPUnit echoue sur les
depreciations PHP et PHPUnit, y compris celles supprimees avec `@`.
`SYMFONY_DEPRECATIONS_HELPER=max[total]=0` est configure, mais le bridge
Symfony installe se desactive avec PHPUnit 13 : ce sont donc les options
strictes de PHPUnit qui assurent ce controle.

## Integration continue

Le job PHPUnit Ubuntu dispose d'un service MariaDB 11.8 et execute le
groupe `database`. La `DATABASE_URL` du workflow est prioritaire sur
`.env.test`. Foundry construit le schema et charge les referentiels, sans
import de dump ni migration de donnees.

Windows conserve la suite sans `functional` ni `database`, car les services
Docker GitHub Actions n'y sont pas disponibles. Voir
[Integration continue](integration-continue.md).

## Feuille de route

Les etapes suivantes ne sont pas encore implementees :

1. Etendre les stories globales aux autres referentiels et verifier leurs
   constantes.
2. Ajouter les factories metier et leurs etats nommes (utilisateur,
   etablissement, dossier, commission, etc.).
3. Migrer progressivement les tests `functional` vers `database`, sans
   dependance au contenu d'une base de developpement ni
   `markTestSkipped` conditionne par ce contenu.
4. Remplacer les mocks de persistance par des tests sur base lorsque cela
   simplifie les scenarios ; conserver les tests unitaires purs.
5. Proposer des stories de reproduction de cas client dans une base
   dediee, sans dump partage ni modification de la base de developpement.
6. Ajouter un controle de derive entre mapping et migrations.

Le schema ORM et le schema migre ne sont pas garantis identiques :
`avis.LIBELLE_AVIS` est notamment limite a 75 caracteres dans le mapping,
contre 30 dans la migration initiale. Le PoC n'altere ni le mapping ni
les migrations.
