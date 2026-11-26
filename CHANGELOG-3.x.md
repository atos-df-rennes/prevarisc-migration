<!-- BEGIN HEADER -->
# Changelog


<!-- END HEADER -->

## [3.0.2](https://github.com/atos-df-rennes/prevarisc-migration/compare/v3.0.1...v3.0.2) (26-11-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Sécurise l'ajout de clé étrangère et d'index (#488) ([0ef6af](https://github.com/atos-df-rennes/prevarisc-migration/commit/0ef6afd4f726894e352f3b9a377bdf9991978888))

##### Assets

* Utilise la bonne version applicative pour invalidation (#486) ([40c6fa](https://github.com/atos-df-rennes/prevarisc-migration/commit/40c6fa85059752ec81ff1b981f7e49304a56b93c))

##### Authentification

* Prise en charge de ldaps (#492) ([36280c](https://github.com/atos-df-rennes/prevarisc-migration/commit/36280cdfdad8a5d8fb0944c6c9ac6d5e5a927426))
* La case "Connexion via LDAP/CAS/NTLM" n'était pas correctement cochée/décochée (#482) ([268a7f](https://github.com/atos-df-rennes/prevarisc-migration/commit/268a7fa5282f54f47c71eb673b01e3b065e05c01))

##### Dossier

* Normalise les créateurs invalides (#485) ([1b3dfe](https://github.com/atos-df-rennes/prevarisc-migration/commit/1b3dfe70e4a34ed138318203da6e01e37c4556ed))

##### Pieces-jointes

* Affiche un message d'erreur lisible (#489) ([119223](https://github.com/atos-df-rennes/prevarisc-migration/commit/119223020e77d6da14cdfdc545f03f2038bf8897))
* Remise en état des droits d'ajout historiques (#487) ([2de4bd](https://github.com/atos-df-rennes/prevarisc-migration/commit/2de4bd2c1b33de82ab55eda47ad32cb1b4fe6a38))

##### Rapports

* Message d'erreur lors de l'ouverture d'un document avec LibreOffice > 7 (#423) ([941bef](https://github.com/atos-df-rennes/prevarisc-migration/commit/941befcab595329a0ea5e983a4495393cc8be40f))


---

## [3.0.0](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.10.8...v3.0.0) (17-11-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* La migration des données écrasaient des résultats valides (#480) ([d0744f](https://github.com/atos-df-rennes/prevarisc-migration/commit/d0744f8170129d1c2988877703d3cee9dd1a3449))
* Suppression de fichiers temporaires non effacés ([230008](https://github.com/atos-df-rennes/prevarisc-migration/commit/230008ada912bcd7545657b3b140de16dcb7916d))
* Modification de table avant sa création ([c758a3](https://github.com/atos-df-rennes/prevarisc-migration/commit/c758a397c7887962e5800e453eca36137048aab5))
* Traite les enums MySQL comme des chaînes si existantes dans le schéma ([646cb6](https://github.com/atos-df-rennes/prevarisc-migration/commit/646cb610781cbde64b99a99c28dca67c4d4a90bb))

##### Administration

* Surbrillance de l'onglet sur l'édition des couches cartographiques ([d680a6](https://github.com/atos-df-rennes/prevarisc-migration/commit/d680a64288bef0d7c9542bfe5f3151cfdc65773c))

##### Dossier

* Libellé secrétariat incorrect ([c70a81](https://github.com/atos-df-rennes/prevarisc-migration/commit/c70a811a364cb99e4786f4967f4ffb3a01fdad21))

##### Ressources-specialisees

* Permet l'exclusion de certains types d'activités (#481) ([0cc274](https://github.com/atos-df-rennes/prevarisc-migration/commit/0cc27436aa146c9ce7ad4a4014ea04994bba0cab))

##### Version

* Affiche la page des changelogs par version majeure (#468) ([bc9475](https://github.com/atos-df-rennes/prevarisc-migration/commit/bc94753a5c233f66b7e34ba872b56f776b4b536a))
* Détection via le bon dépôt et les nouvelles conventions (#467) ([54b550](https://github.com/atos-df-rennes/prevarisc-migration/commit/54b550ec53b66e9fda6a1056f182e6614ac6a2b4))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/palette.svg" width="20" height="20"> Style


##### Ressources-specialisees

* Le style n'était pas pris en compte sur l'affichage ([3a8db7](https://github.com/atos-df-rennes/prevarisc-migration/commit/3a8db76d69c3d763c9f8a760bed7de8c02399f4d))


---

## [3.0.0-rc.4](https://github.com/atos-df-rennes/prevarisc-migration/compare/v3.0.0-rc.3...v3.0.0-rc.4) (23-10-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Règles de mapping manquantes pour les formulaires personnalisés (#465) ([201e44](https://github.com/atos-df-rennes/prevarisc-migration/commit/201e449d3fdca8a5a26c8a920b8fa95489d8b538))
* Mapping erroné par rapport à la définition de la base de données (#464) ([6aae4a](https://github.com/atos-df-rennes/prevarisc-migration/commit/6aae4a83ec1dfb994466ed0470722cba367814e7))


---

## [3.0.0-rc.3](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.10.8...v3.0.0-rc.3) (19-10-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/wand-magic-sparkles.svg" width="20" height="20"> Évolutions


##### Platau

* Indication de saisie Prevarisc non encore transmise à Plat'AU (#446) ([0c0bcb](https://github.com/atos-df-rennes/prevarisc-migration/commit/0c0bcb90209e9facb5fb8daebf83d007c9651d92))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Normalise les valeurs des rôles utilisateur (#455) ([c12639](https://github.com/atos-df-rennes/prevarisc-migration/commit/c12639ae10bb9eeef583d77f47fdbcd2adff185b))
* Barre de recherche réduite au focus en tablette (#444) ([d2d384](https://github.com/atos-df-rennes/prevarisc-migration/commit/d2d384c29d24bdd36ede37944ac4e6b416a6354c))
* Mauvais affichage de la catégorie sur les cellules (#437) ([455a33](https://github.com/atos-df-rennes/prevarisc-migration/commit/455a33937cfe6e34b277894ede7dbc64380bf418))

##### Cartographie

* Mauvaise interprétation de l'autoconfiguration locale (#438) ([796244](https://github.com/atos-df-rennes/prevarisc-migration/commit/796244a93fd914ace40081299e8e5131b88c2c62))

##### Commissions

* Formatage du nom de téléchargement des fichiers (#440) ([a0954a](https://github.com/atos-df-rennes/prevarisc-migration/commit/a0954aad4fa505794adbbd034eef7949b4ceeca9))

##### Dossier

* Utilise le type de service instructeur pour le rapport (#448) ([61128c](https://github.com/atos-df-rennes/prevarisc-migration/commit/61128ce49243f85496333d527a52ddbcc0023109))
* Présélection des préventionnistes de l'établissement (#426) ([03a3b2](https://github.com/atos-df-rennes/prevarisc-migration/commit/03a3b221bf9379977a4ddef685d608db82e653b0))

##### Etablissement

* Affiche correctement les icônes des types (#443) ([0ad5d7](https://github.com/atos-df-rennes/prevarisc-migration/commit/0ad5d7c7b424767b373a85aa98db26319b6b5624))
* Mauvais préventionnistes suggérés (#430) ([531f6d](https://github.com/atos-df-rennes/prevarisc-migration/commit/531f6dad2964eb2d7b6c3cab175a6d3f66d27633))

##### Groupements

* Libellé du bouton d'affectation qui correspond à l'action effectuée (#460) ([4e8768](https://github.com/atos-df-rennes/prevarisc-migration/commit/4e876814fe8ac4c3ce5d895098b58b7d1b8a35c5))

##### Privileges

* Erreur lors de la création de ressources spécialisées (#432) ([128c5e](https://github.com/atos-df-rennes/prevarisc-migration/commit/128c5e432e890df362625c9f57fc8a4b0f9a841e))

##### Rapport

* Chemins d'accès aux modèles indiqués invalides avec un partage Windows (#439) ([8553a5](https://github.com/atos-df-rennes/prevarisc-migration/commit/8553a5826162e0e3ccb08d97d4c9b3608faee137))
* Récupère l'adresse de l'établissement en fonction de son genre (#427) ([9a2933](https://github.com/atos-df-rennes/prevarisc-migration/commit/9a29337b78c7b3723bbebbbef049d9de1a15d18c))
* Formatage du nom de téléchargement des fichiers (#441) ([c74fd2](https://github.com/atos-df-rennes/prevarisc-migration/commit/c74fd207d68e24ef4c93bd19eba70769f2837650))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/lock.svg" width="20" height="20"> Sécurité

* Utilisation d'un algorithme de hashage plus sécurisé pour les mots de passe (#422) ([06ac3b](https://github.com/atos-df-rennes/prevarisc-migration/commit/06ac3bc044ce6d81fcc85ac2fb556e6c884b7a58))


---

## [3.0.0-rc.2](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.10.6...v3.0.0-rc.2) (16-09-2026)

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gauge-high.svg" width="20" height="20"> Performances

* Diverses optimisations de chargement de liste (#398) ([c005b3](https://github.com/atos-df-rennes/prevarisc-migration/commit/c005b3506cf00986b32ad8a2e56b1087422ab640))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/bug.svg" width="20" height="20"> Corrections

* Aligne le format des dates dans les documents générés (#387) ([c5af22](https://github.com/atos-df-rennes/prevarisc-migration/commit/c5af2211f48880f6be306784f2f67751d53b5a42))
* La génération des documents ne fonctionnait pas si la variable PREVARISC_REAL_DATA_PATH contenait un slash de fin (#376) ([dce73d](https://github.com/atos-df-rennes/prevarisc-migration/commit/dce73d171aff52537e00ffc765af05728eac4da6))
* Crash applicatif sur une troncature au milieu d'un caractère accentué (#375) ([6c001d](https://github.com/atos-df-rennes/prevarisc-migration/commit/6c001daf351e66142438a2ec198cbd8055237aec))

##### Administration

* Affiche le chemin vers les fichiers tel qu'utilisé ([aaea7c](https://github.com/atos-df-rennes/prevarisc-migration/commit/aaea7c642ae4d246d98afa48ebb08c1b78f14f9e))

##### Adresse

* Affiche la bonne adresse d'une cellule sur sa fiche, dans l'odj et lors de la génération des documents de commission (#384) ([a8564d](https://github.com/atos-df-rennes/prevarisc-migration/commit/a8564d6c34d2e056ec057e0a694a0805fe82189d))

##### Assets

* Utilise le numéro de version applicatif pour invalider la mise en cache (#390) ([fd8309](https://github.com/atos-df-rennes/prevarisc-migration/commit/fd8309fba853c7bec06121ffa5412a149f92325a))

##### Avis-derogations

* Affiche l'indicateur en prenant en comtpe les cellules (#411) ([90d3e1](https://github.com/atos-df-rennes/prevarisc-migration/commit/90d3e18ab44370edd45045f70e7b1cb202aec068))

##### Commission

* Prend en compte l'ensemble des valeurs indiquant qu'un document n'est pas renseigné (#412) ([91db8d](https://github.com/atos-df-rennes/prevarisc-migration/commit/91db8d645766de2d9679fc81303883d0d42599f5))
* VTIMEZONE manquant dans l'export ICS Outlook (#407) ([966b0e](https://github.com/atos-df-rennes/prevarisc-migration/commit/966b0e9e07a0a12aee72951e9971520f3c3e8c6f))

##### Dashboard

* Erreur de chargement sur certains blocs (#399) ([fab5dc](https://github.com/atos-df-rennes/prevarisc-migration/commit/fab5dc906404187500b73adb501a5738055b24d9))

##### Documents

* Préserve les chemins d'accès UNC Windows (#381) ([c6d49c](https://github.com/atos-df-rennes/prevarisc-migration/commit/c6d49cba74e1583bec4238d2e64f24602e7fc758))

##### Documents-consultes

* Les champs référence et date ne sont pas obligatoires (#382) ([6d7ddf](https://github.com/atos-df-rennes/prevarisc-migration/commit/6d7ddfabd50052e171f54f966d1958a99f564c31))

##### Dossier

* Nettoie les informations de commission invalides et rend la relation plus stricte (#420) ([40c55a](https://github.com/atos-df-rennes/prevarisc-migration/commit/40c55a16c725848153fc74766c1be920fda98f66))
* Autorise l'utilisation des prescriptions sur les études Dérogation (#409) ([034d26](https://github.com/atos-df-rennes/prevarisc-migration/commit/034d261d840dd12bce8e0cfba94c027882c7740f))
* Affiche/masque l'onglet "Prescriptions" en fonction du type et de la nature (#408) ([ba8174](https://github.com/atos-df-rennes/prevarisc-migration/commit/ba8174248645a74162b26da9ecfddc1f524ef24a))
* Le champ "Objet" n'est pas obligatoire pour certaines natures (#395) ([9063b8](https://github.com/atos-df-rennes/prevarisc-migration/commit/9063b83d8d7cd7c5a378230c3f57208636fbdfad))
* Bouton pour effacer les dates de visite/commission (#394) ([623363](https://github.com/atos-df-rennes/prevarisc-migration/commit/623363d08d87cbb06f0133cf4ad2d02e50a23e73))
* Crash lors d'une indication d'incomplétude (#392) ([8a6de0](https://github.com/atos-df-rennes/prevarisc-migration/commit/8a6de07cee16ec7946a0d268e0c0ce1a6e515814))
* Utilise les bonnes permissions pour accéder aux pièces jointes (#388) ([0a8746](https://github.com/atos-df-rennes/prevarisc-migration/commit/0a8746dee1d77a6703e92cc33262a5b7f89ba32c))
* Vérifie correctement les permissions d'accès aux dossiers d'un site (#386) ([323ef0](https://github.com/atos-df-rennes/prevarisc-migration/commit/323ef0c71bb78d945a41f55d2104ffd6c1821e59))
* Présélectionne la commission de l'établissement à la création (#385) ([fb0722](https://github.com/atos-df-rennes/prevarisc-migration/commit/fb07224480f154b0b18db05baa491ad4a2f46ff6))
* Permet la sélection d'une commune comme service instructeur (#383) ([9cb874](https://github.com/atos-df-rennes/prevarisc-migration/commit/9cb874ca40cae13d905a2af8b9dc58f37ccbc320))

##### Etablissement

* Conserve l'activité sauvegardée lors de la modification (#406) ([cf4d4b](https://github.com/atos-df-rennes/prevarisc-migration/commit/cf4d4b96146c88aca6b458ed1fa2604ca22d5456))
* Permet la suppression des rubriques ICPE sur toutes les tailles d'écran (#405) ([87adf3](https://github.com/atos-df-rennes/prevarisc-migration/commit/87adf3d2f1c56523249b43a8baeb58171a015a4f))
* Lien de création d'un établissement enfant manquant (#396) ([e0ca06](https://github.com/atos-df-rennes/prevarisc-migration/commit/e0ca06fe9596397c935a8a442273f09568e589bc))
* Affiche le bon libellé sur la liste des dossiers en fonction de leur date de création (#380) ([33c994](https://github.com/atos-df-rennes/prevarisc-migration/commit/33c9942d07cbdb08864c779e9fb2de05270f1454))

##### Pieces-jointes

* Crash lors du téléchargement d'une pièce contenant un slash dans son nom (#410) ([0f3cf0](https://github.com/atos-df-rennes/prevarisc-migration/commit/0f3cf070cf7c706aaeb0c559511901113549bba8))

##### Prescriptions

* Crash sur les pages de modification/remplacement (#415) ([21b1c5](https://github.com/atos-df-rennes/prevarisc-migration/commit/21b1c5ed0af157d8022711260b00ba40f21e51d6))

##### Rapport

* Aggrège les dates et heures de visite du dossier (#393) ([0a9bd3](https://github.com/atos-df-rennes/prevarisc-migration/commit/0a9bd31be9488425f507443b91923a460e81e2f1))
* Certaines balises avait toujours une chaîne vide pour valeur (#378) ([4d36d6](https://github.com/atos-df-rennes/prevarisc-migration/commit/4d36d6343ae6ec523ae89cb17894f3b67053dfb0))

##### Recherche

* Rétablissement de la recherche par numéro d'identifiant d'établissement et harmonisation sur tous les usages à travers l'application (#379) ([100d64](https://github.com/atos-df-rennes/prevarisc-migration/commit/100d64bc6dc84c653a5d3c4630268336548e4e88))
* Renvoie uniquement l'établissement correspondant à l'identifiant renseigné (#377) ([94ef91](https://github.com/atos-df-rennes/prevarisc-migration/commit/94ef91404b7970ee48dd967502d5dd502e2a7363))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/palette.svg" width="20" height="20"> Style

* Aligne le visuel du TomSelect de navigation globale avec les autres éléments (#389) ([f579da](https://github.com/atos-df-rennes/prevarisc-migration/commit/f579dad0d15d4259f00ac1499a1c5210adc43d63))

### <img src="https://raw.githubusercontent.com/FortAwesome/Font-Awesome/6.x/svgs/solid/gears.svg" width="20" height="20"> Migration


##### Etablissement

* Route d'aperçu (#401) ([ae136e](https://github.com/atos-df-rennes/prevarisc-migration/commit/ae136ea778f585cd96131dad935bca8a89026b30))

##### Modules

* Changement de libellés prescriptions et facteur de criticité (#397) ([8e508a](https://github.com/atos-df-rennes/prevarisc-migration/commit/8e508acae907b1df7ab95f6cc29a2daf2198f5b8))
* Port des modules personnalisés (#391) ([fd9237](https://github.com/atos-df-rennes/prevarisc-migration/commit/fd92371dcb12883f70bc290464ef8eace52920d9))


---

## [3.0.0-rc.1](https://github.com/atos-df-rennes/prevarisc-migration/compare/v2.10.6...v3.0.0-rc.1) (05-08-2026)

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
