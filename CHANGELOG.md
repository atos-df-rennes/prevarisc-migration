<!--- BEGIN HEADER -->
# Changelog


<!--- END HEADER -->

## [3.0.0-rc.1](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.10.0...v3.0.0-rc.1) (05-08-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/wand-magic-sparkles.svg" width="20" height="20"> Évolutions

* Utilisation du fork OdtPHP (#358) ([6affb9](https://github.com/atos-df-rennes/prevarisc-migration/commit/6affb9396bcaf4a225f9d60c264102023b571e75))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gears.svg" width="20" height="20"> Migration

* Mise à jour vers Symfony 7.4 (#372) ([168906](https://github.com/atos-df-rennes/prevarisc-migration/commit/16890699435fb4dd08b94bdfc69f941463149a2c))
* Mise à jour vers PHP 8.5 (#370) ([c956ed](https://github.com/atos-df-rennes/prevarisc-migration/commit/c956eddaf7c8993500b8a2efaa893881a0b88fc8))
* Mise à jour vers PHP 8.4 (#369) ([c40329](https://github.com/atos-df-rennes/prevarisc-migration/commit/c403298b33c2ceeb3886e70cbac31c9fc3044db8))
* Mise à jour vers PHP 8.3 (#368) ([a34f17](https://github.com/atos-df-rennes/prevarisc-migration/commit/a34f17f8dde24d6a25b93c0f6a42377272fe20fb))
* Mise à jour vers PHP 8.2 (#367) ([d241a1](https://github.com/atos-df-rennes/prevarisc-migration/commit/d241a1be91b2b9d6eff44f6be981fb01206fefd7))
* Passage sur MariaDB LTS (#365) ([7e6f22](https://github.com/atos-df-rennes/prevarisc-migration/commit/7e6f225e43c0b0182fd8830031db3d32297af5c8))
* Mise à jour vers Symfony 6.4 (#364) ([0585e1](https://github.com/atos-df-rennes/prevarisc-migration/commit/0585e13d2f0057e71f6988ad302dc8255682923d))
* Mise à jour vers PHP 8.1 (#362) ([89f50c](https://github.com/atos-df-rennes/prevarisc-migration/commit/89f50c9aa2791c6fe420aa323e62e421b80dcc7f))
* Utilisation des attributs au lieu des annotations (#361) ([bcb92b](https://github.com/atos-df-rennes/prevarisc-migration/commit/bcb92b5ecbe3c3201a2d676505ecdcdfb10634d9))
* Mise à jour vers PHP 8.0 (#360) ([6f15ba](https://github.com/atos-df-rennes/prevarisc-migration/commit/6f15ba3ae61247dfd8c3a32deacc4117767a9efc))
* Mise à jour vers Symfony 5.4 (#357) ([9f4302](https://github.com/atos-df-rennes/prevarisc-migration/commit/9f4302048120f50520888685f94a69ecc4c32d86))
* Mise à jour vers PHP 7.4 (#356) ([1666c8](https://github.com/atos-df-rennes/prevarisc-migration/commit/1666c8d83d86f77d169fb86c6b27dee3b5fefddf))
* Mise à jour vers PHP 7.3 (#355) ([c93e43](https://github.com/atos-df-rennes/prevarisc-migration/commit/c93e43a727fa205c0f7e6cb55d062c3da2186d51))
* Mise à jour vers PHP 7.2 (#354) ([69f420](https://github.com/atos-df-rennes/prevarisc-migration/commit/69f4208ddd031e8cc3fe9a7c1288f1b69b27de7a))
* Retrait de l'authentification Zend et des redirections pour vidage de cache Zend (#352) ([cc981d](https://github.com/atos-df-rennes/prevarisc-migration/commit/cc981d1639eb57353698deddd4ff9b0099e13ba2))

##### Calendrier-des-commissions

* Téléchargement des pièces jointes (#228) ([5cc6ce](https://github.com/atos-df-rennes/prevarisc-migration/commit/5cc6ce2c8480881e83a439ba3a670f67f31092b6))


---

## [2.10.0](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.19...v2.10.0) (08-07-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/wand-magic-sparkles.svg" width="20" height="20"> Évolutions

##### Platau

* Meilleur affichage des informations retournées par le healthcheck (maintenance en cours/prévue, informations de version) (#275) ([824f32](https://github.com/atos-df-rennes/prevarisc-migration/commit/824f3294be85104b7ced75a3857fdb3ce62818a0))

##### Recherche

* Ajout de colonnes dans l'export de données (#232) ([f59f4b](https://github.com/atos-df-rennes/prevarisc-migration/commit/f59f4bcbf178b5bbd28964a3a9c1a032d77a1e58))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

##### A-propos

* Affiche une erreur explicite si un document recherché n'existe pas (#349) ([b8a09e](https://github.com/atos-df-rennes/prevarisc-migration/commit/b8a09e56426c39e201286cec09e81aa90b180d15))
* Référence correctement le fichier de contribution (#348) ([b7cca6](https://github.com/atos-df-rennes/prevarisc-migration/commit/b7cca65c74bed390e6408b5609a6e8db8e4ba460))
* Correction orthographique "Développeurs" (#347) ([f8d48b](https://github.com/atos-df-rennes/prevarisc-migration/commit/f8d48b582520ca51566230dfa38327865350db55))

##### Administration

* Limite l'affichage des utilisateurs connectés à 1000 (#344) ([514773](https://github.com/atos-df-rennes/prevarisc-migration/commit/514773139e1402fb04b1f2ded6ebce583e93f8b1))
* Affiche correctement le paramétrage de la projection Geoconcept (#343) ([2bc201](https://github.com/atos-df-rennes/prevarisc-migration/commit/2bc2017b731949eb0fc040a7b8654a4deecda352))
* Timeout de 3s sur la vérification de nouvelle version (#341) ([532789](https://github.com/atos-df-rennes/prevarisc-migration/commit/532789103853bfb030d93d7a5395165448ac4cee))

##### Authentification

* Mise à jour de la date de dernière action utilisateur lors de la déconnexion (#262) ([62a51a](https://github.com/atos-df-rennes/prevarisc-migration/commit/62a51a8531b2bf7b96a7e5baa8561bb2952e1521))
* Correction des paramètres de connexion LDAP (#233) ([70abb9](https://github.com/atos-df-rennes/prevarisc-migration/commit/70abb96e8848eda63105fa832ff0be54b7a13b09))
* Prend en compte la valeur incorrecte "HTTP" pour le protocole proxy (#231) ([db9d4f](https://github.com/atos-df-rennes/prevarisc-migration/commit/db9d4f8e31cd5255be16b3cd890e4f2d5b3f8bc9))

##### Commissions

* Suppression des documents associés lors de la suppression d'un membre (#254) ([74f5c6](https://github.com/atos-df-rennes/prevarisc-migration/commit/74f5c65e548b47e029f82e9f8cab158b6fcd995b))
* Bouton pour cocher/décocher tous les types d'activité ERP et natures de dossier (#241) ([b0ff61](https://github.com/atos-df-rennes/prevarisc-migration/commit/b0ff61d32cbdf2079f34f8332eec3942b45f5abf))

##### Communes

* Fonction fixée à maire pour les informations utilisateur (#313) ([02d526](https://github.com/atos-df-rennes/prevarisc-migration/commit/02d5265ca693da3d64c0005740b6fd5057067feb))
* Retour sur la commune concernée après sauvegarde (#312) ([52acbb](https://github.com/atos-df-rennes/prevarisc-migration/commit/52acbb508092d2b7914fa307451441f7f814b17c))

##### Contacts

* Augmente la limite du nombre de résultats retournés à 100 (#273) ([4b2b9e](https://github.com/atos-df-rennes/prevarisc-migration/commit/4b2b9e77bfc848c04775571775f0c5dfe00cfaae))
* Affiche les contacts des parents et grand-parents d'une cellule (#272) ([061513](https://github.com/atos-df-rennes/prevarisc-migration/commit/0615136ee94e4c2d98b7f2cd247d2e6174860b49))
* Rend la fonction Maire disponible lors de l'édition d'un contact (#271) ([29504f](https://github.com/atos-df-rennes/prevarisc-migration/commit/29504fec96636b6d940ee7bf7307509e64b4faf4))
* Sécurise la vérification d'un contact étant toujours utilisé avant suppression (#256) ([264209](https://github.com/atos-df-rennes/prevarisc-migration/commit/264209195ca1fbf0d0025de4f3b409e649f81184))

##### Couches-cartographiques

* Validation des valeurs lors du réordonnancement des couches (#305) ([0ee2ae](https://github.com/atos-df-rennes/prevarisc-migration/commit/0ee2aeed87bc0b35033e94f2d159717e4d8d0f82))

##### Dossier

* Absence du champ "État du dossier" (#248) ([6b5460](https://github.com/atos-df-rennes/prevarisc-migration/commit/6b5460c1d465073e5447768b227a4706d5b71005))
* Vérifie le verrouillage avant d'effectuer une action et vérifie la connexion Plat'AU pour l'affichage des informations (#230) ([9fc954](https://github.com/atos-df-rennes/prevarisc-migration/commit/9fc95426e05b9c366ca8c6351ab4f92e6efbdf4a))

##### Elements-supprimes

* Affiche les dates en format lisible (#337) ([0e00f2](https://github.com/atos-df-rennes/prevarisc-migration/commit/0e00f2666e2406f5b3568dc68112a01d60d7d9f9))
* N'affiche que les onglets autorisés par les permissions de l'utilisateur (#336) ([cac35e](https://github.com/atos-df-rennes/prevarisc-migration/commit/cac35ea21f50835bcf9d4868dec0a14a0ecab699))

##### Formulaires

* Affichage des dates en texte (#302) ([ccbf13](https://github.com/atos-df-rennes/prevarisc-migration/commit/ccbf1351f1558aa3c46a318125eaa76c3c3613fe))
* écrasement des données entre les onglets (#235) ([2af4ff](https://github.com/atos-df-rennes/prevarisc-migration/commit/2af4ffc3c0ea683ac7945985b1abc51d48c58947))
* Affiche correctement les rubriques visibles (#234) ([f8e934](https://github.com/atos-df-rennes/prevarisc-migration/commit/f8e9342a62c40c7d44f22219f7f68af858c9e46a))

##### Fusion-des-communes

* Aligne les permissions nécessaires sur celles de la gestion des communes (#320) ([18bc4d](https://github.com/atos-df-rennes/prevarisc-migration/commit/18bc4d216ca5a3686f07a5ce8c9aa4f320e94e8c))
* Affiche les messages de succès/échec/succès partiel à l'utilisateur (#318) ([34bb54](https://github.com/atos-df-rennes/prevarisc-migration/commit/34bb5494b88864bfd62161fbf53eb8d1a2ef96c3))
* Validations sur le fichier d'entrée (#315) ([ef206c](https://github.com/atos-df-rennes/prevarisc-migration/commit/ef206cdacaa47d074cd47cb78cd3c22df30a602b))
* Supprimes les règles de commission d'une commune fusionnée (#310) ([fe75d5](https://github.com/atos-df-rennes/prevarisc-migration/commit/fe75d5bcd78c3fd0f8061d68ca5b4b39f10aaa98))
* Une commune fusionnée n'est plus liée au groupement (#309) ([e6672c](https://github.com/atos-df-rennes/prevarisc-migration/commit/e6672c66427b766d43ea0bc94886c8a75e4b4ac1))

##### Gestion-des-documents

* Indicateur de chargement du document (#324) ([7c8795](https://github.com/atos-df-rennes/prevarisc-migration/commit/7c8795b73cf03f011296653ad4bad706c9c3c7a1))
* N'affiche que les documents ODT (#323) ([7db55e](https://github.com/atos-df-rennes/prevarisc-migration/commit/7db55e2bb2664cc9800081ab850e6e943f0b0b47))
* Affichage sous forme de panneaux dépliables (#321) ([18ba86](https://github.com/atos-df-rennes/prevarisc-migration/commit/18ba86a73e448ebb0151cf04724a6d31bf5470cd))

##### Groupements

* Validation serveur de la condition d'affectation des préventionnistes (#270) ([350b67](https://github.com/atos-df-rennes/prevarisc-migration/commit/350b67227cea7ebc5f7b5cc1706d4d8a02785526))
* Garantie du tri par type puis libellé (#268) ([122306](https://github.com/atos-df-rennes/prevarisc-migration/commit/1223066a4116a311a21b61384abecbf68f4b2d16))
* Invalide le cache de recherche après une réaffectation de préventionniste (#267) ([e8bc2d](https://github.com/atos-df-rennes/prevarisc-migration/commit/e8bc2dec0340f2b09e973791af5bb61407cd0695))
* Ajout de la fiche d'informations d'un groupement (#264) ([549259](https://github.com/atos-df-rennes/prevarisc-migration/commit/54925922fd6892363bcb7d29c2ee62c70722fdfc))
* Sélection du groupement après sa création (#263) ([1f6bbe](https://github.com/atos-df-rennes/prevarisc-migration/commit/1f6bbef11894f23b49e332f48760e8e34d015bb8))

##### Migrations

* Ajout de gardes en cas de rejeu de migration (#240) ([fd83a1](https://github.com/atos-df-rennes/prevarisc-migration/commit/fd83a12b5cfddcd4425dd370520b13b435902e99))

##### Periodicite

* Validation bornée 0-999 mois (#291) ([57c51a](https://github.com/atos-df-rennes/prevarisc-migration/commit/57c51ab4bcefa3e4773502d8ca5df8cf0c162771))

##### Permissions

* Vérifie le droit de création d'un établissement lors de la modification d'un site (#261) ([98d157](https://github.com/atos-df-rennes/prevarisc-migration/commit/98d157c71f0ce3c0cd08c5173351ec3a48520970))
* Vérifie le droit d'accès à au moins un établissement lié pour un dossier en plus de son propre droit (#258) ([e666c0](https://github.com/atos-df-rennes/prevarisc-migration/commit/e666c066d6885fb29e674149c73cb1088f170736))
* Les permissions liées aux dossiers ne doivent pas être traitées de manière globales (#238) ([96d250](https://github.com/atos-df-rennes/prevarisc-migration/commit/96d2507e088e7d3c8244bad808c45ff4a164625e))
* Permissions manquantes ou trop larges sur certaines actions (#236) ([22686f](https://github.com/atos-df-rennes/prevarisc-migration/commit/22686fe39248a8ec5914a9b5d492bbbb6034fadf))

##### Pieces-jointes

* Mauvaise utilisation des placements et création de trop de miniatures (#281) ([1a02d6](https://github.com/atos-df-rennes/prevarisc-migration/commit/1a02d60b43328a76a4a0ab1dceb16655c4b56872))

##### Platau

* Indicateur de vérification de la santé (#297) ([697fd0](https://github.com/atos-df-rennes/prevarisc-migration/commit/697fd02d8d550053ad3c662313aaf9ce3d5fa07f))

##### Prescriptions

* Ajoute les contrôles de validation lors de la reprise de prescriptions et de la suppression de prescriptions types (#255) ([b63d5e](https://github.com/atos-df-rennes/prevarisc-migration/commit/b63d5e4bbcd17c0a09c6ca9fe98bde275739cf67))

##### Recherche

* Réintroduction du cache sur les recherches répétées (#250) ([cd6284](https://github.com/atos-df-rennes/prevarisc-migration/commit/cd6284711bbcf4b6e85a356fcfe4a0139bb3c6a6))

##### Textes-applicables

* Affiche un toast utilisateur lors d'un ordonnancement (#288) ([c14e1d](https://github.com/atos-df-rennes/prevarisc-migration/commit/c14e1d3c2c2c1a162acce305f55e65fe88d8dd03))
* Mise en gras des textes déjà sélectionnés (#287) ([ec815e](https://github.com/atos-df-rennes/prevarisc-migration/commit/ec815ee78a02b9411bad96d0528b7bf9d07929c2))
* Affichage de l'erreur à l'utilisateur si la sauvegarde n'a pas pu être effectuée (#286) ([d3b0a7](https://github.com/atos-df-rennes/prevarisc-migration/commit/d3b0a7657eefb3fc2a21f7675a35f4998950cc88))
* Synchronisation du dossier vers l'établissement pour les visites et groupes de visite (#284) ([0dd0b2](https://github.com/atos-df-rennes/prevarisc-migration/commit/0dd0b2d4d6aff40ee9a41d70934855d3da0f6024))

##### Utilisateurs

* Nullifie le mot de passe pour les comptes SSO (#257) ([39b6ea](https://github.com/atos-df-rennes/prevarisc-migration/commit/39b6eaadd381c1ef6ff6a097c4bb70d04b7a0ca1))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gears.svg" width="20" height="20"> Migration

##### Dossier

* Suppression logique d'un dossier (#253) ([93acf5](https://github.com/atos-df-rennes/prevarisc-migration/commit/93acf5648dab7d47b9d3145f6448403fd91d85e1))

##### Pieces-jointes

* Téléchargement des pièces jointes des dates de commission (#280) ([a6aa9b](https://github.com/atos-df-rennes/prevarisc-migration/commit/a6aa9b242600f928535bbd70ba782403c218ac49))


---

## [2.8.20](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.19...v2.8.20) (08-07-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Corrige la structure de la base et les données erronées avant application des migrations ([a93810](https://github.com/atos-df-rennes/prevarisc-migration/commit/a938105d2c2a3a49446dfe2ff078c269c07e6339))

##### Couches-carto

* Affichage/masquage des couches et nommages corrects (#276) ([2480b3](https://github.com/atos-df-rennes/prevarisc-migration/commit/2480b3c1f75c2666767ce1e4329c07be86c7cbea))


---

## [2.10.0-rc.2](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.8.19...v2.10.0-rc.2) (28-05-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gauge-high.svg" width="20" height="20"> Performances

* Sert les routes dans un fichier statique au lieu de faire une requête (#205) ([0d1f3b](https://github.com/atos-df-rennes/prevarisc-migration/commit/0d1f3bbd66efa3f79193f76aa6ab8fd4e4e164cd))

##### Accueil

* Optimisation des requêtes des blocs (#194) ([93b3db](https://github.com/atos-df-rennes/prevarisc-migration/commit/93b3db8b9fbf635d7225ae65b2bba067c68dc72d))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

##### Accueil

* Erreur 500 / boucle d'authentification / assets non chargés (#204) ([643a14](https://github.com/atos-df-rennes/prevarisc-migration/commit/643a14db9e41d316523991f48ad620e1d770c2e9))

##### Dossier

* La sauvegarde des documents d'urbanisme n'étaient pas prise en compte (#224) ([3d1f5b](https://github.com/atos-df-rennes/prevarisc-migration/commit/3d1f5bf4a2a1c24c20694b1268442e807490a55d))
* Correction sur la sauvegarde des préventionnistes (#223) ([4cd8d6](https://github.com/atos-df-rennes/prevarisc-migration/commit/4cd8d6ae3198c033a11bbb46f07bacab3c5bb74e))
* Les renvois de pec et d'avis Plat'AU n'étaient pas pris en compte (#202) ([ff2191](https://github.com/atos-df-rennes/prevarisc-migration/commit/ff2191d58e99931ee42e3c6c78b02282a99d7b33))

##### Etablissement

* Supprimer la propagation catégorie/périodicité aux cellules (#225) ([ec6cdb](https://github.com/atos-df-rennes/prevarisc-migration/commit/ec6cdb935eb54c0502dd56abb7826536ca9e7039))
* Ordonne les établissements liés via leur libellé ([d4248f](https://github.com/atos-df-rennes/prevarisc-migration/commit/d4248fe82fef370608bb08292cd9f455296e976e))
* Ne prend en compte que les utilisateurs actifs ([f3a57d](https://github.com/atos-df-rennes/prevarisc-migration/commit/f3a57ddc9074bde1df75359185b051ed1fafae1d))

##### Formulaires

* Supprime les espaces superflus lors de la recherche des champs par nom (#222) ([76b7fc](https://github.com/atos-df-rennes/prevarisc-migration/commit/76b7fcaaba322791b53e4331a406f98497f85f06))

##### Recherche

* Affichage d'un dossier sans établissement (#196) ([7f0b4c](https://github.com/atos-df-rennes/prevarisc-migration/commit/7f0b4cf22157a237bdddb7c87a6828fb4e675224))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/palette.svg" width="20" height="20"> Style

* Affichage de sélection des villes pour les groupements de communes plus repsonsive (#199) ([a85aaa](https://github.com/atos-df-rennes/prevarisc-migration/commit/a85aaa120ad6807619ec81a9297fe9cedf2557c8))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gears.svg" width="20" height="20"> Migration

* Fonctionnement avec un proxy (#206) ([7a9c67](https://github.com/atos-df-rennes/prevarisc-migration/commit/7a9c672bb3baba3aa83d58b58f9dc26735d8ccd0))
* Authentification (#201) ([296994](https://github.com/atos-df-rennes/prevarisc-migration/commit/296994d9195a02711bddd46fecd7c0b80f2f1c83))
* Suppression du menu Statistiques inutilisé (#197) ([554764](https://github.com/atos-df-rennes/prevarisc-migration/commit/55476411c5e3210c1b7fd0fe5a276c5aa1f9f359))

##### Calendrier-des-commissions

* Génération des documents de commission (#221) ([19bd3c](https://github.com/atos-df-rennes/prevarisc-migration/commit/19bd3c5876fe9c1fb56cd0f66538a5d489076361))
* Affichage et gestion des ordres du jour (#187) ([b7e296](https://github.com/atos-df-rennes/prevarisc-migration/commit/b7e2967f53b2c5d9a629507b7c3799ec96992c80))

##### Dossier

* Génération du rapport (#203) ([93c27d](https://github.com/atos-df-rennes/prevarisc-migration/commit/93c27d061bd72660e1b7486ced2f6229043defea))
* Onglet Pièces Jointes (#200) ([43b60a](https://github.com/atos-df-rennes/prevarisc-migration/commit/43b60a1e22cfa69188263f7384c1a623566d35a0))
* Avis et dérogations (#192) ([c059c2](https://github.com/atos-df-rennes/prevarisc-migration/commit/c059c2d6dcac5820f25b3b361047d4ec17bc49ad))

##### Etablissement

* Suppression logique d'un établissement (#226) ([127209](https://github.com/atos-df-rennes/prevarisc-migration/commit/127209838c7a878ea15ded68ecea3fff5b9090e8))
* édition d'un établissement (#218) ([2ad97d](https://github.com/atos-df-rennes/prevarisc-migration/commit/2ad97d115d8553570056260c829d8128de913a92))
* Ajout d'un établissement (#215) ([1fa3b4](https://github.com/atos-df-rennes/prevarisc-migration/commit/1fa3b4b0c3f667aa8e6d196b3f73d25085708fac))
* Gestion des pièces jointes (#190) ([9a5aa0](https://github.com/atos-df-rennes/prevarisc-migration/commit/9a5aa0eaa167b76217b7185e7277452e9124650f))


---

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

