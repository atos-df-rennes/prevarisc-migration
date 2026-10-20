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
Les valeurs et IDs reprennent les donnees de reference des migrations,
y compris leurs ajouts ulterieurs, sauf les listes explicitement fournies
par l'utilisateur et absentes des migrations (voir `docmanquant` ci-dessous).
Les IDs ne sont pas necessairement
consecutifs : ne pas compter sur l'ordre d'auto-increment pour les obtenir.
Pour ces references, une insertion DBAL avec IDs explicites dans la story
permet de respecter ce contrat sans modifier le mapping Doctrine.
Utiliser les constantes de `App\Constant\*` lorsqu'elles existent.
Les stories sont des preconditions des tests applicatifs : ne pas ajouter
un test par referentiel qui relit simplement les valeurs qu'elles inserent.
Un tel test ne verifie pas leur conformite aux donnees livrees par les
migrations.

Le premier referentiel est `AvisStory`, avec les acces nommes
`AvisStory::favorable()` et `AvisStory::defavorable()`. Ses valeurs sont
`Favorable` (1), `Défavorable` (2) et
`Pas d'avis - à motiver dans la partie Fondement de l'avis` (6).
Les deux premiers proviennent de la migration initiale ; le dernier est
ajoute par `Version20251107152219`.

Le lot 2 ajoute une story globale pour chaque table demandee : `capsulerubrique`
(4 lignes), `categorie` (5), `classe` (10), `classement` (5), `commissiontype`
(5), `commissiontypeevenement` (3), `docmanquant` (11), `dossiernatureliste` (62),
`dossiertype` (7), `famille`
(11), `fonction` (17), `genre` (10), `groupementtype` (13),
`listetypechamprubrique` (7), `piecejointestatut` (5), `privileges` (76),
`resources` (23), `statut` (4), `type` (27), `typeactivite` (115),
`typeplan` (5), `typetextesappl` (6) et `utilisateurcivilite` (2), soit
436 lignes ajoutees aux 3 lignes d'`avis`, soit 439 lignes au total.

Les 11 libelles de `docmanquant` proviennent de la liste actuelle fournie
par l'utilisateur, pas des migrations, qui n'en contiennent aucun.
Leurs IDs explicites 1 a 11 suivent l'ordre de cette liste et sont locaux
aux fixtures : ce ne sont pas des IDs de production ni des constantes metier.
Les accents et la ponctuation de la liste sont conserves.

Les jeux reflectent l'etat final des migrations, et non seulement leur premier
seed : genre 6 devient `BUP`, les libelles de statuts de pièces jointes sont
completes par `Version20250220152927`, l'activite 25 est corrigee par
`Version20250220140918`, les libelles `Lycee` sont accentues par
`Version20260113071712` et les privileges de lecture/ecriture sont ceux apres
`Version20260929180000`. Les IDs 118 a 120 de `typeactivite` sont ceux que la
migration attribue aux trois insertions sans ID apres le maximum explicite 117.

Les ressources specialisees sont celles reconnues par
`ResourcesRepository::findRessourcesSpecialiseesDossier()` et
`findRessourcesSpecialiseesEtablissement()` : leurs noms commencent par
`dossier_` ou `etablissement_`. Les 10 ressources generiques par defaut
(IDs 4 a 9 et 52 a 55) et leurs 21 privileges (IDs 17 a 29 et 52 a 59)
font partie des stories globales, avec les IDs et libelles de
`Version20250211103328` et le renommage EIC vers BUP de
`Version20250220154139`. Chaque ressource d'etablissement dispose de
`view_ets` et `edit_ets` ; `dossier_0` dispose de `view_doss`, `edit_doss`
et `verrouillage_dossier`. Ces valeurs permettent aux voters de trouver
les ressources par defaut ; les ressources specialisees personnalisees
restent exclues des stories globales.
Les autres ressources et privileges des migrations sont conserves,
y compris les insertions ulterieures. Aucun groupe, role ou octroi
automatique universel n'est ajoute : les groupes propres aux scenarios
pourront recevoir ces privileges au lot 3. Les ACL de production ne
changent pas ; aucun test de concordance des stories n'est ajoute.

Les states nommes et leurs methodes d'acces sont des raccourcis facultatifs
pour les valeurs frequemment utilisees dans les scenarios, pas une obligation
par ligne. Pour les referentiels volumineux, comme les types et types
d'activite, rechercher la valeur utile par son ID dans le repository de
l'entite ; ne pas multiplier les states et methodes.

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
ou un fournisseur de donnees. Verifier les comportements applicatifs qui
utilisent les referentiels, pas la seule insertion des fixtures.
Le rollback est pris en charge par
DAMA : ne pas ajouter de tests dedies au fonctionnement interne de cet
outil ou de Foundry.

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

1. **Termine — lot 2 :** stories globales des referentiels demandees, avec
   IDs deterministes, valeurs des migrations et ressources generiques par
   defaut incluses ; ressources specialisees personnalisees exclues.
   Liste utilisateur pour `docmanquant`, avec IDs locaux aux fixtures.
   Pas de tests de concordance autonomes.
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

Le schema ORM et le schema migre ne sont pas garantis identiques ; le
controle de derive reste a ajouter. Pour `avis.LIBELLE_AVIS`, le mapping
et le schema apres `Version20251107152219` definissent tous deux une
longueur de 75 caracteres : il n'y a pas de divergence sur ce point.
Les stories globales n'alterent ni le mapping ni les migrations.
