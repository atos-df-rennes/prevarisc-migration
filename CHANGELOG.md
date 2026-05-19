<!--- BEGIN HEADER -->
# Changelog


<!--- END HEADER -->

## [2.10.0-rc.1](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.18...v2.10.0-rc.1) (27-04-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/wand-magic-sparkles.svg" width="20" height="20"> Évolutions


##### Changelog

* Page changelog et bandeau de notification de mise à jour (#161) ([81ffef](https://github.com/atos-df-rennes/prevarisc-migration/commit/81ffef501171aeac344c937287070d5d9e39be74))

##### Dashboard

* Ajout du bloc dossiers incomplets (#153) ([dbc6cc](https://github.com/atos-df-rennes/prevarisc-migration/commit/dbc6cc4e00ac8b95a1b320a38a199598b6a6d34c))

##### Dossier

* Indicateur sur le dossier donnant avis d'un établissement (#163) ([6dcecf](https://github.com/atos-df-rennes/prevarisc-migration/commit/6dcecf8dd336abddc4b705f22bdce2a647628e78))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Les permissions à appliquer lors de l'installation n'étaient pas correctes (#152) ([e664f8](https://github.com/atos-df-rennes/prevarisc-migration/commit/e664f89bcbc90c60cb683c0eb549ec4cfe114569))
* Mauvais ordre d'exécution des instructions d'une migration (#148) ([0a09ef](https://github.com/atos-df-rennes/prevarisc-migration/commit/0a09efcd4ef0843692da4a96be5d76bb16770d51))

##### Accueil

* Mauvais affichage du contenu du bloc lors du clic (#180) ([3de300](https://github.com/atos-df-rennes/prevarisc-migration/commit/3de300ca1b77155ff7a17b6c81542eeca815851e))

##### Admin

* Correction des permissions d'accès aux pages ([f5f69c](https://github.com/atos-df-rennes/prevarisc-migration/commit/f5f69c9e732d6176c8260bae8a06b987addfa4c4))

##### Dossier

* Autorise les numéros d'intervention non-entiers (#165) ([f1f250](https://github.com/atos-df-rennes/prevarisc-migration/commit/f1f250b8be515d70725676295fb63eabb671e1b2))
* Vérifie qu'un dossier a bien une date de visite/de commission renseignée pour donner avis (#164) ([58a9cb](https://github.com/atos-df-rennes/prevarisc-migration/commit/58a9cbf3c490f3e3bdefd358341e6ff715d3bb3f))

##### Etablissement

* Ne plante pas si le type ou l'activité n'est pas renseigné (#136) ([bfcd18](https://github.com/atos-df-rennes/prevarisc-migration/commit/bfcd18b259a538dfab8419ed24c4266145f7c612))

##### Recherche

* Affiche les pièces d'un dossier au clic sur le bouton lié ([eeb14f](https://github.com/atos-df-rennes/prevarisc-migration/commit/eeb14fef931f5d1e1f2c144737fc65ab1afae20b))
* Présélectionne uniquement les groupements territoriaux de l'utilisateur ([dd8042](https://github.com/atos-df-rennes/prevarisc-migration/commit/dd80426f582bd12901d0d9310d0d01119f6b0d85))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/palette.svg" width="20" height="20"> Style

* Adapte correctement la hauteur de la barre de navigation latérale (#146) ([ced5fe](https://github.com/atos-df-rennes/prevarisc-migration/commit/ced5fe322aec9c03ba1a99b4a5ebd5dffddf02b2))
* Affichage de la table des matières sur grands écrans uniquement (#92) ([1eb521](https://github.com/atos-df-rennes/prevarisc-migration/commit/1eb521fc0b82b5cc0be8fd0c053123baac8e64ce))

##### Formulaires

* Les longs noms de boucle créaient un overflow sur la page (#108) ([963e6a](https://github.com/atos-df-rennes/prevarisc-migration/commit/963e6a914e7c89661c849c19aba9dea6aa18cdf2))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gears.svg" width="20" height="20"> Migration

* Page d'accueil (#168) ([8e88d5](https://github.com/atos-df-rennes/prevarisc-migration/commit/8e88d56cbf238f5701eff733ee67cf5c1e12acde))

##### Administration

* Gestion des commissions (#112) ([4fc579](https://github.com/atos-df-rennes/prevarisc-migration/commit/4fc579090d5410abb703ddd1866b3b3c3947b4f4))

##### Dossier

* Contacts (#162) ([a60cdc](https://github.com/atos-df-rennes/prevarisc-migration/commit/a60cdcde137b7fd89132280bc4578baa145da2fb))
* Documents consultés (#158) ([86efcc](https://github.com/atos-df-rennes/prevarisc-migration/commit/86efcc80bf99e0e51645d6ee4d589c31bb7f7149))
* établissements et dossiers liés (#156) ([76b1cb](https://github.com/atos-df-rennes/prevarisc-migration/commit/76b1cbd80b296f9f8ec22b4e5bb68db866276441))
* Ajout seul ou sur un établissement (#147) ([5bf53d](https://github.com/atos-df-rennes/prevarisc-migration/commit/5bf53d5ed56e5786aaf3f14b51c7133a7aba9b86))
* Reprise des effectifs et dégagements depuis une autre étude (#157) ([ab8e5a](https://github.com/atos-df-rennes/prevarisc-migration/commit/ab8e5ae568aedc54027cfd04752475448f1a9748))
* Copie et reprise des effectifs et dégagements avec l'établissement (#155) ([551254](https://github.com/atos-df-rennes/prevarisc-migration/commit/55125470d5a624d06c57893965379c871ac62c59))
* Onglet Prescriptions (#135) ([d57f5f](https://github.com/atos-df-rennes/prevarisc-migration/commit/d57f5ffca6f594d7d7a1eff6ac921d904995a75f))
* Onglet Textes applicables (#144) ([4cd7bc](https://github.com/atos-df-rennes/prevarisc-migration/commit/4cd7bc22d58c30dc037c42803738233b73cc8acc))
* Onglet descriptif (des travaux) (#143) ([8eb4f1](https://github.com/atos-df-rennes/prevarisc-migration/commit/8eb4f1bea87f098f646c66209c7002f2a6a8fd1f))

##### Etablissement

* Affichage et édition du descriptif original (#154) ([59c79a](https://github.com/atos-df-rennes/prevarisc-migration/commit/59c79ab6db9da0e1eb1f2ff17422890d36ed185e))
* Onglet Dossiers (#129) ([67e8f1](https://github.com/atos-df-rennes/prevarisc-migration/commit/67e8f16c9c6dd7a6573dcc221c6b005135f56e04))

##### Formulaires

* édition des valeurs (#160) ([ffd504](https://github.com/atos-df-rennes/prevarisc-migration/commit/ffd5047c417f9ee166730a44d14413496d78150f))
* Affichage des valeurs saisies (#150) ([4e77f6](https://github.com/atos-df-rennes/prevarisc-migration/commit/4e77f64a3478fe52056c647a175522072a06f560))

##### Recherche

* établissements et dossiers (#166) ([7c621d](https://github.com/atos-df-rennes/prevarisc-migration/commit/7c621d06cd16f82d22730c89109af35239377167))

##### Tableau-periodicites

* Application à tous les établissements (#132) ([ae2310](https://github.com/atos-df-rennes/prevarisc-migration/commit/ae231088acc69b71b8da67174907879c91f0cfa5))


---

## [2.8.19](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.18...v2.8.19) (11-05-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/wand-magic-sparkles.svg" width="20" height="20"> Évolutions


##### Calendrier-des-commission

* Permet la génération de documents sur des manifestations temporaires (#209) ([15d4bb](https://github.com/atos-df-rennes/prevarisc-migration/commit/15d4bbe0f89d890c61f9ecded834964955b24e08))

##### Dashboard

* Ajout du bloc dossiers incomplets (#153) ([dbc6cc](https://github.com/atos-df-rennes/prevarisc-migration/commit/dbc6cc4e00ac8b95a1b320a38a199598b6a6d34c))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections


##### Dossier

* Autorise les numéros d'intervention non-entiers (#165) ([f1f250](https://github.com/atos-df-rennes/prevarisc-migration/commit/f1f250b8be515d70725676295fb63eabb671e1b2))


---

## [2.8.18](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.17...v2.8.18) (31-03-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections


##### Formulaires

* Corrige l'ordre d'affichage des éléments et la modification des positions des éléments (#151) ([fd0db9](https://github.com/atos-df-rennes/prevarisc-migration/commit/fd0db977b83a18dc329818eba2f03cfeb87aa145))
* La modification d'ordre des valeurs d'un champ Liste n'affectait pas les bonnes positions (#149) ([502493](https://github.com/atos-df-rennes/prevarisc-migration/commit/502493bc8970856698f9220c345b543f7b4a5fac))


---

## [2.8.17](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.16...v2.8.17) (24-03-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/wand-magic-sparkles.svg" width="20" height="20"> Évolutions


##### Rapports

* Ajoute les avis et dérogations d'une cellule dans les données de l'établissement parent (#121) ([b94e74](https://github.com/atos-df-rennes/prevarisc-migration/commit/b94e74e0d3e1c69f35ccb0a320012b7573c7b562))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections


##### Dossier

* Vérification du droit de verrouillage pour effectuer l'action (#125) ([63a5e7](https://github.com/atos-df-rennes/prevarisc-migration/commit/63a5e7274f5b288ba26a4835f786e7f4d09e24e3))

##### Etablissement

* Correction d'orthographe des libellés "Lycee" (#102) ([e1d0ee](https://github.com/atos-df-rennes/prevarisc-migration/commit/e1d0ee327d7edcc15e6b42be3f6c31f00eafc746))
* Affiche correctement les pièces jointes avec le SimpleFileDataStore (#124) ([8453b9](https://github.com/atos-df-rennes/prevarisc-migration/commit/8453b95a6d4e9cb511aaef7c3820627562923448))

##### Pieces-jointes

* Chemin de stockage par défaut incorrect (#119) ([50bc5e](https://github.com/atos-df-rennes/prevarisc-migration/commit/50bc5e01b2be09bc346cdd9bd18f604d9d10bd63))

##### Prescriptions

* L'ajout d'une prescription type sur un texte se faisait sur la catégorie (#142) ([da39e7](https://github.com/atos-df-rennes/prevarisc-migration/commit/da39e7a429c923425e4802db2b1b664410f2ea32))
* Permet l'ajout d'une prescription type à n'importe quel niveau de la hiérarchie (#120) ([a308e0](https://github.com/atos-df-rennes/prevarisc-migration/commit/a308e0bb0f590f33a7b36852c94764ee418f907f))


---

## [2.9.0-rc.1](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.15...v2.9.0-rc.1) (03-03-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gauge-high.svg" width="20" height="20"> Performances


##### Etablissement

* Optimisation des requêtes et affichages (#116) ([b93f82](https://github.com/atos-df-rennes/prevarisc-migration/commit/b93f821bd9b09e24e99d5c942ac1c531afdd78a6))

##### Formulaires

* Traitement par batch pour diminuer significativement le temps d'import avec une configuration volumineuse (#107) ([0997ca](https://github.com/atos-df-rennes/prevarisc-migration/commit/0997ca1a76a3e27110ebb0d03643f48cbef73cba))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Nom de colonne erroné pour les ressources ([11d4e4](https://github.com/atos-df-rennes/prevarisc-migration/commit/11d4e424a616282e211346c2126915174759e457))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/palette.svg" width="20" height="20"> Style

##### Formulaires

* Les longs noms de boucle créaient un overflow sur la page (#108) ([963e6a](https://github.com/atos-df-rennes/prevarisc-migration/commit/963e6a914e7c89661c849c19aba9dea6aa18cdf2))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gears.svg" width="20" height="20"> Migration


##### Administration

* Groupement des communes (#99) ([5439c3](https://github.com/atos-df-rennes/prevarisc-migration/commit/5439c39835fdb8c3506073ffb0e10d3566accffa))

##### Dossier

* Affichage et édition d'un dossier (#122) ([907e69](https://github.com/atos-df-rennes/prevarisc-migration/commit/907e69e24094654772db92fa9ccd3efb94844a3f))

##### Elements-supprimes

* Rétablissement des éléments (#111) ([d3d907](https://github.com/atos-df-rennes/prevarisc-migration/commit/d3d907c7358a96c072df09cab4bdf5aa27fa5a38))

##### Etablissement

* Affichage de la page établissement (#90) ([64db81](https://github.com/atos-df-rennes/prevarisc-migration/commit/64db814de91ac55896d12583e08238fcc17cf46f))


---

## [2.8.15](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.14...v2.8.15) (20-02-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/wand-magic-sparkles.svg" width="20" height="20"> Évolutions

##### Formulaires

* [#5006] choix du mode d'ouverture par défaut des rubriques (#300) ([3e282ff](https://github.com/atos-df-rennes/prevarisc/commit/3e282ffba3e271a024056c0dc07bf9374fe362f8))
* [#5005] copie/reprise des effectifs et dégagements entre établissement et dossier (#299) ([0be40ef](https://github.com/atos-df-rennes/prevarisc/commit/0be40ef0f045688b51ec0b732b0514129ad1dc7c))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections


##### Formulaires

* Harmonisation de l'ordre d'affichage des valeurs de liste (#115) ([5ba8ea](https://github.com/atos-df-rennes/prevarisc-migration/commit/5ba8ea2de4d9ac75a33c67386b88ab7fe5e74612))


---

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

