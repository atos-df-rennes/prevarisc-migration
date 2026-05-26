# Rapport d'écarts — MOD-04 PRESCRIPTIONS

> **Date** : 2026-05-22  
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
| Impact critique | 1 (substitution texte/article par défaut non accessible en Symfony) |

---

## Écarts détaillés

### Écart 1 — Validation incomplète des prescriptions types côté Symfony

| | Détail |
|---|--------|
| **Règles** | RG-PRE-002 |
| **Spec** | Une prescription type doit contenir un contenu non vide et au moins une association texte/article pour pouvoir être enregistrée. |
| **Symfony** | Le formulaire `PrescriptionTypePrescriptionType` déclare simplement `libelle` et `associations` sans contrainte `Count(min=1)` ni validation explicite côté contrôleur. La sauvegarde persiste directement l'entité si le formulaire est considéré valide. |
| **Legacy** | L'IHM legacy bloque la sauvegarde tant qu'aucune association `texte[]` n'est présente ou que le contenu est vide (`compteurTA > 0` et `LIBELLE_PRESCRIPTIONTYPE != ''`). |
| **Impact** | **Moyen** — une prescription type sans association peut être enregistrée côté Symfony, alors que la spec et l'IHM legacy l'interdisent. |
| **Fichiers concernés** | `prevarisc-migration/src/Form/PrescriptionTypePrescriptionType.php:13-24`, `prevarisc-migration/src/Controller/Prescription/PrescriptionsTypesController.php:304-314`, `prevarisc/application/views/scripts/gestion-prescriptions/index.phtml:518-575` |

---

### Écart 2 — La valeur par défaut texte/article n'est plus atteignable depuis les formulaires Symfony

| | Détail |
|---|--------|
| **Règles** | RG-PRE-011, cas particulier §7.3 |
| **Spec** | Si l'utilisateur ne choisit pas de texte ou d'article, le système doit utiliser le texte/article par défaut comme valeur technique de substitution. |
| **Symfony** | Les trois sous-formulaires d'association (`dossier`, `prescription type`, `rappel réglementaire`) utilisent des `EntityType` / `ChoiceType` sans option vide ni `required=false`. L'utilisateur doit donc sélectionner un texte visible **et** un article visible, ce qui empêche de saisir une association partielle ou vide puis de basculer sur la valeur par défaut. |
| **Legacy** | Les formulaires legacy utilisent `chosen({allow_single_deselect: true})`, puis convertissent les valeurs vides en ID `1` côté service (`ID_TEXTE = 1` / `ID_ARTICLE = 1`). Le comportement décrit par la spec est donc bien supporté. |
| **Impact** | **Élevé** — en Symfony, il n'est plus possible de saisir correctement une prescription ne citant qu'un texte, qu'un article, ou aucun des deux avec recours à la valeur par défaut. |
| **Fichiers concernés** | `prevarisc-migration/src/Form/PrescriptionDossierAssociationType.php:31-54`, `prevarisc-migration/src/Form/PrescriptionTypePrescriptionAssociationType.php:31-54`, `prevarisc-migration/src/Form/PrescriptionReglementaireAssociationType.php:35-55`, `prevarisc/application/views/scripts/gestion-prescriptions/formprescription.phtml:82-110`, `prevarisc/application/views/scripts/gestion-prescriptions/prescription-form.phtml:147-184`, `prevarisc/application/views/scripts/dossier/prescription-edit.phtml:112-137`, `prevarisc/application/services/Prescriptions.php:71-84,209-218`, `prevarisc/application/services/Dossier.php:501-518,579-596` |

---

### Écart 3 — Les formulaires Symfony de remplacement texte/article proposent des choix interdits

