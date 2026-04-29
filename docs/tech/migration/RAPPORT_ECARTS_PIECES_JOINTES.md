━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RAPPORT D'ÉCARTS PAR RAPPORT AU LEGACY
Migration : Onglet Pièces jointes du dossier
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Fichiers legacy analysés :
  - prevarisc/application/controllers/PieceJointeController.php (9 actions, type=dossier uniquement porté)
  - prevarisc/application/controllers/DossierController.php (pieceJointeAction, getZipAllPjAction)
  - prevarisc/application/views/scripts/piece-jointe/index.phtml
  - prevarisc/application/views/scripts/piece-jointe/display.phtml
  - prevarisc/application/views/scripts/piece-jointe/form.phtml
  - prevarisc/application/views/scripts/piece-jointe/export.phtml
  - prevarisc/public/js/dossier/pieceJointe.js

Fichiers migrés :
  - prevarisc-migration/src/Controller/DossierPiecesJointesController.php
  - prevarisc-migration/src/Repository/DossierPieceJointeRepository.php (ajout findAllByDossier)
  - prevarisc-migration/templates/dossier/pieces_jointes/index.html.twig
  - prevarisc-migration/templates/dossier/pieces_jointes/_piece_jointe_row.html.twig
  - prevarisc-migration/templates/dossier/pieces_jointes/form.html.twig
  - prevarisc-migration/public/js/dossier/pieces-jointes.js
  - prevarisc-migration/templates/layouts/dossier.html.twig (lien onglet PJ)

Niveau de confiance global : ⚠️

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ PORT DIRECT — aucune vérification nécessaire sur ces points :

- Routing : `/piece-jointe/index`, `/piece-jointe/add`, `/piece-jointe/get`, etc.
  → Routes `@Route` Symfony nommées `dossier_pieces_jointes_*` (mécanique)

- Injection de dépendances : `new Model_DbTable_PieceJointe()`, `new Service_Notification()`, etc.
  → Injection constructeur via `DataStore`, `DossierRepository`, `NotificationService`, etc.

- Variables de vue : `$this->view->assign('listePj', ...)`, `$this->view->assign('isPlatau', ...)`
  → `render(..., ['listePj' => ..., 'isPlatau' => ...])`

- Rendu du fichier (download) : `header(...)` + `readfile()` + `exit`
  → `BinaryFileResponse` Symfony (comportement identique)

- Détection Platau : `$dossier['ID_PLATAU'] !== null`
  → `$dossier->getConsultationPlatau() !== null` (même logique, API Doctrine)

- Chemin fichier Platau : `REAL_DATA_PATH.DS.'uploads'.DS.'pieces-jointes'.DS.$id.$ext`
  → `$this->realDataPath.'/uploads/pieces-jointes/'.$pj->getId().$pj->getExtension()`

- Chemin fichier standard : `$this->store->getFilePath($pj, 'dossier', $id)`
  → `$this->dataStore->getFilePath($pj, 'dossier', (int)$dossier->getId(), $createDir)`

- Verrou dossier : `$this->verrou != 1` dans la vue
  → `$dossier->hasVerrou()` (même sémantique)

- Blocage suppression Platau : `!in_array($pj['NOM_STATUT'], ['to_be_exported', 'exported', 'awaiting_status'])`
  → même liste de statuts dans `delete()` et la vue (RG-PJ-010)

- Flash messages : `$this->_helper->flashMessenger([...])` → `$this->addFlash('success'/'error', '...')`

- Bootstrap 2 → 3 (mécanique) :
  - `icon-trash` → `glyphicon glyphicon-trash`
  - `icon-calendar icon-white` → `glyphicon glyphicon-calendar`
  - `icon-tag icon-white` → `glyphicon glyphicon-tag`
  - `icon-info-sign` → `glyphicon glyphicon-info-sign`
  - `label-important` → `label-danger`
  - `btn` (sans modificateur) → `btn btn-default`
  - `span3`/`span9`/`span10`/`span2` (fluid grid) → `col-md-3`/`col-md-9`/etc.
  - `ui-helper-hidden` + jQuery UI dialog → BS3 modal (`modal fade`, `modal-dialog`, `modal-content`)
  - `dl-horizontal` + `dl`/`dt`/`dd` → `form-horizontal` avec `form-group` + `control-label`
  - Dropzone jQuery UI → Dropzone.js natif (même librairie mais initiation BS3)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⚠️ ADAPTATIONS — vérifier ces points précis :

