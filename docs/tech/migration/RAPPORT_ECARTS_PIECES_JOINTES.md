# Rapport d'écarts — Onglet Pièces jointes du dossier

**Niveau de confiance global :** ⚠️

## Fichiers analysés

**Legacy :**
- `prevarisc/application/controllers/PieceJointeController.php` (9 actions, type=dossier uniquement porté)
- `prevarisc/application/controllers/DossierController.php` (pieceJointeAction, getZipAllPjAction)
- `prevarisc/application/views/scripts/piece-jointe/index.phtml`
- `prevarisc/application/views/scripts/piece-jointe/display.phtml`
- `prevarisc/application/views/scripts/piece-jointe/form.phtml`
- `prevarisc/application/views/scripts/piece-jointe/export.phtml`
- `prevarisc/public/js/dossier/pieceJointe.js`

**Migrés :**
- `prevarisc-migration/src/Controller/DossierPiecesJointesController.php`
- `prevarisc-migration/src/Repository/DossierPieceJointeRepository.php` (ajout findAllByDossier)
- `prevarisc-migration/templates/dossier/pieces_jointes/index.html.twig`
- `prevarisc-migration/templates/dossier/pieces_jointes/_piece_jointe_row.html.twig`
- `prevarisc-migration/templates/dossier/pieces_jointes/form.html.twig`
- `prevarisc-migration/public/js/dossier/pieces-jointes.js`
- `prevarisc-migration/templates/layouts/dossier.html.twig` (lien onglet PJ)

---

## ✅ Port direct — aucune vérification nécessaire

- **Routing** : `/piece-jointe/index`, `/piece-jointe/add`, etc. → routes `@Route` Symfony nommées `dossier_pieces_jointes_*`
- **Injection de dépendances** : `new Model_DbTable_PieceJointe()` → injection constructeur via `DataStore`, `NotificationService`, etc.
- **Variables de vue** : `$this->view->assign('listePj', ...)` → `render(..., ['listePj' => ...])`
- **Download** : `header(...)` + `readfile()` + `exit` → `BinaryFileResponse` (comportement identique)
- **Détection Platau** : `$dossier['ID_PLATAU'] !== null` → `$dossier->getConsultationPlatau() !== null`
- **Chemin fichier Platau** : `REAL_DATA_PATH.DS.'uploads'.DS.'pieces-jointes'.DS.$id.$ext` → `$this->realDataPath.'/uploads/pieces-jointes/'.$pj->getId().$pj->getExtension()`
- **Chemin fichier standard** : `$this->store->getFilePath($pj, 'dossier', $id)` → `$this->dataStore->getFilePath($pj, 'dossier', (int)$dossier->getId(), $createDir)`
- **Verrou dossier** : `$this->verrou != 1` dans la vue → `$dossier->hasVerrou()`
- **Blocage suppression Platau** : `!in_array($pj['NOM_STATUT'], ['to_be_exported', 'exported', 'awaiting_status'])` → même liste dans `delete()` et la vue (RG-PJ-010)
- **Flash messages** : `$this->_helper->flashMessenger([...])` → `$this->addFlash('success'/'error', '...')`
- **Bootstrap 2 → 3** (mécanique) : `icon-trash` → `glyphicon glyphicon-trash`, `label-important` → `label-danger`, `btn` → `btn btn-default`, grid fluid → `col-md-*`, jQuery UI dialog → modal BS3, Dropzone jQuery UI → Dropzone.js natif

---

## ⚠️ Adaptations — points à vérifier

### 1. Nom de fichier par défaut (fallback sans libellé)

- **Legacy** : `substr($_FILES['fichier']['name'], 0, -4)` — retire 4 caractères (extension de 3 chars)
- **Symfony** : `pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)` — retire toute l'extension
- **Différence** : pour les extensions courtes (`.js`) ou longues (`.html`, `.jpeg`), le résultat diffère légèrement
- **Test** : uploader `mon-document.html` sans libellé → le nom attendu est `mon-document` (Symfony) vs `mon-document.htm` (legacy, bug corrigé)

### 2. CSRF ajouté sur `check()`

- **Legacy** : `checkAction()` acceptait les données POST sans token CSRF
- **Symfony** : CSRF validé via token `dossier_pj_add` (même token que `add()`)
- **Test** : appeler `POST .../verifier` sans token → vérifier que `{"exists": false}` est retourné

### 3. CSRF + vérification propriété dans `retryExportPlatau()`

- **Legacy** : `retryExportPlatauAction()` acceptait un `idPj` POST brut sans vérifier l'appartenance au dossier
- **Symfony** : CSRF obligatoire + vérification que la PJ appartient au dossier (IDOR corrigé)
- **Test** : renvoyer une PJ Platau en erreur → vérifier que le statut repasse à `to_be_exported`

### 4. IS_NEW — persistance DB vs session Zend

- **Legacy** : `Service_Notification::setLastPageVisitDate()` stocke en session PHP (volatile, perdu à déconnexion)
- **Symfony** : `NotificationService` persiste la date dans `Utilisateur::derniereVisitePiecesDossier` (DB)
- **Différence** : après déconnexion/reconnexion, toutes les PJ seront marquées IS_NEW en legacy ; en Symfony, la date de visite est mémorisée
- **Test** : visiter l'onglet PJ, se déconnecter, se reconnecter → les PJ ne doivent plus avoir le badge "Nouveau !"

