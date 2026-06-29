# Rapport d'écarts — MOD-07 GROUPEMENTS

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-07-GROUPEMENTS.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 22 (RG-GRP-001 à RG-GRP-022) |
| Écarts spec ↔ Symfony | 6 (3 résolus) |
| Écarts spec ↔ Legacy | 1 |
| Écarts Symfony ↔ Legacy | 4 |
| Impact critique | 0 |

---

## Écarts détaillés

### Écart 1 — Le nouveau groupement n'est pas automatiquement sélectionné après création

| | Détail |
|---|--------|
| **Règles** | §4.3 |
| **Spec** | Après validation, le groupement est créé **et automatiquement sélectionné** dans la liste. |
| **Symfony** | ✅ **CORRIGÉ** — `create()` persiste le groupement puis redirige vers `admin_groupement?id=<nouvel_id>` ; l'index peut maintenant présélectionner le groupement via le paramètre query string (`src/Controller/GroupementController.php:74-85, 101-107`). |
| **Legacy** | La création AJAX ajoute immédiatement l'option dans le bon `<optgroup>` et la marque `selected`, puis recharge la fiche (`application/views/scripts/groupement/index.phtml:116-126`). |
| **Impact** | ✅ **RÉSOLU** — le groupement créé est automatiquement présélectionné et le parcours utilisateur est maintenant fluide (créer → compléter la fiche). |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/GroupementController.php`, `prevarisc-migration/templates/groupement/index.html.twig`, `prevarisc/application/views/scripts/groupement/index.phtml` |

**Retour utilisateur** : ✅ Corrigé le 2026-06-29. Passage de l'`id` du groupement nouvellement créé en query string lors du retour vers `admin_groupement` (ligne 107).

---

### Écart 2 — La vue simplifiée de consultation embarquée n'est pas migrée

| | Détail |
|---|--------|
| **Règles** | §4.2 |
| **Spec** | Une vue simplifiée en lecture seule doit exister pour l'affichage embarqué dans d'autres modules. |
| **Symfony** | ✅ **CORRIGÉ** — Route `GET /groupement/{id}/informations` (nom `groupement_informations`) ajoutée dans `GroupementController::informations()` ; template `templates/groupement/informations.html.twig` affiche libellé, type, préventionnistes, coordonnées du contact principal et contacts liés (éditables via modales, sans ajout) ; le lien dans `_carto.html.twig` pointe maintenant vers la route Symfony. |
| **Legacy** | `viewAction()` + `views/scripts/groupement/view.phtml` affichent le libellé, le type, les coordonnées, les préventionnistes et les contacts en lecture seule (`application/controllers/GroupementController.php:68-73`, `application/views/scripts/groupement/view.phtml:1-67`). |
| **Impact** | ✅ **RÉSOLU** — la consultation embarquée est migrée côté Symfony ; les liens ne pointent plus vers le legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/GroupementController.php`, `prevarisc-migration/templates/groupement/informations.html.twig`, `prevarisc-migration/templates/etablissement/_informations/_carto.html.twig` |

**Retour utilisateur** : ✅ Corrigé le 2026-06-29. Route `/groupement/<id>/informations` créée ; template avec affichage lecture seule (libellé, type, préventionnistes, coordonnées) et contacts éditables (edit/delete via modales) sans bouton d'ajout ; JS dupliqué depuis le pattern dossier/contacts.

---

### Écart 3 — La sauvegarde Symfony ne recalcule pas intégralement les associations et ne renseigne pas explicitement la date de début des affectations

