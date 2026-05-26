# Rapport d'écarts — MOD-19 STATISTIQUES

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-19-STATISTIQUES.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 19 (RG-STA-001 à RG-STA-019) |
| Écarts spec ↔ Symfony | 19 (module non migré) |
| Écarts spec ↔ Legacy | 6 |
| Écarts Symfony ↔ Legacy | 1 (legacy présent, Symfony absent) |
| Impact critique | 1 (aucune fonctionnalité disponible côté Symfony) |

---

## Écarts détaillés

### Écart 1 — Le module est totalement absent côté Symfony

| | Détail |
|---|--------|
| **Règles** | RG-STA-001 à RG-STA-019, § 3, § 4 |
| **Spec** | Le module doit proposer un écran d'accueil, 4 extractions, des paramètres dynamiques JSON, un affichage HTML et un export XML. |
| **Symfony** | Aucun contrôleur, template, route ou entrée de manifest relatifs aux statistiques n'a été trouvé dans `prevarisc-migration/src`, `prevarisc-migration/templates` ni dans `docs/tech/migration/MANIFEST.yaml`. |
| **Legacy** | Le module existe encore via `StatistiquesController`, son modèle `Model_DbTable_Statistiques` et les vues `index`, `extraction` et `extraction.xml` (`prevarisc/application/controllers/StatistiquesController.php:7-267`, `prevarisc/application/models/DbTable/Statistiques.php:3-224`, `prevarisc/application/views/scripts/statistiques/index.phtml:20-128`). |
| **Impact** | **Critique** — aucune des 4 extractions spécifiées n'est disponible dans la migration Symfony. |
| **Fichiers concernés** | `prevarisc/application/controllers/StatistiquesController.php`, `prevarisc/application/models/DbTable/Statistiques.php`, `prevarisc/application/views/scripts/statistiques/*.phtml` |

---

### Écart 2 — Le legacy est explicitement marqué « non fonctionnel » et l'extraction 4 ne filtre pas la bonne période

| | Détail |
|---|--------|
| **Règles** | RG-STA-008, RG-STA-009, § 3.4, § 6 |
| **Spec** | L'extraction 4 doit lister les ERP avec visites périodiques programmées sur la période demandée, à partir des dossiers futurs de nature visite périodique ou inopinée. |
| **Symfony** | Non applicable : module absent. |
| **Legacy** | Le contrôleur signale dès l'en-tête : « Code non fonctionnel et non utilisé actuellement. A ne pas migrer. » (`prevarisc/application/controllers/StatistiquesController.php:3-6`). En plus, `listeDesERPVisitePeriodique()` calcule bien une `DATEVISITE_DOSSIER` future (`prevarisc/application/models/DbTable/Statistiques.php:82-90`), mais filtre ensuite la période sur `DATE_ETABLISSEMENTINFORMATIONS` et non sur la date de visite (`prevarisc/application/models/DbTable/Statistiques.php:115-123`). |
| **Impact** | **Élevé** — le legacy ne constitue pas une référence fiable pour l'extraction 4 ; il peut exclure ou inclure des ERP sur une mauvaise base temporelle. |
| **Fichiers concernés** | `prevarisc/application/controllers/StatistiquesController.php`, `prevarisc/application/models/DbTable/Statistiques.php` |

---

### Écart 3 — Aucun contrôle d'accès métier n'est implémenté dans le legacy

| | Détail |
|---|--------|
| **Règles** | § 2, § 6.1, § 6.2 |
| **Spec** | La spec demande explicitement une remise en état avec contrôle d'accès basé sur les rôles avant toute exploitation du module. |
| **Symfony** | Aucun contrôle n'existe puisque le module n'est pas migré. |
| **Legacy** | `StatistiquesController` ne contient aucune vérification de privilège ou d'ACL avant d'afficher l'écran ni avant d'exécuter les extractions (`prevarisc/application/controllers/StatistiquesController.php:17-27`, `51-266`). Le menu principal expose en outre un lien direct `/statistiques` (`prevarisc/application/layouts/includes/nav-main.phtml:37-38`). |
| **Impact** | **Élevé** — en l'état, le module legacy n'applique pas la restriction d'accès recommandée par la spec. |
| **Fichiers concernés** | `prevarisc/application/controllers/StatistiquesController.php`, `prevarisc/application/layouts/includes/nav-main.phtml` |

---

### Écart 4 — Les dates de référence sont concaténées dans le SQL avec une validation minimale

| | Détail |
|---|--------|
| **Règles** | RG-STA-002, RG-STA-007, RG-STA-016, § 6.1 point 3 |
| **Spec** | Les dates utilisateur sont au format `jj/mm/aaaa` et la remise en état doit utiliser des requêtes paramétrées. |
| **Symfony** | Non applicable : module absent. |
| **Legacy** | Les dates sont converties par `getDate()` avec un simple `explode('/')` puis concaténées dans plusieurs `Zend_Db_Expr` (`prevarisc/application/models/DbTable/Statistiques.php:58-61`, `120-123`, `161-170`, `213-223`). La validation ne vérifie ni la validité calendaire ni l'usage de paramètres SQL préparés. |
| **Impact** | **Élevé** — dette de sécurité et de robustesse explicitement mentionnée dans la spec. |
| **Fichiers concernés** | `prevarisc/application/models/DbTable/Statistiques.php` |

