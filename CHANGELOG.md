<!--- BEGIN HEADER -->
# Changelog


<!--- END HEADER -->

## [2.8.14](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.13...v2.8.14) (11-02-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections


##### Ressources

* Prend en compte tous les types d'établissement enfant pour donner l'accès à un site (#109) ([990753](https://github.com/atos-df-rennes/prevarisc-migration/commit/9907533db70560ae65da9d979c21baf6193cadc7))


---

## [2.8.13](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.12...v2.8.13) (29-01-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections


##### Documents

* [#4989] erreur sur la suppression de documents contenant des apostrophes (#103) ([4183cf](https://github.com/atos-df-rennes/prevarisc-migration/commit/4183cf12eb16a9923ee3ce243ed63c156b423f51))

##### Prescriptions

* Possibilité d'ajouter une prescription type sur un texte sans article (#104) ([424376](https://github.com/atos-df-rennes/prevarisc-migration/commit/424376628cacc3fcb482e992958234ffbf8047e7))


---

## [2.8.12](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.11...v2.8.12) (08-01-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Ordonnancement des valeurs à l'ajout d'une liste (#101) ([184a1b](https://github.com/atos-df-rennes/prevarisc-migration/commit/184a1b7c4dcc95a6aafd9e3d1fa880a31c27c9bf))
* Conserve le type et l'indication de tableau à la sauvegarde d'un champ (#100) ([5d3925](https://github.com/atos-df-rennes/prevarisc-migration/commit/5d39259fa81f697deb988cb708f9a043576a9f2e))


---

## [2.8.11](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.10...v2.8.11) (08-12-2025)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Corrige l'application des droits aux répertoires de cache (#97) ([fc1ede](https://github.com/atos-df-rennes/prevarisc-migration/commit/fc1ede725669a003a13932951576e16707431e61))
* Résolution du datastore au runtime (#95) ([1e9b3a](https://github.com/atos-df-rennes/prevarisc-migration/commit/1e9b3a0ff31a46df42dfa6282af2f18cebb71ea5))


---

## [2.8.8](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.7...v2.8.8) (18-11-2025)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Ajout de la gestion des erreurs avec un logger dans LastActionSubscriber ([055526](https://github.com/atos-df-rennes/prevarisc-migration/commit/05552666b36e23eb6c0acce7d71d509512456cbd))
* Correction de la condition d'activation du menu "Gestion des prescriptions" ([3d9ceb](https://github.com/atos-df-rennes/prevarisc-migration/commit/3d9ceb3df49fada627bea68e63189b32e83318f2))
* Remplacement de balises `<i>` par `<span>` pour les icônes glyphicon ([f8147b](https://github.com/atos-df-rennes/prevarisc-migration/commit/f8147b89e27f99beb0ebd95ee315c5c3e53621d2))

##### Etablissement

* Affichage des informations utilisateur sur l'historique (#79) ([e6dac8](https://github.com/atos-df-rennes/prevarisc-migration/commit/e6dac87311ee6bb513a6656b46d4869f758f096f))

##### Platau

* Permet l'utilisation d'un fichier de configuration spécifique à un environnement (#85) ([108c15](https://github.com/atos-df-rennes/prevarisc-migration/commit/108c15ca3d422499ee7ad585b636f4ac09c6118e))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gears.svg" width="25" height="25"> Migration


##### Administration

* Gestion des champs des formulaires personnalisés (#80) ([06d0ed](https://github.com/atos-df-rennes/prevarisc-migration/commit/06d0ed3160fe20d191f512ea1c0e4bb2fa88eba6))
* Permissions avancées pour les établissements et les dossiers (#83) ([199040](https://github.com/atos-df-rennes/prevarisc-migration/commit/199040e460fc52689753318deb1987dde05cab1a))
* Gestion des rubriques des formulaires personnalisés (#76) ([33c7c6](https://github.com/atos-df-rennes/prevarisc-migration/commit/33c7c67a0383d9fb0cc0613f9631f3212d0ae940))
* Gestion des prescriptions types (#69) ([85f2fc](https://github.com/atos-df-rennes/prevarisc-migration/commit/85f2fc3b2df6622661af6bc408d02d06b568360b))
* Gestion des formulaires personnalisés (#68) ([633639](https://github.com/atos-df-rennes/prevarisc-migration/commit/633639d1c8a313292802aa27105cf394809b708f))

##### Dossier

* Verrouillage et déverrouillage (#67) ([a2b33c](https://github.com/atos-df-rennes/prevarisc-migration/commit/a2b33cbc2fef96315bb28a491b6f9407303ff593))

##### Etablissement

* Onglet contacts (#65) ([43d0f0](https://github.com/atos-df-rennes/prevarisc-migration/commit/43d0f07a122b5cfc1e89db42a625c46cc6240153))
* Affichage des pièces jointes (#64) ([aa5403](https://github.com/atos-df-rennes/prevarisc-migration/commit/aa5403c856d97683ba5d0497551c4d552a6ab2e4))

##### Prescriptions

* Ajout et modification des rappels réglementaires (#63) ([73a0e2](https://github.com/atos-df-rennes/prevarisc-migration/commit/73a0e23a9276d5c623e67a9cba90f6e999cb83e5))
* Ajout, modification et suppression des textes (#58) ([dd6c24](https://github.com/atos-df-rennes/prevarisc-migration/commit/dd6c249711cc617cdcdb0ea9f41ecdb9024128ef))


---

## [2.8.7](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.5...v2.8.7) (04-11-2025)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections


##### Formulaires

* Timeout lors de l'export de la configuration (#86) ([d33416](https://github.com/atos-df-rennes/prevarisc-migration/commit/d33416e48d5d059eb12d9d1f5f31b482336d0291))


---

## [2.8.5](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.4...v2.8.5) (15-10-2025)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections


##### Etablissement

* Affichage de l'historique avec utilisateurs inconnus (#75) ([66db47](https://github.com/atos-df-rennes/prevarisc-migration/commit/66db47e82a88b37934b533146132e55c63bbb121))


---

## [2.8.4](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.1...v2.8.4) (13-10-2025)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Augmentation de la taille des champs TYPE et SOUS_TYPE pour les pièces jointes (#74) ([262727](https://github.com/atos-df-rennes/prevarisc-migration/commit/262727df1711921c1716f9112cd594b276a71bd9))
* Insertion des ressources de changements indépendamment de la création de la table (#71) ([fb8c6d](https://github.com/atos-df-rennes/prevarisc-migration/commit/fb8c6dd234fc3ef70bd615bc6b3d3312eb56b05a))


---

## [2.8.1](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.0...v2.8.1) (24-09-2025)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/wand-magic-sparkles.svg" width="20" height="20"> Évolutions


##### Formulaires

* Glisser les valeurs de la liste dans administration (#55) ([bf4419](https://github.com/atos-df-rennes/prevarisc-migration/commit/bf4419f81505e8aaa22c04305c3bfd7ec7aeea74))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Mauvaise synchronisation sur le nom d'une colonne entre l'entité et le schéma (#52) ([5a15bb](https://github.com/atos-df-rennes/prevarisc-migration/commit/5a15bb90f50207a59270d84113d9c3d7ea9df9d4))
* Migrations en erreur sur une installation fresh (#51) ([358c88](https://github.com/atos-df-rennes/prevarisc-migration/commit/358c883c3d43934d0c5dd699bf16750acce8fe6b))
* Ne force pas les informations sur la commune (#49) ([625533](https://github.com/atos-df-rennes/prevarisc-migration/commit/625533d51a1a999339f8b8098f1c76ae3cec1350))
* Informations non obligatoires sur la commune (#48) ([71e761](https://github.com/atos-df-rennes/prevarisc-migration/commit/71e761d1255988e49c08f4f894514a7bb6f1e36b))
* Mauvais ordre d'exécution des migrations (#45) ([32ceae](https://github.com/atos-df-rennes/prevarisc-migration/commit/32ceaeb69d4724fe5d1a56027535cb104e195dc9))
* Rafraichissement des vues après modification d'une des tables utilisées (#43) ([6c5931](https://github.com/atos-df-rennes/prevarisc-migration/commit/6c5931a93b7a82886e6fa3c1e3592fca550c628d))
* Prise en compte des caractères spéciaux dans la chaîne de connexion à la base de données (#42) ([4f5ebc](https://github.com/atos-df-rennes/prevarisc-migration/commit/4f5ebcda3be7a14cef604764f7a39a5ae53c5f13))
* Le "-" fait planter la commande de migration (#41) ([14fdc8](https://github.com/atos-df-rennes/prevarisc-migration/commit/14fdc8a831301137c2a589d054f9c55eb271e5c1))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gears.svg" width="25" height="25"> Migration


##### Administration

* Couches cartographiques (#31) ([1681a6](https://github.com/atos-df-rennes/prevarisc-migration/commit/1681a6b6ecfe9888f5fb7d27973811a9caf974f2))
* Textes applicables (#44) ([b8d6cd](https://github.com/atos-df-rennes/prevarisc-migration/commit/b8d6cdbeb8c53089a30d8749915a71b4218d6b48))

##### Etablissement

* Affichage des avis et dérogations (#53) ([f1f37e](https://github.com/atos-df-rennes/prevarisc-migration/commit/f1f37edf7d0977569903f423275dcbe4e6718c27))
* édition des textes applicables (#50) ([aff3b8](https://github.com/atos-df-rennes/prevarisc-migration/commit/aff3b8c588e21bf7a221175cdd6b110775b9b8df))
* Affichage des textes applicables (#47) ([346ef3](https://github.com/atos-df-rennes/prevarisc-migration/commit/346ef3ebe773bb9c6af33d6f11dec90488dbca50))
* Historique (#32) ([5e9dc3](https://github.com/atos-df-rennes/prevarisc-migration/commit/5e9dc353fafaa693276cee8dc81bdbc5f0a82453))

##### Prescriptions

* Affichage des textes (#56) ([3843bf](https://github.com/atos-df-rennes/prevarisc-migration/commit/3843bf6db4746fcee1d2ae8ccde7668f99277c7a))


---

## [2.8.0](https://github.com/atos-df-rennes/prevarisc-migration/compare/3e6bb61fcff5194de100a562d0388ee4a89d38c0...v2.8.0) (28-07-2025)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/wand-magic-sparkles.svg" width="20" height="20"> Évolutions

* Prise en compte des assets (#1) ([572316](https://github.com/atos-df-rennes/prevarisc-migration/commit/572316a74f81a9a4f1e57becaaa951699a48f829))

##### Formulaires

* Export/import de configuration (#28) ([aca83f](https://github.com/atos-df-rennes/prevarisc-migration/commit/aca83fa7e5a0ca8e29af32a002abbad0384c2e74))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Renseignement des dates de dernière visite à la création d'un utilisateur ([7ad84c](https://github.com/atos-df-rennes/prevarisc-migration/commit/7ad84cbffe1694debe6290c4504a2700c612cc7f))
* Directive pour Apache >= 2.4 ([dac98d](https://github.com/atos-df-rennes/prevarisc-migration/commit/dac98d30aaff5b8af725afec348846d5545f4272))
* Les documents de commission n'étaient pas générés (#23) ([467aa7](https://github.com/atos-df-rennes/prevarisc-migration/commit/467aa746fa5001ee768360e0cb39cee4c91b1b27))

##### Admin

* Modification d'un utilisateur sans modification du mot de passe (#34) ([7f0477](https://github.com/atos-df-rennes/prevarisc-migration/commit/7f0477e2df4fd07a9f4d52d56c27f6656ebb8244))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gears.svg" width="25" height="25"> Migration

* Fusion des communes (#24) ([e58119](https://github.com/atos-df-rennes/prevarisc-migration/commit/e581194c3664cc1620407ae32f6f27b59b15c74d))
* Page du tableau des périodicités (#22) ([9142cb](https://github.com/atos-df-rennes/prevarisc-migration/commit/9142cb2c197fc337f88b868cd08ef925ea8afa83))
* Page de gestion des communes (#21) ([42080c](https://github.com/atos-df-rennes/prevarisc-migration/commit/42080c5a9980c5111ec7d0c592974960709fa701))
* Page des éléments supprimés (#20) ([01ca67](https://github.com/atos-df-rennes/prevarisc-migration/commit/01ca67cf9f9ca22dd4507ece0bd578ffcbe34a15))
* Gestion des documents (#18) ([2b6711](https://github.com/atos-df-rennes/prevarisc-migration/commit/2b67112d6494f3d65af6dd4f8d013492fb3cfd23))
* Messages d'alerte (#17) ([ce6c3a](https://github.com/atos-df-rennes/prevarisc-migration/commit/ce6c3a46742f139022514028081974fe42f40b42))
* Matrice des droits (#16) ([fe3a84](https://github.com/atos-df-rennes/prevarisc-migration/commit/fe3a84b1cea23a2aeb9ecb0606e4d88374632501))
* Modification d'un utilisateur (#15) ([4328d4](https://github.com/atos-df-rennes/prevarisc-migration/commit/4328d4d225112a81920ba0db834cfa880600d455))
* Date de dernière action utilisateur (#13) ([fdb7a9](https://github.com/atos-df-rennes/prevarisc-migration/commit/fdb7a9aa73893de4aae3bf79f128e5a2782d8e94))
* Pages d'erreur (#9) ([5bbb18](https://github.com/atos-df-rennes/prevarisc-migration/commit/5bbb182689c1bb77e057e2e193b8b6b8e2a4587f))
* Utilisation des voters en lieu et place de l'ACL (#8) ([ad945a](https://github.com/atos-df-rennes/prevarisc-migration/commit/ad945a273688fe828f7d9bc6262531a17eb8c391))
* Mise à jour du schéma de la base de données (#3) ([0c5fc6](https://github.com/atos-df-rennes/prevarisc-migration/commit/0c5fc6a18fe432ddd0a2d0c335a265bb2325088b))
* Authentification des utilisateurs (#7) ([ae7b05](https://github.com/atos-df-rennes/prevarisc-migration/commit/ae7b05f6bc6e628415af87f1de0141eb0298a8ea))
* Prise en compte des versions de Bootstrap pour chaque application (#5) ([2b50c0](https://github.com/atos-df-rennes/prevarisc-migration/commit/2b50c035590da3f2ba130bd15ca0f1629d1044ca))
* Pages d'à propos (#2) ([e39fc6](https://github.com/atos-df-rennes/prevarisc-migration/commit/e39fc6498d3db1989cd08c01a37b09a398b725b9))

##### Admin

* Pages de gestion des utilisateurs et des groupes (#11) ([dcaf9f](https://github.com/atos-df-rennes/prevarisc-migration/commit/dcaf9f4316e753fe1b4d0a40dfd39d551af374a9))
* Page d'informations (#10) ([4bf472](https://github.com/atos-df-rennes/prevarisc-migration/commit/4bf4729b00f91acad9f30c2eb3cc670fc91a4285))


---

