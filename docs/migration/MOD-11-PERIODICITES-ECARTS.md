# Rapport d'écarts — MOD-11 PÉRIODICITÉS

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-11-PERIODICITES.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse
| Indicateur | Valeur |
|---|---|
| Règles de gestion analysées | 20 (RG-PER-001 à RG-PER-020) |
| Écarts spec ↔ Symfony | 1 |
| Écarts spec ↔ Legacy | 1 |
| Écarts Symfony ↔ Legacy | 4 |

---

## Écarts détaillés
### Écart 1 — La borne métier 0..999 mois n'est pas validée côté serveur
| | Détail |
|---|---|
| **Règles** | RG-PER-001, § 3.2 |
| **Spec** | Les périodicités sont des entiers en mois, limités à trois chiffres, donc de `0` à `999`. |
| **Symfony** | La sauvegarde valide seulement « entier » via `FILTER_VALIDATE_INT` (`src/Controller/TableauDesPeriodicitesController.php:88-100`) ; aucune borne `min/max` n'est contrôlée. Le template limite bien `maxlength="3"`, mais seulement côté navigateur (`templates/tableau_des_periodicites/index.html.twig:66-76`, `99-100`). |
| **Legacy** | Le legacy sauvegarde chaque valeur postée sans vérification métier de borne (`application/controllers/TableauDesPeriodicitesController.php:44-57`) ; lui aussi se repose uniquement sur `maxlength="3"` dans la vue (`application/views/scripts/tableau-des-periodicites/index.phtml:40-49`, `74-75`). |
| **Impact** | **Moyen** — une soumission hors interface (ou un contournement client) peut injecter des valeurs hors plage, contrairement à la spec. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/TableauDesPeriodicitesController.php`, `prevarisc-migration/templates/tableau_des_periodicites/index.html.twig`, `prevarisc/application/controllers/TableauDesPeriodicitesController.php`, `prevarisc/application/views/scripts/tableau-des-periodicites/index.phtml` |

**Retour utilisateur** : Écart de spec confirmé. Ajouter une borne côté serveur.

---

### Écart 2 — Le vidage du cache de recherche dépend encore d'un pont legacy côté Symfony
| | Détail |
|---|---|
| **Règles** | RG-PER-018 |
| **Spec** | L'application en masse doit vider le cache de recherche après exécution réussie. |
| **Symfony** | Après `applyPeriodicites()`, le contrôleur ne vide pas le cache lui-même ; il redirige vers `/legacy/admin/clear-search-cache-and-redirect?...` avec un `@todo` de décommissionnement (`src/Controller/TableauDesPeriodicitesController.php:139-147`). |
| **Legacy** | Le flux historique exécutait l'application puis s'appuyait sur l'écosystème Zend existant ; la migration Symfony n'est donc pas autonome sur ce point. |
| **Impact** | **Moyen** — la règle est fonctionnellement couverte tant que le legacy reste disponible, mais la migration n'est pas complète ni découplée. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/TableauDesPeriodicitesController.php` |

---

### Écart 3 — L'action « Appliquer sur TOUS les établissements » était un GET sans protection CSRF en legacy
| | Détail |
|---|---|
| **Règles** | § 4.3, RG-PER-011 |
| **Spec** | L'application en masse doit demander confirmation et garantir une exécution maîtrisée. |
| **Symfony** | Symfony exécute l'action via un formulaire `POST` avec token CSRF (`src/Controller/TableauDesPeriodicitesController.php:127-137`, `templates/tableau_des_periodicites/index.html.twig:114-119`). |
| **Legacy** | Le legacy exposait un lien direct `/tableau-des-periodicites/apply` déclenché en `GET`, avec une simple confirmation JS (`application/views/scripts/tableau-des-periodicites/index.phtml:84-86`). |
| **Impact** | **Faible à moyen** — amélioration nette en Symfony ; le legacy restait plus exposé aux déclenchements non désirés. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/TableauDesPeriodicitesController.php`, `prevarisc-migration/templates/tableau_des_periodicites/index.html.twig`, `prevarisc/application/views/scripts/tableau-des-periodicites/index.phtml` |

---

### Écart 4 — La sauvegarde legacy de la matrice n'est pas atomique, contrairement à Symfony
| | Détail |
|---|---|
| **Règles** | § 4.2 |
| **Spec** | La modification de la matrice doit enregistrer un ensemble cohérent de valeurs. |
| **Symfony** | Symfony prépare l'ensemble des entités puis exécute un `flush()` unique (`src/Controller/TableauDesPeriodicitesController.php:102-123`). |
| **Legacy** | Le legacy sauvegarde ligne par ligne dans une boucle (`application/controllers/TableauDesPeriodicitesController.php:44-57`). En cas d'échec en cours de route, une partie de la matrice peut être déjà persistée. |
| **Impact** | **Faible à moyen** — pas de régression Symfony, mais un comportement legacy moins robuste à garder en tête pour l'analyse d'écarts. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/TableauDesPeriodicitesController.php`, `prevarisc/application/controllers/TableauDesPeriodicitesController.php` |

