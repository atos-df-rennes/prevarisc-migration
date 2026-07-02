# Rapport d'écarts — MOD-10 TEXTES APPLICABLES

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-10-TEXTES-APPLICABLES.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse
| Indicateur | Valeur |
|---|---|
| Règles de gestion analysées | 15 (RG-TXT-001 à RG-TXT-015) |
| Écarts spec ↔ Symfony | 5 |
| Écarts spec ↔ Legacy | 2 |
| Écarts Symfony ↔ Legacy | 4 |

---

## Écarts détaillés
### Écart 1 — La synchronisation visite/groupe de visites → établissement n'est pas portée dans Symfony
| | Détail |
|---|---|
| **Règles** | RG-TXT-009, RG-TXT-010, RG-TXT-011, § 5.2, § 7.4 |
| **Spec** | Lorsqu'un texte est ajouté/retiré dans un dossier de type visite ou groupe de visites, la modification doit être répercutée sur le premier établissement rattaché ; cette synchronisation ne s'applique pas aux études. |
| **Symfony (avant)** | L'écran dossier édite simplement la collection Doctrine puis exécute `flush()` (`src/Controller/Dossier/DossierController.php:717-730`). La copie initiale à la création existe bien (`src/Service/Dossier/DossierCreationService.php:80-90`), mais aucune logique symétrique d'ajout/retrait vers l'établissement n'est exécutée lors des modifications du dossier. |
| **Symfony (après)** | La méthode `editTextesApplicables()` du contrôleur appelle désormais `DossierCreationService::synchroniserTextesApplicablesVersEtablissement($dossier)` après la sauvegarde (`src/Controller/Dossier/DossierController.php:743-769`). Cette nouvelle méthode synchronise les textes du dossier vers le premier établissement pour les types visite/groupe de visites, reproduisant exactement le comportement legacy. |
| **Legacy** | Le service legacy `saveTextesApplicables()` récupère le premier établissement pour les types visite/groupe, puis supprime ou crée les liaisons à la fois dans `dossiertextesappl` et `etablissementtextapp` (`application/services/Dossier.php:211-247`). |
| **Impact** | ✅ **Résolu** — la fiche établissement et le dossier restent synchronisés en Symfony comme en legacy. |
| **Fichiers modifiés** | `prevarisc-migration/src/Controller/Dossier/DossierController.php`, `prevarisc-migration/src/Service/Dossier/DossierCreationService.php` |

---

### Écart 2 — L'ordre d'affichage par défaut n'est pas réellement calculé « en fin de liste »
| | Détail |
|---|---|
| **Règles** | RG-TXT-003 |
| **Spec** | Un nouveau texte doit recevoir un ordre d'affichage en fin de liste, c'est-à-dire la valeur maximale cohérente dans sa catégorie. |
| **Symfony** | Le champ caché `num` utilise une valeur fixe `9999` quand il est vide (`src/Form/TexteApplicableType.php:30-32`) ; aucun calcul de `MAX(num)+1` n'est effectué à l'enregistrement. |
| **Legacy** | Le legacy utilise aussi une valeur fixe (`99999`) à la création **et** à l'édition (`application/controllers/GestionTextesApplicablesController.php:31-46`), ce qui déplace en plus un texte modifié en fin de liste à chaque sauvegarde. |
| **Impact** | **Moyen** — la règle métier est approximée dans les deux implémentations ; le legacy ajoute une dérive supplémentaire en réordonnant les textes édités. |
| **Fichiers concernés** | `prevarisc-migration/src/Form/TexteApplicableType.php`, `prevarisc/application/controllers/GestionTextesApplicablesController.php` |

---

### Écart 3 — La gestion d'erreur à la sauvegarde n'est pas restituée dans Symfony
| | Détail |
|---|---|
| **Règles** | § 4.2, RG-TXT-013 |
| **Spec** | En cas d'échec de sauvegarde, l'application doit afficher « Erreur lors de la sauvegarde du texte » avec le détail de l'erreur, puis revenir à la liste. |
| **Symfony** | Les actions `ajouter()` et `modifier()` appellent directement `serviceTexteApplicable->save(...)` puis affichent un message de succès (`src/Controller/TextesApplicablesController.php:51-68`, `79-92`). Aucun `try/catch` ni message d'erreur métier n'encadre la sauvegarde. |
| **Legacy** | Le contrôleur Zend encapsule la sauvegarde dans un `try/catch` et remonte un flash `Erreur lors de la sauvegarde du texte` avec `getMessage()` (`application/controllers/GestionTextesApplicablesController.php:26-68`). |
| **Impact** | **Moyen** — en cas d'exception Doctrine ou SQL, Symfony ne restitue pas le comportement attendu par la spec ni par le legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/TextesApplicablesController.php`, `prevarisc/application/controllers/GestionTextesApplicablesController.php` |

---

### Écart 4 — Les textes déjà associés ne sont plus mis en gras dans les écrans de modification Symfony
| | Détail |
|---|---|
| **Règles** | § 5.1, § 5.2 |
| **Spec** | Dans les écrans d'association établissement/dossier, les textes déjà associés doivent être cochés **et affichés en gras**. |
| **Symfony** | Le template Symfony se contente d'un `form_row(texte_applicable)` sans style conditionnel pour les éléments déjà cochés (`templates/etablissement/edit_textes_applicables.html.twig:18-25`). |
| **Legacy** | Le legacy applique explicitement `font-weight: bold` si le texte est déjà lié à l'établissement (`application/views/scripts/etablissement/edit-textes-applicables.phtml:15-19`). |
| **Impact** | **Faible à moyen** — perte d'un repère visuel prévu par la spec et utile sur les listes volumineuses. |
| **Fichiers concernés** | `prevarisc-migration/templates/etablissement/edit_textes_applicables.html.twig`, `prevarisc/application/views/scripts/etablissement/edit-textes-applicables.phtml` |