---

### Écart 5 — Aucune pagination n'est prévue malgré les volumes potentiels

| | Détail |
|---|--------|
| **Règles** | § 4.1, § 6.1 point 4, § 6.2 point 3 |
| **Spec** | La spec identifie l'absence de pagination comme un problème à corriger lors de la remise en état. |
| **Symfony** | Non applicable : module absent. |
| **Legacy** | Les extractions affichent tous les résultats sur une seule page et `go()` exécute un `fetchAll(...)->toArray()` sans limite (`prevarisc/application/models/DbTable/Statistiques.php:204-208`). La vue d'extraction affiche ensuite le tableau complet (`prevarisc/application/views/scripts/statistiques/extraction.phtml:6-36`). |
| **Impact** | **Élevé** — risque de lenteur et de surcharge mémoire sur un parc important d'ERP. |
| **Fichiers concernés** | `prevarisc/application/models/DbTable/Statistiques.php`, `prevarisc/application/views/scripts/statistiques/extraction.phtml` |

---

### Écart 6 — L'interface legacy conserve des fragilités fonctionnelles et de présentation

| | Détail |
|---|--------|
| **Règles** | RG-STA-010 à RG-STA-017, § 6.1 points 5 à 7 |
| **Spec** | La remise en état doit moderniser l'interface, corriger les libellés erronés et fiabiliser le calcul de la date limite. |
| **Symfony** | Non applicable : module absent. |
| **Legacy** | Le formulaire s'appuie sur `$('.date').live(...)`, API jQuery dépréciée (`prevarisc/application/views/scripts/statistiques/index.phtml:124-126`). Le libellé colonne contient la coquille `Prevenstionniste` (`prevarisc/application/controllers/StatistiquesController.php:226-233`). Le calcul de date limite ne traite que quelques cas simples et retourne sinon un lien de correction, sans gestion métier plus robuste (`prevarisc/application/controllers/StatistiquesController.php:239-260`). |
| **Impact** | **Moyen** — l'UX et la qualité des données restent fragiles même côté legacy. |
| **Fichiers concernés** | `prevarisc/application/views/scripts/statistiques/index.phtml`, `prevarisc/application/controllers/StatistiquesController.php` |

---

## Comportements alignés (confirmés)

| Règles / sections | Sujet | Constat |
|-------------------|-------|---------|
| § 3 | Catalogue des 4 extractions | ✅ Le legacy expose bien 4 extractions distinctes via `StatistiquesController::$liste` et l'écran d'accueil (`prevarisc/application/controllers/StatistiquesController.php:9-15`, `29-34`, `prevarisc/application/views/scripts/statistiques/index.phtml:20-33`). |
| RG-STA-001 / 003 / 004 / 006 / 014 | Filtres principaux | ✅ Le legacy applique le genre bâtiment, le statut ouvert, la périodicité > 0, l'avis défavorable et le filtre commune (`prevarisc/application/models/DbTable/Statistiques.php:52-62`, `133-148`, `155-177`, `182-186`). |
| § 4.1 / § 4.2 / RG-STA-018 / RG-STA-019 | Restitution HTML + export XML | ✅ Le legacy produit un résumé, le total, la date d'exécution et un export XML avec métadonnées (`prevarisc/application/views/scripts/statistiques/extraction.phtml:1-36`, `prevarisc/application/views/scripts/statistiques/extraction.xml.phtml:1-44`). |
| RG-STA-012 / RG-STA-013 | Liens d'action sur l'extraction 4 | ✅ Le legacy affiche bien des liens de rattrapage quand visite, préventionniste ou périodicité manquent (`prevarisc/application/controllers/StatistiquesController.php:239-260`). |

---

## Recommandations

1. **Ne pas considérer le legacy comme base de port direct** : le code est explicitement non fonctionnel et présente des défauts structurels. Une réécriture fonctionnelle sécurisée paraît préférable à une migration mécanique.
2. **Décider explicitement du sort du module** : soit le planifier comme chantier spécifique de remise en état, soit l'exclure clairement du périmètre de migration tant qu'aucun besoin métier confirmé n'existe.
3. **Si migration décidée** : repartir d'une implémentation Symfony neuve avec contrôle d'accès, formulaires validés, requêtes paramétrées, pagination systématique et tests métier sur les 4 extractions.
4. **Faire valider l'extraction 4 avec le métier** : le legacy semble déjà dévier de la spec sur la période filtrée ; la règle de calcul doit être requalifiée avant développement.