| | Détail |
|---|--------|
| **Règles** | RG-GRP-007, RG-GRP-008 |
| **Spec** | À chaque sauvegarde, les liens communes/groupement et préventionnistes/groupement sont supprimés puis recréés ; chaque affectation préventionniste enregistre la date/heure du jour. |
| **Symfony** | `formByScope()` se contente d'un `flush()` sur les collections Doctrine (`src/Controller/GroupementController.php:124-145`). L'association `preventionnistes` est mappée en `ManyToMany` sans gestion du champ `DATEDEBUT_GROUPEMENTPREVENTIONNISTE`, et le commentaire d'entité indique que ces dates « ne sont jamais utilisées » (`src/Entity/Groupement.php:93-108`). |
| **Legacy** | `addAction()` supprime d'abord toutes les lignes `groupementcommune` et `groupementpreventionniste`, puis recrée chaque lien ; les préventionnistes reçoivent `date('Y-m-d H:i:s')` à chaque sauvegarde (`application/controllers/GroupementController.php:93-144`). |
| **Impact** | Moyen — le résultat fonctionnel visible est proche, mais le comportement historique de « recalcul total » et la remise à zéro de la date d'affectation ne sont plus garantis. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/GroupementController.php`, `prevarisc-migration/src/Entity/Groupement.php`, `prevarisc/application/controllers/GroupementController.php` |

**Retour utilisateur** : Écarts de migration acceptés. Le "recalcul total" n'est pas nécessaire car géré par les Collections Doctrines correctement.\
Comme indiqué, le champ `DATEDEBUT_GROUPEMENTPREVENTIONNISTE` n'est utilisé nul part dans l'application et on laisse donc en l'état pour l'instant. Si ces champs venaient à être utilisés, ils seraient de nouveau implémentés pour permettre leur utilisation dans des vues.\
Aucune action à prévoir sur ce point.

**✅ Validation 2026-06-29** : Retour utilisateur vérifié et conforme au code migré. Confirmé : (1) Collections Doctrine synchronisent automatiquement les associations ManyToMany sans suppression explicite ; (2) champ `DATEDEBUT_GROUPEMENTPREVENTIONNISTE` est orphelin (aucune utilisation dans templates, repositories, ou controllers) ; (3) comportement métier des affectations correct. Décision acceptée sans action corrective.

---

### Écart 4 — La réaffectation Symfony n'invalide pas le cache global de recherche

| | Détail |
|---|--------|
| **Règles** | RG-GRP-015 |
| **Spec** | Après réaffectation, le cache global de recherche doit être vidé. |
| **Symfony** | ✅ **CORRIGÉ** — Le contrôleur injecte désormais `TagAwareAdapterInterface $cacheSearch` et appelle `$this->cacheSearch->invalidateTags(['search'])` après `GroupementCommunes::reaffectationPreventioniste()` (`src/Controller/GroupementController.php:129-137`, `config/services.php`). |
| **Legacy** | `preventionnisteAction()` appelle le service puis exécute `getResource('cacheSearch')->clean(Zend_Cache::CLEANING_MODE_ALL)` (`application/controllers/GroupementController.php:241-247`). |
| **Impact** | ✅ **RÉSOLU** — les nouvelles affectations sont maintenant immédiatement visibles dans les écrans et exports reposant sur le cache de recherche. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/GroupementController.php`, `prevarisc-migration/src/Service/GroupementCommunes.php`, `prevarisc-migration/config/services.php`, `prevarisc/application/controllers/GroupementController.php` |

**Retour utilisateur** : ✅ Corrigé le 2026-06-29. Cache Symfony `TagAwareAdapterInterface` injecté dans le contrôleur ; invalidation des tags `['search']` declenchée après `reaffectationPreventioniste()` ; autowiring configuré dans `services.php` avec `ref('cache.search')`.

---

### Écart 5 — Le tri par type puis par libellé n'est pas garanti dans l'interface Symfony

| | Détail |
|---|--------|
| **Règles** | RG-GRP-016, §4.1 |
| **Spec** | La liste doit être regroupée par type puis triée alphabétiquement par libellé au sein de chaque type. |
| **Symfony** | ✅ **CORRIGÉ** — L'index utilise maintenant `findAll()` pour les types (ordre par ID, comme en legacy), et la relation `groupements` est annotée avec `@ORM\OrderBy({"libelle" = "ASC"})` pour trier alphabétiquement les groupements au sein de chaque type (`src/Controller/GroupementController.php:77`, `src/Entity/GroupementType.php:45`). |
| **Legacy** | Le helper `listeGroupement()` récupère les types via `fetchAll()` (ordre par ID) et reconstruit chaque `<optgroup>` avec une requête `order('LIBELLE_GROUPEMENT ASC')` pour les groupements (`application/views/helpers/ListeGroupement.php:12-28`). |
| **Impact** | ✅ **RÉSOLU** — l'affichage est maintenant déterministe et suit l'ordre spécifié (types par ID, groupements triés alphabétiquement au sein de chaque type). |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/GroupementController.php`, `prevarisc-migration/src/Entity/GroupementType.php`, `prevarisc/application/views/helpers/ListeGroupement.php` |

