# Rapport d'écarts — MOD-17 DOCUMENTS MODÈLES

> **Date** : 2026-07-06  
> **Spec source** : `docs/specs/MOD-17-DOCUMENTS-MODELES.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 16 |
| Écarts confirmés | 4 |
| Points à vérifier | 5 |
| Niveau de risque | Moyen |
| Niveau de confiance | Bon |

**Constats clés** : Le périmètre principal est bien migré (administration des modèles, upload ODT, écrasement silencieux par nom, espace transversal, génération depuis dossier). Les validations métier majeures sont présentes. Les écarts confirmés concernent surtout l'écran d'administration (absence de repli/dépli des commissions, badge `0` sur commission vide, gestion non bloquante d'un répertoire manquant). Un point technique important subsiste : l'administration liste tous les fichiers du répertoire, alors que la génération ne prend en compte que les `.odt`.

---

## Écarts détaillés

### Écart 1 — Commission sans modèle (affichage du badge `0`) ✅ RÉSOLU

| | Détail |
|---|--------|
| **Règles** | §5.1.4 |
| **Spec** | Une commission sans modèle doit être affichée sans lien et sans compteur |
| **Symfony** | Le template Twig affiche un badge avec `0` dès que `documents` n'est pas `null` |
| **Legacy** | Affichait uniquement le libellé de la commission, sans compteur |
| **Impact** | Écart UX visible par rapport à la spec et au comportement historique |
| **Fichiers modifiés** | `templates/documents/index.html.twig`, `prevarisc/application/views/scripts/gestion-des-documents/index.phtml` |

**Retour utilisateur** : Remplacer l'affichage titre + badge + tableau par un panel (titre + badge en en-tête, contenu dans le body), en s'appuyant sur les panels existants du code Symfony. Le nom du document ne permet plus de télécharger directement : il est affiché en texte brut, avec un bouton d'action "Télécharger" ajouté à droite. Le texte "Suppression du document" devient un bouton d'action "Supprimer", aligné à droite avec le bouton "Télécharger". Si aucun document n'est disponible, afficher le texte "Aucun document disponible" en conservant le panel avec titre + badge `0`.

**Résolution (2026-07-06)** : `templates/documents/index.html.twig` refondu pour utiliser un `panel panel-default` (comme dans `templates/dossier/liaisons/index.html.twig`) avec `panel-heading` (titre + badge, y compris badge `0`) et `panel-body`. Chaque document est affiché en texte brut avec deux boutons d'action alignés à droite (`pull-right`) : "Télécharger" (lien vers `document.filepath`) et "Supprimer" (déclenche la modale de suppression existante). Quand la liste est vide, le texte "Aucun document disponible" est affiché dans le panel-body, panel conservé avec badge `0`. Twig-CS-Fixer et PHP-CS-Fixer OK.

**Complément (2026-07-06)** : Ajout d'un chevron de dépliage/repliage sur chaque panel, en réutilisant le pattern Bootstrap collapse déjà présent dans le projet (`templates/components/_formulaire_personnalise.html.twig`, `templates/commissions/_membre_panel_macro.html.twig`) : lien `panel-title__link` avec `data-toggle="collapse"`/`data-target` et `span.caret-toggle.glyphicon-chevron-down`, dont la rotation est gérée par la classe CSS existante `.panel-title .collapsed .caret-toggle` (`public/css/utilities.css`), sans JavaScript custom (Bootstrap gère nativement le collapse). Ajout également d'icônes glyphicon sur les boutons d'action, en s'appuyant sur les patterns déjà utilisés ailleurs dans le projet : `glyphicon-download-alt` pour "Télécharger" (cf. `templates/dossier/pieces_jointes/index.html.twig`) et `glyphicon-trash` pour "Supprimer" (cf. `templates/commissions/_membre_panel_macro.html.twig`). Lint et tests (642 tests) OK.

---

### Écart 2 — Absence de repli/dépli des commissions ✅ À migrer

| | Détail |
|---|--------|
| **Règles** | §5.1.5 |
| **Spec** | Le clic sur le nom d'une commission doit dérouler / replier sa liste de fichiers |
| **Symfony** | Le nom de commission est un simple `<h5>` et le tableau est affiché directement |
| **Legacy** | Utilisait un lien `.commission` et un `toggle()` sur le tableau associé |
| **Impact** | Régression UX : la navigation accordéon du legacy n'est plus présente |
| **Fichiers modifiés** | `templates/documents/index.html.twig`, `prevarisc/application/views/scripts/gestion-des-documents/index.phtml` |

---

### Écart 3 — Répertoire de commission inexistant (gestion bloquante)

| | Détail |
|---|--------|
| **Règles** | §8.5 |
| **Spec** | Le répertoire manquant doit provoquer une erreur bloquante |
| **Symfony** | Le builder crée un objet avec `documents = null`, et la vue affiche une alerte non bloquante |
| **Legacy** | Faisait un `opendir(...) \|\| exit(...)`, donc arrêt bloquant |
| **Impact** | Divergence fonctionnelle : Symfony laisse la page vivre là où le legacy bloquait |
| **Fichiers modifiés** | `src/Service/Commission/CommissionDocumentBuilder.php`, `templates/documents/index.html.twig`, `prevarisc/application/controllers/GestionDesDocumentsController.php` |

---

### Écart 4 — Liste d'administration non filtrée aux ODT

| | Détail |
|---|--------|
| **Règles** | RG-DOC-001 |
| **Spec** | L'administration doit gérer des modèles ODT |
| **Symfony** | `Documents::findForCommission()` liste tous les fichiers du répertoire sans filtre `*.odt` |
| **Legacy** | La génération restait centrée sur les ODT ; l'administration ne distinguait pas mieux |
| **Impact** | Incohérence interne : un fichier non-ODT peut apparaître dans l'admin alors qu'il n'est jamais proposé dans la génération |
| **Fichiers modifiés** | `src/Service/Documents.php`, `src/Controller/Dossier/DossierRapportController.php` |

---

## Points à vérifier

### Écart 5 — Barre de progression à l'upload

| | Détail |
|---|--------|
| **Règles** | §5.2 |
| **Spec** | Une barre de progression doit s'afficher pendant l'envoi |
| **Symfony** | Aucun composant de progression visible dans les templates Symfony consultés |
| **Legacy** | Ouvrait une petite boîte de chargement avec une barre GIF |
| **Impact** | Écart UX probable si l'on applique la spec à la lettre |
| **Fichiers modifiés** | `templates/documents/_modal_ajout.html.twig`, `templates/documents/ajout.html.twig`, `prevarisc/application/views/scripts/gestion-des-documents/index.phtml` |

---

### Écart 6 — Décrémentation du compteur sans reload

| | Détail |
|---|--------|
| **Règles** | §5.4 |
| **Spec** | Après suppression, la ligne disparaît et le compteur est décrémenté |
| **Symfony** | Recharge toute la page après succès/erreur de suppression |
| **Legacy** | Retirait la ligne et décrémentait le compteur en place |
| **Impact** | Résultat final identique, mais expérience utilisateur moins fluide |
| **Fichiers modifiés** | `templates/documents/index.html.twig`, `prevarisc/application/views/scripts/gestion-des-documents/index.phtml` |

---

### Écart 7 — Variables `gn2` / `gn3` non portées correctement

| | Détail |
|---|--------|
| **Règles** | §3.1 |
| **Spec** | Les champs de fusion doivent refléter correctement les données métier |
| **Symfony** | Remappe `gn2/gn3` à partir de `ID_GENRE`, avec commentaire précisant que la colonne legacy n'existe plus dans la base migrée |
| **Legacy** | Utilisait la donnée métier d'origine |
| **Impact** | Risque de divergence sur certains modèles ODT selon l'interprétation métier attendue |
| **Fichiers modifiés** | `src/Service/DossierRapportOdtService.php` |

---

### Écart 8 — Effectifs / dégagements non injectés

| | Détail |
|---|--------|
| **Règles** | §3.1 |
| **Spec** | Les champs doivent être injectés dans les modèles si disponibles |
| **Symfony** | Plusieurs champs (`descriptionEffectifDossier`, `descriptionDegagementDossier`, etc.) sont explicitement ajoutés à `null` |
| **Legacy** | Disposait vraisemblablement d'un calcul ou d'une alimentation dédiée |
| **Impact** | Les modèles utilisant ces variables produiront des champs vides, sans erreur, mais potentiellement avec perte de contenu |
| **Fichiers modifiés** | `src/Service/DossierRapportOdtService.php` |

---

### Écart 9 — Libellé de l'espace transversal (faute orthographique) ✅ RÉSOLU

| | Détail |
|---|--------|
| **Règles** | §5.1.1 |
| **Spec** | Le libellé attendu est « Documents visibles après verrouillage » |
| **Symfony** | Le titre Twig contient une faute : `Visbles après verrouillage` |
| **Legacy** | Affichait un libellé proche, également imparfait, mais l'intention fonctionnelle restait claire |
| **Impact** | Écart cosmétique mineur |
| **Fichiers modifiés** | `templates/documents/index.html.twig` |

**Résolution (2026-07-06)** : Faute corrigée dans `templates/documents/index.html.twig` : `Visbles après verrouillage` → `Visibles après verrouillage`. Corrigé dans le cadre de la refonte du panel de l'écart 1 (même fichier).

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-DOC-001 | Upload limité au format ODT | ✅ Aligné (contrainte MIME sur `DocumentType`) |
| RG-DOC-002 | Répertoire commission créé automatiquement | ✅ Aligné (service dédié appelé à la création) |
| RG-DOC-003 | Espace transversal (commission `0`) | ✅ Aligné (même logique que le legacy) |
| RG-DOC-004 | Dossier verrouillé : documents transversaux seuls | ✅ Aligné (commissions masquées quand verrouillé) |
| RG-DOC-005 | Commission du dossier présélectionnée | ✅ Aligné (option par défaut dans le rapport) |
| RG-DOC-006 | Écrasement silencieux par même nom | ✅ Aligné (déplacement direct du fichier uploadé) |
| RG-DOC-007 | Suppression confirmée avant exécution | ✅ Aligné (modale de confirmation conforme) |
| RG-DOC-008 | Nom de fichier conservé | ✅ Aligné (`basename()` / nom original conservé) |
| RG-DOC-009 | Tri alphabétique des fichiers | ✅ Aligné (`sortByName()`) |
| RG-DOC-010 | Accès depuis l'administration | ✅ Aligné (contrôleur d'administration dédié) |
| RG-DOC-011 | Génération bloquée sans modèle ou établissement | ✅ Aligné (contrôle JS + message utilisateur) |
| RG-DOC-012 | Un document généré par établissement | ✅ Aligné (boucle sur les établissements sélectionnés) |
| RG-DOC-013 | Commissions regroupées par type | ✅ Aligné (groupement dans l'admin et les listes) |
| RG-DOC-014 | Compte-rendu unique par commission | ✅ Aligné (remplacement explicite du fichier précédent) |
| RG-DOC-015 | Séparation courriers/modèles rapports | ✅ Aligné (contrôleurs / vues séparés) |
| RG-DOC-016 | Balises non résolues remplacées par vide | ✅ Aligné (`OdtWriter` injecte `''`) |

---

## Recommandations

**Écart 1 — Commission sans modèle (affichage du badge `0`)** ✅ RÉSOLU (2026-07-06)  
Retour utilisateur : conserver le badge `0` (plutôt que le supprimer) et refondre l'affichage en panel (titre + badge en en-tête, contenu en body), avec nom de document en texte brut, boutons d'action "Télécharger"/"Supprimer" alignés à droite, et texte "Aucun document disponible" si la liste est vide. Implémenté dans `templates/documents/index.html.twig`.

**Écart 2 — Absence de repli/dépli des commissions**  
Rétablir le comportement de repli/dépli des commissions si l'objectif est l'iso-fonctionnalité stricte de l'écran d'administration.

**Écart 3 — Répertoire de commission inexistant (gestion bloquante)**  
Décider du niveau de sévérité attendu pour un répertoire manquant : si la spec prime, l'erreur doit redevenir bloquante ; si l'amélioration UX est conservée, il faut documenter l'écart.

**Écart 4 — Liste d'administration non filtrée aux ODT**  
Filtrer les fichiers `.odt` dans `Documents::findForCommission()` pour aligner l'administration avec la génération des rapports.

**Écart 9 — Libellé de l'espace transversal (faute orthographique)** ✅ RÉSOLU (2026-07-06)  
Faute corrigée : `Visbles après verrouillage` → `Visibles après verrouillage`.

**Écarts 5–8 (points à vérifier restants)**  
Valider métier les variables ODT non portées (`gn2/gn3`, effectifs, dégagements) et améliorer l'UX du composant de suppression (sans reload) et de progress bar upload avant de conclure à une iso-fonctionnalité complète.