1. [DossierPiecesJointesController.php:~208] Nom de fichier par défaut (fallback sans label)
   Legacy : `substr($_FILES['fichier']['name'], 0, -4)` — retire les 4 derniers caractères (`.ext` de 3 chars)
   Symfony : `pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)` — retire toute l'extension
   Différence : pour les extensions courtes (.js, .ts) ou longues (.html, .jpeg), le résultat diffère légèrement.
   → Tester : uploader un fichier `mon-document.html` sans saisir de libellé → vérifier que le nom affiché est `mon-document` (Symfony) vs `mon-document.htm` (legacy, bug corrigé)

2. [DossierPiecesJointesController.php:check()] CSRF ajouté sur `check()`
   Legacy : `checkAction()` recevait les données POST sans validation CSRF (pas de token dans le formulaire legacy)
   Symfony : CSRF validé via token `dossier_pj_add` — le JS envoie le même token que pour `add()`
   → Tester : appeler l'endpoint `POST .../verifier` sans token → s'assurer que `{'exists': false}` est retourné

3. [DossierPiecesJointesController.php:~510] CSRF + vérification propriété dans `retryExportPlatau()`
   Legacy : `retryExportPlatauAction()` acceptait un `idPj` POST brut sans vérifier l'appartenance au dossier
   Symfony : CSRF obligatoire + vérification que la PJ appartient au dossier (IDOR corrigé)
   → Tester : renvoyer une PJ Platau en erreur → vérifier que le statut repasse à `to_be_exported` puis que le bouton Renvoyer disparaît

4. [NotificationService] IS_NEW — persistance DB vs session Zend
   Legacy : `Service_Notification::setLastPageVisitDate()` stocke en session PHP (volatile, perdu à déconnexion)
   Symfony : `NotificationService` persiste la date dans `Utilisateur::derniereVisitePiecesDossier` (DB)
   Différence fonctionnelle : après déconnexion/reconnexion, toutes les PJ seront marquées IS_NEW dans le legacy ;
   en Symfony, la dernière visite est mémorisée même après reconnexion.
   → Tester : visiter l'onglet PJ, se déconnecter, se reconnecter, revenir → vérifier que les PJ n'ont plus le badge "Nouveau !"