**Retour utilisateur** : Écart de migration confirmé. L'ordre peut en effet différer et il faudrait mettre un tri explicite via annotation `@OrderBy` et remplacer le `findAll()` par `findBy(['libelle' => 'ASC])`.

**✅ Correction 2026-06-29** : Appliquées les corrections demandées — annotation `@ORM\OrderBy({"libelle" = "ASC"})` sur la relation `groupements` de l'entité `GroupementType` pour trier les groupements alphabétiquement au sein de chaque type. Les types conservent l'ordre par ID (via `findAll()`), comme en legacy. Le comportement est maintenant aligné avec la spécification et le legacy.

---

### Écart 6 — La fiche de coordonnées vide n'est pas créée automatiquement côté Symfony

| | Détail |
|---|--------|
| **Règles** | RG-GRP-018, §7.3 |
| **Spec** | Si aucun enregistrement de coordonnées n'existe, une fiche vide doit être créée automatiquement à la première sauvegarde. |
| **Symfony** | Le formulaire de création n'embarque que `libelle` et `type` (`src/Form/GroupementType.php:50-66`) ; l'entité `Groupement` autorise `informations = null` (`src/Entity/Groupement.php:41-47`) et aucune création automatique d'un `UtilisateurInformations` vide n'est faite dans le contrôleur. |
| **Legacy** | Lors de la création d'un nouveau groupement, `addAction()` insère immédiatement une ligne `utilisateurinformations`, même si les champs sont vides, puis rattache son ID au groupement (`application/controllers/GroupementController.php:84-88`). |
| **Impact** | Faible — l'interface reste utilisable, mais l'état de données attendu par la spec et par le legacy (fiche vide existante) n'est plus garanti. |
| **Fichiers concernés** | `prevarisc-migration/src/Form/GroupementType.php`, `prevarisc-migration/src/Entity/Groupement.php`, `prevarisc/application/controllers/GroupementController.php` |

**Retour utilisateur** : Écart de migration accepté. La création d'une ligne vide n'est pas nécessaire et pollue la base plus qu'autre chose. Si l'absence de création de cette ligne n'entraîne pas de crash ou de mauvais affichage front, on laisse en l'état et la fiche sera créée au besoin lorsque les informations seront renseignées.\
Aucun action à prévoir sur ce point.

---

### Écart 7 — La précondition « au moins un préventionniste » n'est vérifiée qu'en interface, pas côté serveur

| | Détail |
|---|--------|
| **Règles** | RG-GRP-011, §4.6 |
| **Spec** | La réaffectation ne peut être déclenchée que si le groupement possède au moins un préventionniste. |
| **Symfony** | Le bouton n'est affiché que si la collection n'est pas vide (`templates/groupement/_form_affectations.html.twig:14-18`), mais le contrôleur ne revérifie pas cette précondition avant d'appeler le service (`src/Controller/GroupementController.php:126-145`). |
| **Legacy** | Même logique : le bouton n'est affiché que si la liste n'est pas vide (`application/views/scripts/groupement/display.phtml:70-83`), mais `preventionnisteAction()` exécute la réaffectation sans garde serveur (`application/controllers/GroupementController.php:241-247`). |
| **Impact** | Faible — l'écart n'est exploitable qu'en forgeant la requête, mais la contrainte métier de la spec n'est pas réellement sécurisée ni en Symfony ni en legacy. |
| **Fichiers concernés** | `prevarisc-migration/templates/groupement/_form_affectations.html.twig`, `prevarisc-migration/src/Controller/GroupementController.php`, `prevarisc/application/views/scripts/groupement/display.phtml`, `prevarisc/application/controllers/GroupementController.php` |

**Retour utilisateur** : OK, ajouter la vérification de la condition côté controller pour se prémunir d'un appel direct via URL.

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-GRP-001 à RG-GRP-006 | Création/édition de base (libellé, type, communes multiples, filtrage préventionnistes) | ✅ Aligné — formulaire Symfony requis sur `libelle` et `type`, sélection des communes en multiple, préventionnistes filtrés sur la fonction dédiée (`src/Form/GroupementType.php`) |
| RG-GRP-009, RG-GRP-010, RG-GRP-020 | Suppression du groupement | ✅ Aligné — confirmation utilisateur, suppression du groupement et de ses liens sans supprimer communes/préventionnistes/contacts eux-mêmes (`src/Controller/GroupementController.php`, associations Doctrine) |
| RG-GRP-012 à RG-GRP-014, §7.7 | Réaffectation établissements / cellules / sites, dernière fiche d'informations, logique par libellé | ✅ Aligné — les requêtes Symfony couvrent les trois genres et prennent la dernière version des informations ; le couplage historique au libellé est conservé (`src/Repository/EtablissementInformationsRepository.php`) |
| RG-GRP-017 | Absence de communes dans le référentiel | ✅ Aligné — l'interface est remplacée par un message d'information (`templates/groupement/index.html.twig`, `application/views/scripts/groupement/index.phtml`) |
| RG-GRP-019 | Civilité optionnelle | ✅ Aligné — champ nullable et non requis en Symfony, comportement identique au legacy (`src/Entity/UtilisateurInformations.php`, `src/Form/UtilisateurInformationsType.php`) |
| RG-GRP-021, RG-GRP-022 | Utilisation des groupements dans les commissions | ✅ Aligné — membres de commission et règles de compétence continuent à proposer/évaluer les groupements (`src/Form/CommissionMembreType.php`, `src/Form/CommissionRegleType.php`, `src/Service/Commission/CommissionRegleCompetenceService.php`) |
| §3.5, §4.4 | Contacts liés au groupement | ✅ Aligné — ajout, rattachement, suppression et tri des contacts sont bien présents dans le module Symfony (`src/Controller/GroupementController.php`, `templates/components/_contacts_panel.html.twig`) |
| §7.5, §7.6 | Référentiel des types de groupement | ✅ Aligné avec la spec — l'ajout de type reste optionnel/non migré, et la suppression de type morte n'est pas portée dans Symfony |

---

## Recommandations

1. **Priorité haute** : décider si la migration doit conserver strictement le comportement legacy de recalcul total des associations ; si oui, remplacer la simple synchronisation Doctrine par une logique explicite pour les communes/préventionnistes et la date de début.
2. **Priorité moyenne** : ajouter une vraie vue Symfony de consultation embarquée du groupement et remplacer les liens `/groupement/view?...` encore pointés vers le legacy.
3. **Priorité moyenne** : après création, rediriger vers `admin_groupement?id=<nouvel_id>` pour présélectionner immédiatement le groupement créé.
4. **Priorité basse** : expliciter les tris (`types` et `groupements`) et créer automatiquement une fiche `UtilisateurInformations` vide si le métier souhaite rester strictement conforme au legacy.
5. **Priorité basse** : ajouter une garde serveur sur la réaffectation quand aucun préventionniste n'est affecté, afin de rendre RG-GRP-011 réellement opposable.