| | Détail |
|---|--------|
| **Règles** | RG-PRE-010, RG-PRE-011 |
| **Spec** | Lors de la suppression d'un texte ou d'un article, la liste de remplacement doit exclure l'élément supprimé et l'élément par défaut. |
| **Symfony** | Les formulaires `ReplacePrescriptionTexteListeType` et `ReplacePrescriptionArticleListeType` utilisent un `EntityType` non filtré. Les templates affichent donc l'ensemble des éléments, y compris l'élément courant et l'élément par défaut. |
| **Legacy** | Les écrans legacy excluent explicitement l'ID courant **et** l'ID `1` avant d'afficher les radios de remplacement. |
| **Impact** | **Moyen à élevé** — l'utilisateur peut choisir un remplacement invalide (par lui-même ou par la valeur par défaut), contrairement à la spec et au legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Form/ReplacePrescriptionTexteListeType.php:12-18`, `prevarisc-migration/src/Form/ReplacePrescriptionArticleListeType.php:12-18`, `prevarisc-migration/templates/prescriptions_textes/replacement_form.html.twig:35-55`, `prevarisc-migration/templates/prescriptions_articles/form_replacement.html.twig:35-55`, `prevarisc/application/views/scripts/gestion-prescriptions/gestion-textes-replace.phtml:51-66`, `prevarisc/application/views/scripts/gestion-prescriptions/gestion-articles-replace.phtml:51-66` |

---

### Écart 4 — La suppression d'une prescription de dossier n'a plus qu'une confirmation simple

| | Détail |
|---|--------|
| **Règles** | RG-PRE-018 |
| **Spec** | La suppression d'une prescription de dossier doit passer par une confirmation en deux étapes. |
| **Symfony** | Le formulaire de suppression repose sur un simple `confirm()` JavaScript avant l'envoi du `DELETE`. |
| **Legacy** | L'écran legacy affiche successivement `Supprimer la prescription`, puis `Confirmer la suppression` et `Annuler` avant de soumettre l'action `delete`. |
| **Impact** | **Faible à moyen** — la suppression reste protégée par le token CSRF, mais la sécurité UX est moins forte que dans la spec et le legacy. |
| **Fichiers concernés** | `prevarisc-migration/templates/dossier/prescriptions/form.html.twig:85-93`, `prevarisc/application/views/scripts/dossier/prescription-edit.phtml:7-10,139-156` |

---

### Écart 5 — La reprise multi-établissements est bien bloquée en Symfony, mais sans message explicatif

| | Détail |
|---|--------|
| **Règles** | RG-PRE-019, cas particulier §7.5 |
| **Spec** | La reprise doit être impossible si le dossier concerne plusieurs établissements **et** le système doit afficher le message « Pour pouvoir utiliser cette fonctionnalité, le dossier ne doit concerner qu'un seul établissement. » |
| **Symfony** | L'IHM masque simplement le bouton de reprise quand `dossier.etablissements|length != 1`, et l'endpoint de modale renvoie un `400` vide si on l'appelle quand même. Aucun message explicatif n'est affiché à l'utilisateur. |
| **Legacy** | La vue `formrecupprescription.phtml` affiche explicitement le message prévu par la spec lorsque `nbEtab != 1`. |
| **Impact** | **Faible** — la règle de blocage est respectée, mais l'utilisateur ne comprend pas pourquoi l'action n'est pas disponible. |
| **Fichiers concernés** | `prevarisc-migration/templates/dossier/prescriptions/index.html.twig:11-29`, `prevarisc-migration/src/Controller/Dossier/DossierPrescriptionRepriseController.php:51-55`, `prevarisc/application/views/scripts/dossier/formrecupprescription.phtml:1-3` |

---

### Écart 6 — La reprise Symfony renumérote les prescriptions au lieu de conserver leur numéro d'origine

