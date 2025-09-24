<!--- BEGIN HEADER -->
# Changelog


<!--- END HEADER -->

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

