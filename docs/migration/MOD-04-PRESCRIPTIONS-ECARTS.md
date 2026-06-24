# Rapport d'écarts — MOD-04 PRESCRIPTIONS

> **Date** : 2026-05-22 (mis à jour le 2026-06-24)
> **Spec source** : `docs/specs/MOD-04-PRESCRIPTIONS.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 25 (RG-PRE-001 à RG-PRE-025) |
| Écarts spec ↔ Symfony | 6 |
| Écarts spec ↔ Legacy | 0 |
| Écarts Symfony ↔ Legacy | 6 |
| Écarts corrigés | 3 (Écarts 1, 3, 5) |
| Écarts documentés / non-régressions | 3 (Écarts 2, 4, 6) |
| Impact critique | 0 (résolu) |

---

## Écarts détaillés

### Écart 1 — Validation incomplète des prescriptions types côté Symfony ✅ Corrigé

| | Détail |
|---|--------|
| **Règles** | RG-PRE-002 |
| **Spec** | Une prescription type doit contenir un contenu non vide et au moins une association texte/article pour pouvoir être enregistrée. |
| **Symfony (avant)** | Le formulaire `PrescriptionTypePrescriptionType` déclarait simplement `libelle` et `associations` sans contrainte `Count(min=1)` ni contrainte `NotBlank`. La sauvegarde persistait directement l'entité si le formulaire était considéré valide. |
| **Legacy** | L'IHM legacy bloque la sauvegarde tant qu'aucune association `texte[]` n'est présente ou que le contenu est vide (`compteurTA > 0` et `LIBELLE_PRESCRIPTIONTYPE != ''`). |
| **Symfony (après)** | Contrainte `NotBlank` ajoutée sur `libelle` et `Count(min=1)` sur `associations` dans `PrescriptionTypePrescriptionType`. La sauvegarde est désormais bloquée si le contenu est vide ou si aucune association n'est définie. |
| **Fichiers modifiés** | `prevarisc-migration/src/Form/PrescriptionTypePrescriptionType.php` |

---

### Écart 2 — La valeur par défaut texte/article n'est plus atteignable depuis les formulaires Symfony

> **Statut** : 📋 Documenté — non-régression (comportement Symfony correct)

| | Détail |
|---|--------|
| **Règles** | RG-PRE-011, cas particulier §7.3 |
| **Spec** | Si l'utilisateur ne choisit pas de texte ou d'article, le système doit utiliser le texte/article par défaut comme valeur technique de substitution. |
| **Symfony** | Les trois sous-formulaires d'association (`dossier`, `prescription type`, `rappel réglementaire`) utilisent des `EntityType` / `ChoiceType` sans option vide ni `required=false`. L'utilisateur doit donc sélectionner un texte visible **et** un article visible, ce qui empêche de saisir une association partielle ou vide puis de basculer sur la valeur par défaut. |
| **Legacy** | Les formulaires legacy utilisent `chosen({allow_single_deselect: true})`, puis convertissent les valeurs vides en ID `1` côté service (`ID_TEXTE = 1` / `ID_ARTICLE = 1`). Le comportement décrit par la spec est donc bien supporté. |
| **Impact** | **Aucun** — le legacy présélectionne les valeurs sans options vides. Les options vides apparaissent uniquement après ajout d'une première association. Lorsqu'une valeur est sélectionnée, il n'est pas possible de la retirer. Le comportement de sélection partielle est un bug isolé dans le legacy et ne doit pas être permis. Le code Symfony est correct. |
| **Fichiers concernés** | `prevarisc-migration/src/Form/PrescriptionDossierAssociationType.php`, `prevarisc-migration/src/Form/PrescriptionTypePrescriptionAssociationType.php`, `prevarisc-migration/src/Form/PrescriptionReglementaireAssociationType.php` |

---

### Écart 3 — Les formulaires Symfony de remplacement texte/article proposent des choix interdits ✅ Corrigé

| | Détail |
|---|--------|
| **Règles** | RG-PRE-010, RG-PRE-011 |
| **Spec** | Lors de la suppression d'un texte ou d'un article, la liste de remplacement doit exclure l'élément supprimé. |
| **Symfony (avant)** | Les formulaires `ReplacePrescriptionTexteListeType` et `ReplacePrescriptionArticleListeType` utilisaient un `EntityType` non filtré. Les templates affichaient l'ensemble des éléments, y compris l'élément courant. |
| **Legacy** | Les écrans legacy excluent explicitement l'ID courant avant d'afficher les radios de remplacement. La valeur par défaut (ID `1`) est proposée car elle existe en doublon en base (libellé identique, ID `1` & ID `114`). |
| **Symfony (après)** | Ajout d'un `query_builder` dans les deux formulaires avec une option `excluded` obligatoire. Les controllers passent désormais l'entité en cours de suppression via `['excluded' => $texteListe]` / `['excluded' => $articleListe]`. Seul l'élément supprimé est exclu, la valeur par défaut reste proposée. |
| **Fichiers modifiés** | `prevarisc-migration/src/Form/ReplacePrescriptionTexteListeType.php`, `prevarisc-migration/src/Form/ReplacePrescriptionArticleListeType.php`, `prevarisc-migration/src/Controller/Prescription/PrescriptionsTextesController.php`, `prevarisc-migration/src/Controller/Prescription/PrescriptionsArticlesController.php` |

---

### Écart 4 — La suppression d'une prescription de dossier n'a plus qu'une confirmation simple

> **Statut** : 📋 Documenté — non-régression (comportement équivalent)

| | Détail |
|---|--------|
| **Règles** | RG-PRE-018 |
| **Spec** | La suppression d'une prescription de dossier doit passer par une confirmation en deux étapes. |
| **Symfony** | Le formulaire de suppression repose sur un simple `confirm()` JavaScript avant l'envoi du `DELETE`. |
| **Legacy** | L'écran legacy affiche successivement `Supprimer la prescription`, puis `Confirmer la suppression` et `Annuler` avant de soumettre l'action `delete`. |
| **Impact** | **Aucun** — le legacy a une validation via remplacement du HTML alors que le code Symfony a une validation via alert JS. Le comportement est identique dans les deux cas : clic sur « Supprimer » → validation → OK ou annulation. La sécurité est équivalente (protection CSRF). |
| **Fichiers concernés** | `prevarisc-migration/templates/dossier/prescriptions/form.html.twig` |

---

### Écart 5 — La reprise multi-établissements est bien bloquée en Symfony, mais sans message explicatif ✅ Corrigé

| | Détail |
|---|--------|
| **Règles** | RG-PRE-019, cas particulier §7.5 |
| **Spec** | La reprise doit être impossible si le dossier concerne plusieurs établissements **et** le système doit afficher le message « Pour pouvoir utiliser cette fonctionnalité, le dossier ne doit concerner qu'un seul établissement. » |
| **Symfony (avant)** | L'IHM masquait simplement le bouton de reprise quand `dossier.etablissements\|length != 1`, et l'endpoint de modale renvoyait un `400` vide si on l'appelait quand même. Aucun message explicatif n'était affiché à l'utilisateur. |
| **Legacy** | La vue `formrecupprescription.phtml` affiche explicitement le message prévu par la spec lorsque `nbEtab != 1`. |
| **Symfony (après)** | Le bouton de reprise est désormais toujours affiché. Lorsque `dossier.etablissements\|length != 1`, il est rendu `disabled` avec un `title` portant le message explicatif. L'endpoint retourne désormais le message dans le corps de la réponse 400. La spec a été mise à jour pour refléter ce comportement (§7.5). |
| **Fichiers modifiés** | `prevarisc-migration/templates/dossier/prescriptions/index.html.twig`, `prevarisc-migration/src/Controller/Dossier/DossierPrescriptionRepriseController.php`, `prevarisc-migration/docs/specs/MOD-04-PRESCRIPTIONS.md` (§7.5) |

---

### Écart 6 — La reprise Symfony renumérote les prescriptions au lieu de conserver leur numéro d'origine

> **Statut** : 📋 Documenté — écart accepté, spec mise à jour

| | Détail |
|---|--------|
| **Règles** | §5.6.7 à §5.6.10, §7.6 |
| **Spec (avant)** | Lors d'une reprise, les prescriptions conservaient leur numéro d'ordre d'origine ; en cas de conflit entre plusieurs dossiers sources, l'ordre de traitement départageait les copies. |
| **Symfony** | Chaque copie reçoit d'abord le prochain numéro disponible dans le dossier cible, puis un renumérotage global est relancé après import. Le numéro source n'est donc pas conservé. |
| **Legacy** | Le legacy recopie `NUM_PRESCRIPTION_DOSSIER` du dossier source dans la copie et ne relance pas de renumérotation globale après la reprise. |
| **Décision** | Écart accepté. Le legacy peut créer des doublons de numéros, ce qui perturbe l'utilisateur. La renumérotation Symfony garantit une séquence continue sans doublon. La spec a été mise à jour pour documenter ce nouveau fonctionnement (§5.6.7, §7.6). |
| **Fichiers modifiés** | `prevarisc-migration/docs/specs/MOD-04-PRESCRIPTIONS.md` (§5.6.7, §7.6) |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-PRE-001 | Hiérarchie catégorie → texte → article | ✅ Aligné entre spec, Symfony et legacy (`PrescriptionTexte` / `PrescriptionArticle`, `Model_DbTable_PrescriptionTexte` / `PrescriptionArticle`) |
| RG-PRE-004 à RG-PRE-007 | Réordonnancement et insertion en fin de liste | ✅ Aligné (`PrescriptionsTypesController::reOrder`, `categoryAdd`, `textsForm`, `articlesForm` ; `Service_Prescriptions::setOrder`, `savecategorieAction`, `savetexteAction`, `savearticleAction`) |
| RG-PRE-008 | Rappels étude/visite + ajout par défaut à la création d'un dossier | ✅ Aligné (`DossierCreationService::initialiserPrescriptionsReglementaires`, `PrescriptionReglementaireRepository::recupPrescRegl` ; legacy `DossierController.php:1045-1052`) |
| RG-PRE-009 et partie « masquage » de RG-PRE-011 | Visibilité des listes et masquage de l'élément par défaut dans les écrans de gestion | ✅ Aligné (`findAllTextes(true)` / `findAllArticles(true)` pour la saisie, `textes.html.twig` et `articles.html.twig` filtrent `id != 1` ; même filtrage dans `gestion-textes.phtml` et `gestion-articles.phtml`) |
| RG-PRE-012 à RG-PRE-017 | Trois catégories dossier, numérotation continue exploitation/amélioration, recalcul, ajout libre et détachement d'un type à l'édition | ✅ Aligné (`DossierPrescriptionService.php:89-161,247-278`, `PrescriptionDossierType.php`; legacy `Service_Dossier::savePrescription`) |
| RG-PRE-020 à RG-PRE-025 | Exclusion des prescriptions levées, badge dossier source, multi-sélection, verrouillage, recherche mots-clés, date/justificatif de levée | ✅ Aligné dans les deux versions |

---

## Recommandations

Tous les écarts ont été traités. Aucune action complémentaire requise sur ce module.