---

### Écart 5 — Le typage interne autour de `ID_TYPE` reste fragile dans la migration Symfony
| | Détail |
|---|---|
| **Règles** | RG-PER-002, RG-PER-004, RG-PER-005 |
| **Spec** | La matrice est indexée par catégorie, type/classe et local à sommeil selon le genre d'établissement. |
| **Symfony** | L'entité `Periodicite` mappe `ID_TYPE` en `string` (`src/Entity/Periodicite.php`), tandis que `EtablissementType` reste un identifiant entier (`src/Entity/EtablissementType.php:17-25`). Le repository convertit implicitement l'identifiant de type d'établissement vers `gn4(...)` (`src/Repository/PeriodiciteRepository.php:111-123`). |
| **Legacy** | Le legacy travaillait avec des identifiants SQL plus permissifs et n'exposait pas cette tension de typage dans le code PHP. |
| **Impact** | **Faible** — pas de bug métier observé, mais point de fragilité technique si le typage ou la source des identifiants évolue. |
| **Fichiers concernés** | `prevarisc-migration/src/Entity/Periodicite.php`, `prevarisc-migration/src/Entity/EtablissementType.php`, `prevarisc-migration/src/Repository/PeriodiciteRepository.php` |

---

## Comportements alignés (confirmés)
| Règles | Sujet | Constat |
|---|---|---|
| RG-PER-003 | Valeur `0` affichée en opacité réduite | ✅ Aligné : les deux interfaces appliquent une opacité réduite aux champs à `0` (`templates/tableau_des_periodicites/index.html.twig:8-16`, `application/views/scripts/tableau-des-periodicites/index.phtml:91-99`). |
| RG-PER-006 / RG-PER-019 | Types obligatoirement avec locaux à sommeil | ✅ Aligné : les colonnes « sans locaux à sommeil » sont masquées pour les types configurés et l'API Symfony force `local_sommeil = true` pour ces types (`templates/tableau_des_periodicites/index.html.twig:66-71`, `src/Controller/Api/EtablissementController.php:200-207`). |
| RG-PER-007 | Périodicité d'un site = minimum des ERP enfants ouverts | ✅ Aligné : la logique métier est conservée dans les services établissement côté legacy et Symfony. |
| RG-PER-008 | Périodicité d'une cellule héritée du parent | ✅ Aligné : règle conservée côté service établissement. |
| RG-PER-010 / RG-PER-011 | Application en masse transactionnelle | ✅ Aligné : `applyPeriodicites()` en Symfony ouvre explicitement une transaction (`src/Repository/PeriodiciteRepository.php:62-93`) et le legacy faisait déjà de même au niveau SQL. |
| RG-PER-012 / RG-PER-013 | Calcul de la prochaine visite | ✅ Aligné : la logique « dernière visite périodique + périodicité » reste le mécanisme de référence dans les deux piles. |
| RG-PER-015 | Affichage optionnel en années/mois | ✅ Aligné : la transformation d'affichage est conservée dans les services établissement. |
| RG-PER-016 / RG-PER-017 | Date de référence configurable et détection des ERP en retard | ✅ Aligné : la requête Symfony tient compte du paramètre `PREVARISC_DATE_COMMISSION_RELANCE_PERIODICITE` et conserve la logique de détection des ERP en retard (`src/Repository/EtablissementRepository.php:339-385`). |
| RG-PER-020 | Écran de confirmation avec périodicité saisie / conseillée | ✅ Aligné : le flux Symfony conserve un écran de confirmation dédié dans la fiche établissement, comme le legacy. |

---

## Recommandations
1. Ajouter une vraie validation serveur `0 <= periodicite <= 999` dans l'action de sauvegarde de la matrice et, si possible, au niveau formulaire/DTO.
2. Remplacer la redirection `/legacy/admin/clear-search-cache-and-redirect` par un mécanisme Symfony natif avant la suppression définitive du legacy.
3. Conserver l'amélioration Symfony sur le `POST + CSRF` pour l'action d'application globale ; ne pas réintroduire de déclenchement en `GET`.
4. Documenter le point de fragilité de typage autour de `ID_TYPE` pour éviter des régressions lors d'une future refactorisation Doctrine.