| | Détail |
|---|--------|
| **Règles** | §5.6.7 à §5.6.10, §7.6 |
| **Spec** | Lors d'une reprise, les prescriptions conservent leur numéro d'ordre d'origine ; en cas de conflit entre plusieurs dossiers sources, l'ordre de traitement départage les copies. |
| **Symfony** | Chaque copie reçoit d'abord le prochain numéro disponible dans le dossier cible, puis un renumérotage global est relancé après import. Le numéro source n'est donc pas conservé. |
| **Legacy** | Le legacy recopie `NUM_PRESCRIPTION_DOSSIER` du dossier source dans la copie et ne relance pas de renumérotation globale après la reprise. |
| **Impact** | **Moyen** — le résultat affiché en Symfony diffère du legacy et du texte de spec pour les reprises depuis un dossier déjà rempli ou depuis plusieurs sources. |
| **Fichiers concernés** | `prevarisc-migration/src/Service/DossierPrescriptionService.php:334-368`, `prevarisc/application/services/Dossier.php:636-645`, `prevarisc/application/controllers/DossierController.php:3076-3084` |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-PRE-001 | Hiérarchie catégorie → texte → article | ✅ Aligné entre spec, Symfony et legacy (`PrescriptionTexte` / `PrescriptionArticle`, `Model_DbTable_PrescriptionTexte` / `PrescriptionArticle`) |
| RG-PRE-004 à RG-PRE-007 | Réordonnancement et insertion en fin de liste | ✅ Aligné (`PrescriptionsTypesController::reOrder`, `categoryAdd`, `textsForm`, `articlesForm` ; `Service_Prescriptions::setOrder`, `savecategorieAction`, `savetexteAction`, `savearticleAction`) |
| RG-PRE-008 | Rappels étude/visite + ajout par défaut à la création d'un dossier | ✅ Aligné (`DossierCreationService::initialiserPrescriptionsReglementaires`, `PrescriptionReglementaireRepository::recupPrescRegl` ; legacy `DossierController.php:1045-1052`) |
| RG-PRE-009 et partie « masquage » de RG-PRE-011 | Visibilité des listes et masquage de l'élément par défaut dans les écrans de gestion | ✅ Aligné (`findAllTextes(true)` / `findAllArticles(true)` pour la saisie, `textes.html.twig` et `articles.html.twig` filtrent `id != 1` ; même filtrage dans `gestion-textes.phtml` et `gestion-articles.phtml`) |
| RG-PRE-012 à RG-PRE-017 | Trois catégories dossier, numérotation continue exploitation/amélioration, recalcul, ajout libre et détachement d'un type à l'édition | ✅ Aligné (`DossierPrescriptionService.php:89-161,247-278`, `PrescriptionDossierType.php`; legacy `Service_Dossier::savePrescription`) |
| RG-PRE-020 à RG-PRE-025 | Exclusion des prescriptions levées, badge dossier source, multi-sélection, verrouillage, recherche mots-clés, date/justificatif de levée | ✅ Aligné dans les deux versions (hors message multi-établissement et numérotation de reprise, voir écarts n°5 et n°6) |

---

## Recommandations

1. **Priorité haute** : ajouter une vraie validation métier sur les prescriptions types Symfony (`Count(min=1)` sur les associations, validation explicite du contenu), au minimum au niveau du formulaire `PrescriptionTypePrescriptionType`.
2. **Priorité haute** : rétablir la logique legacy/spec de substitution par défaut dans les trois formulaires d'association Symfony (`required=false` + option vide + mapping vers l'ID technique par défaut).
3. **Priorité haute** : filtrer les formulaires de remplacement texte/article pour exclure l'élément courant et l'élément par défaut avant soumission.
4. **Priorité moyenne** : remettre une confirmation en deux étapes pour la suppression d'une prescription dossier, et afficher le message métier lorsque la reprise est impossible sur un dossier multi-établissements.
5. **Priorité moyenne** : arbitrer avec le métier la règle attendue sur la numérotation lors d'une reprise (conservation du numéro source vs renumérotation globale), puis aligner Symfony sur cette décision et documenter la règle retenue.