---

### Écart 5 — Le réordonnement AJAX Symfony renvoie une redirection HTTP au lieu d'une réponse légère
| | Détail |
|---|---|
| **Règles** | RG-TXT-004 |
| **Spec** | Le réordonnement doit être enregistré en arrière-plan sans rechargement de page. |
| **Symfony** | Le JS appelle `admin_textes_applicables_updateorder` en AJAX (`templates/textes_applicables/index.html.twig:22-26`), mais le contrôleur répond par `redirectToRoute(...)` (`src/Controller/TextesApplicablesController.php:103-126`). La page ne se recharge pas, mais la réponse HTTP n'est pas adaptée au mode AJAX. |
| **Legacy** | Le legacy désactive explicitement le rendu et retourne une réponse vide pour `updateorderAction()` (`application/controllers/GestionTextesApplicablesController.php:74-86`). |
| **Impact** | **Faible** — pas de casse visible aujourd'hui, mais comportement réseau inutilement ambigu et moins propre que le flux legacy. |
| **Fichiers concernés** | `prevarisc-migration/templates/textes_applicables/index.html.twig`, `prevarisc-migration/src/Controller/TextesApplicablesController.php`, `prevarisc/application/controllers/GestionTextesApplicablesController.php` |

---

### Écart 6 — La liste des natures déclenchant l'héritage des textes ne correspond pas littéralement à la spec
| | Détail |
|---|---|
| **Règles** | RG-TXT-008, § 7.7 |
| **Spec** | L'héritage automatique concerne les visites périodiques, de contrôle et inopinées (simples et groupes). |
| **Symfony** | La constante utilisée pour la copie vaut `[21, 22, 24, 26, 27, 29]` (`src/Constant/DossierNature.php:70-76`) et le service de création s'y réfère directement (`src/Service/Dossier/DossierCreationService.php:82-90`). |
| **Legacy** | Le comportement historique repose sur la même logique métier lors de la création dossier, mais avec le même jeu de natures côté application. |
| **Impact** | **Moyen** — la migration est cohérente avec le legacy, mais pas totalement avec la formulation littérale de la spec ; une validation métier est nécessaire pour confirmer que l'ensemble `[21,22,24,26,27,29]` est bien le référentiel attendu. |
| **Fichiers concernés** | `prevarisc-migration/src/Constant/DossierNature.php`, `prevarisc-migration/src/Service/Dossier/DossierCreationService.php` |

---

## Comportements alignés (confirmés)
| Règles | Sujet | Constat |
|---|---|---|
| RG-TXT-001 | Visibilité des textes dans les écrans d'association | ✅ Aligné : seuls les textes visibles sont proposés dans les formulaires établissement/dossier (`src/Form/TextesApplicablesEtablissementType.php`, `src/Form/Dossier/DossierTextesApplicablesType.php`). |
| RG-TXT-002 | Regroupement par type et tri d'affichage | ✅ Aligné : le repository Symfony trie par type puis `num`, comme le legacy (`src/Repository/TexteApplicableRepository.php:49-56`). |
| RG-TXT-005 | Pas de suppression métier | ✅ Aligné : ni le contrôleur legacy ni le contrôleur Symfony n'exposent une action de suppression des textes applicables ; le masquage reste la seule voie. |
| RG-TXT-006 / § 7.6 | Alimentation du référentiel de prescriptions | ✅ Aligné : une sauvegarde avec l'option cochée crée une nouvelle entrée de prescription sans déduplication (`src/Service/TexteApplicable.php:19-29`, `application/controllers/GestionTextesApplicablesController.php:49-55`). |
| RG-TXT-007 / RG-TXT-012 | Association unique et traitement ajout/retrait | ✅ Aligné : Doctrine gère l'association dossier/établissement par collection sans doublon, dans l'esprit du couple clé composite historique. |
| RG-TXT-013 | Création/édition en modale | ✅ Aligné : la liste admin ouvre les formulaires via AJAX dans une modale Symfony, comme le legacy (`templates/textes_applicables/index.html.twig:30-49`). |
| § 7.1 / § 7.2 | Message « Il n'y a pas de texte applicable » | ✅ Aligné : le message vide et le lien de modification sont présents dans les écrans Symfony établissement/dossier, conformément au legacy. |
| RG-TXT-014 | Récupération triée des textes pour les documents | ✅ Aligné : les textes du dossier sont récupérés et regroupés par type avant rendu documentaire côté Symfony, comme en legacy. |
| RG-TXT-015 | Option prescriptions à Non par défaut | ✅ Aligné : l'option est initialisée à `false` dans le formulaire Symfony (`src/Form/TexteApplicableType.php:37-49`) et à `Non` dans le formulaire legacy. |

---

## Recommandations
1. Porter en priorité la synchronisation dossier visite/groupe ↔ premier établissement, car c'est l'écart fonctionnel majeur entre Symfony et le legacy.
2. Remplacer les valeurs fixes `9999/99999` par un calcul explicite « fin de liste » et supprimer le reset d'ordre à l'édition côté legacy migré.
3. Ajouter un `try/catch` métier dans les actions Symfony d'ajout/édition pour restituer le message d'erreur attendu par la spec.
4. Réintroduire le repère visuel « texte déjà associé en gras » dans les formulaires Symfony d'établissement et de dossier.
5. Clarifier avec le métier la liste exacte des natures couvertes par RG-TXT-008 avant de considérer la migration comme strictement conforme à la spec.