### 5. `ZipArchive::open()` — erreur maintenant gérée

- **Legacy** : retour non vérifié (silencieux en cas d'échec disque)
- **Symfony** : si `open()` ne retourne pas `true`, flash erreur + redirection
- **Test** : simuler une erreur de permissions sur le répertoire de sortie

### 6. Rejet des fichiers sans extension

- **Legacy** : extension vide `""` stockée en BDD silencieusement
- **Symfony** : rejet explicite (400 + message d'erreur Dropzone)
- **Test** : uploader un fichier sans extension → le rejet doit être affiché dans Dropzone

### 7. Fragment Platau — réponse vide vs null

- **Legacy** : retourne `null` si aucune PJ à exporter → rien affiché côté client
- **Symfony** : retourne une `Response` vide (`''`) → comportement équivalent
- **Test** : ouvrir le panneau Platau sur un dossier sans PJ `not_exported` → fragment vide/absent

### 8. Bouton "Télécharger les pièces jointes" — condition

- **Legacy** : conditionné par le paramètre HTTP `displayDownloadButton=true/false`
- **Symfony** : calculé depuis `!empty($listePj)` côté contrôleur
- **Test** : ouvrir l'onglet PJ d'un dossier avec PJ → le bouton ZIP doit être présent

### 9. Métadonnées Platau (TYPE, SOUS_TYPE, DATE_DEPOT, NATURE)

- **Legacy** : `display.phtml` affichait TYPE, SOUS_TYPE, DATE_DEPOT, NATURE dans la colonne Description
- **Symfony** : ces champs ne sont pas mappés en Doctrine sur `PieceJointe` / `DossierPieceJointe` → affichage vide
- **Test** : vérifier que les PJ importées de Plat'AU affichent correctement leur description (ou vide si non mappé)

---

## 🔴 Logique complexe — vérification approfondie recommandée

### Chargement des PJ — requête unifiée vs N+1

- **Legacy** : `affichagePieceJointe()` exécutait une jointure SQL multi-tables en une seule requête (statut, type, sous-type, nature, etc.)
- **Symfony** : `findAllByDossier()` utilise un QueryBuilder Doctrine avec `addSelect('pj', 'statut')` — les champs Platau non mappés ne sont pas chargés
- **Test** : ouvrir l'onglet PJ d'un dossier avec de nombreuses PJ Platau (>20) → vérifier l'absence de requêtes N+1 (Symfony profiler)

---

## Écarts délibérés

### ÉCART-PJ-01 — Sélection PJ pour passage en commission (`pjpassagecommission`)

- **Legacy** : `PieceJointeController::pjPassageCommissionAction()` appelait `/dossier/fonction` (handler AJAX inexistant dans DossierController)
- **Décision** : code mort, non porté
- **Impact** : zéro (fonctionnalité jamais opérationnelle en production)

### ÉCART-PJ-02 — Support type=établissement et type=dateCommission

- **Legacy** : `PieceJointeController` gérait 3 types (dossier, établissement, dateCommission)
- **Symfony** : `DossierPiecesJointesController` gère uniquement le type dossier
- **Impact** : aucun (`EtablissementPiecesJointesController` existant gère le type établissement)

### ÉCART-PJ-03 — Paramètre `displayDownloadButton` via requête HTTP

- **Legacy** : `filter_var($this->getRequest()->getParam('displayDownloadButton', true), ...)`
- **Symfony** : calculé depuis `!empty($listePj)` — suppression du paramètre HTTP
- **Impact** : aucun (le paramètre n'était jamais passé à `false` dans les vues)

### ÉCART-PJ-04 — `droit_ecriture` dans `checkAction`

- **Legacy** : `checkAction()` passait `'droit_ecriture' => true` au partial mais le template ne l'utilisait jamais
- **Symfony** : variable non transmise (dead code)
- **Impact** : aucun

### ÉCART-PJ-05 — Callback JS iframe post-upload

- **Legacy** : `addAction()` écrivait `<script>window.top.window.callback(...)</script>` dans une iframe cachée
- **Symfony** : réponse JSON `{"success": true, "id": X}` consommée par Dropzone directement
- **Impact** : interface plus moderne et robuste, pas de régression fonctionnelle

### ÉCART-PJ-06 — Validation de l'extension vide

- **Legacy** : fichier sans extension → extension `""` stockée en BDD silencieusement
- **Symfony** : rejet explicite (400 + message d'erreur Dropzone)
- **Impact** : amélioration de la robustesse, cas d'usage rare

### ÉCART-PJ-07 — Liaison PJ ↔ établissement via `EtablissementPieceJointe`

- **Legacy** : `addAction()` (type=dossier) créait des entrées `EtablissementPj` pour chaque établissement coché dans "Entités liées". Cependant, le fichier physique était stocké sous le chemin `dossier/`, le rendant invisible depuis l'onglet PJ de l'établissement (chemin `etablissement/` attendu). La miniature n'était générée que pour les PJ ajoutées directement sur un établissement. Cette fonctionnalité n'a jamais fonctionné en production.
- **Symfony** : section "Entités liées" retirée du formulaire, logique `EtablissementPieceJointe` supprimée
- **Décision** : code mort non fonctionnel, non porté
- **Impact** : aucun