5. [DossierPiecesJointesController.php:~480] ZipArchive::open() — erreur maintenant gérée
   Legacy : retour non vérifié (silencieux en cas d'échec disque)
   Symfony : si `open()` ne retourne pas `true`, flash erreur + redirection
   → Tester : simuler une erreur de permissions sur le répertoire de sortie (chemin non accessible)

6. [DossierPiecesJointesController.php:~208] Rejet des fichiers sans extension
   Legacy : `strrchr($name, '.')` retourne `false` si aucun `.` → extension vide, stockée comme `""` en BDD
   Symfony : fichiers sans extension explicitement rejetés (400 avec message d'erreur)
   → Tester : uploader un fichier sans extension → s'assurer que le rejet est bien signalé dans l'interface Dropzone

7. [DossierPiecesJointesController.php:displayPjPlatau()] Fragment Platau — réponse vide vs null
   Legacy : retourne `null` (PHP `return null;`) si aucune PJ à exporter → rien n'est affiché côté client
   Symfony : retourne une `Response` vide avec corps `''` → comportement équivalent pour le JS qui reçoit la réponse
   → Tester : ouvrir le panneau Platau sur un dossier sans PJ `not_exported` → vérifier que le fragment est vide/absent

8. [index.html.twig:~26] Bouton "Télécharger les pièces jointes" — condition
   Legacy : conditionné par `$displayDownloadButton` issu du paramètre HTTP `displayDownloadButton=true/false`
   Symfony : conditionné par `displayDownloadButton` calculé côté contrôleur = `!empty($listePj)` uniquement
   → Tester : ouvrir l'onglet PJ d'un dossier avec PJ → le bouton ZIP doit être présent

9. [_piece_jointe_row.html.twig] Métadonnées Platau (TYPE, SOUS_TYPE, DATE_DEPOT, NATURE)
   Legacy : `display.phtml` affichait TYPE, SOUS_TYPE, DATE_DEPOT, NATURE dans la colonne Description
   Symfony : les entités `PieceJointe` et `DossierPieceJointe` n'exposent pas ces champs Platau (non mappés en Doctrine)
   → Ces champs sont vides dans la vue Symfony pour les PJ importées de Plat'AU
   → Tester : vérifier que les PJ importées de Plat'AU affichent correctement leur description (ou vide si non mappé)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔴 LOGIQUE COMPLEXE — vérification approfondie recommandée :

1. [DossierPieceJointeRepository.php] Chargement des PJ — requête unifiée vs N+1
   Legacy : `affichagePieceJointe()` exécutait une jointure SQL multi-tables en une seule requête (statut, type, sous-type, nature, etc.)
   Symfony : `findAllByDossier()` utilise un QueryBuilder Doctrine avec `addSelect('pj', 'statut')` — les champs Platau (TYPE, SOUS_TYPE, etc.) ne sont pas chargés (entités non mappées)
   → Tester : ouvrir l'onglet PJ d'un dossier avec de nombreuses PJ Platau (>20) → vérifier l'absence de requêtes N+1 en mode dev (Symfony profiler)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

FONCTIONNALITÉS OMISES (écarts délibérés)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ÉCART-PJ-01 : Sélection PJ pour passage en commission (`pjpassagecommission`)
  Legacy : `PieceJointeController::pjPassageCommissionAction()` — appelait `/dossier/fonction`
           (handler AJAX inexistant dans DossierController)
  Décision : code mort, non porté
  Impact : zéro (fonctionnalité jamais opérationnelle en production)

ÉCART-PJ-02 : Support type=établissement et type=dateCommission dans PieceJointeController
  Legacy : `PieceJointeController` gérait 3 types (dossier, établissement, dateCommission)
  Symfony : `DossierPiecesJointesController` gère uniquement le type dossier
  Impact : aucun (EtablissementPiecesJointesController existant gère le type établissement)

ÉCART-PJ-03 : Paramètre `displayDownloadButton` via requête HTTP
  Legacy : `$displayDownloadButton = filter_var($this->getRequest()->getParam('displayDownloadButton', true), ...)`
  Symfony : calculé depuis `!empty($listePj)` — suppression du paramètre HTTP
  Impact : aucun (le paramètre n'était jamais passé à false dans les vues consommant ce partial)

ÉCART-PJ-04 : `droit_ecriture` dans checkAction
  Legacy : `checkAction()` passait `'droit_ecriture' => true` au partial mais le template ne l'utilisait jamais
  Symfony : variable non transmise (dead code)
  Impact : aucun

ÉCART-PJ-05 : Callback JS iframe post-upload
  Legacy : `addAction()` écrivait `<script>window.top.window.callback(...)</script>` dans une iframe cachée
  Symfony : réponse JSON `{'success': true, 'id': X}` consommée par Dropzone directement
  Impact : interface plus moderne et robuste, pas de régression fonctionnelle

ÉCART-PJ-06 : Validation de l'extension vide
  Legacy : un fichier sans extension aurait extension `""` stockée en BDD
  Symfony : rejet explicite (400 + message d'erreur Dropzone)
  Impact : amélioration de la robustesse, cas d'usage rare

ÉCART-PJ-07 : Liaison PJ ↔ établissement via EtablissementPieceJointe
  Legacy : `addAction()` (type=dossier) créait des entrées `EtablissementPj` pour chaque établissement
           coché dans la section "Entités liées" du formulaire. Cependant, le fichier physique était
           stocké sous le chemin `dossier/` (via `getFilePath($pj, 'dossier', $idDossier)`), ce qui
           rendait la PJ invisible depuis l'onglet PJ de l'établissement (chemin `etablissement/` attendu).
           La miniature n'était générée que pour les PJ ajoutées directement sur un établissement.
           Cette fonctionnalité n'a donc jamais fonctionné en production.
  Symfony : section "Entités liées" retirée du formulaire, logique EtablissementPieceJointe supprimée.
  Décision : code mort non fonctionnel, non porté.
  Impact : aucun (la fonctionnalité n'était pas opérationnelle)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
