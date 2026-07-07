# Rapport d'écarts — MOD-19 STATISTIQUES

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-19-STATISTIQUES.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend
> **Statut final** : 🟢 **FERMÉ - MODULE NON APPLICABLE**
> **Décision** : Module supprimé car inutilisé, présentant de nombreuses erreurs et une UX très pauvre. Tous les écarts sont fermés sans migration.
> **Date de fermeture** : 2026-07-07

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 19 (RG-STA-001 à RG-STA-019) |
| Écarts spec ↔ Symfony | 6 écarts (tous fermés - module non applicable) |
| Écarts spec ↔ Legacy | 6 écarts (tous fermés - module non applicable) |
| Écarts Symfony ↔ Legacy | 1 écart (fermé - module non applicable) |
| Impact critique | N/A - module supprimé |
| **Décision** | **🟢 FERMÉ** - Le module MOD-19 Statistiques n'est pas migré car jugé non applicable : module non utilisé en production, contenant de nombreuses erreurs et présentant une UX très pauvre. Les 6 écarts identifiés restent documentés à titre historique mais ne donnent lieu à aucune action de développement. |

---

## Écarts détaillés

### ⚠️ Statut global : 🟢 Module fermé - Non applicable à la migration

Tous les 6 écarts identifiés ci-dessous ne font l'objet d'aucune action car le module MOD-19 Statistiques **n'est pas applicable** à la migration Symfony :
- Le module est inutilisé en production
- Son code legacy est explicitement marqué comme « non fonctionnel »
- Il contient de nombreuses erreurs structurelles et présente une UX très pauvre
- **Décision** : fermeture sans migration, documentation historique conservée uniquement

---

### Écart 1 — Le module est totalement absent côté Symfony (🟢 FERMÉ)

| | Détail |
|---|--------|
| **Statut** | 🟢 Fermé — Non applicable, module non migré |
| **Règles** | RG-STA-001 à RG-STA-019, § 3, § 4 |
| **Spec** | Le module doit proposer un écran d'accueil, 4 extractions, des paramètres dynamiques JSON, un affichage HTML et un export XML. |
| **Symfony** | Aucun contrôleur, template, route ou entrée de manifest relatifs aux statistiques n'a été trouvé. Module non implémenté. |
| **Legacy** | Le module existe mais est explicitement marqué « non fonctionnel et non utilisé actuellement. A ne pas migrer. » |
| **Décision** | Aucune action — le module n'est pas applicable à la migration. |

---

### Écart 2 — Legacy non fiable + extraction 4 avec filtre incorrect (🟢 FERMÉ)

| | Détail |
|---|--------|
| **Statut** | 🟢 Fermé — Non applicable |
| **Règles** | RG-STA-008, RG-STA-009, § 3.4, § 6 |
| **Spec** | L'extraction 4 doit lister les ERP avec visites périodiques programmées. |
| **Problème legacy** | Code non fonctionnel + filtre incorrect sur `DATE_ETABLISSEMENTINFORMATIONS` au lieu de date visite. |
| **Décision** | Aucune action — ce module ne sera pas migré. |

---

### Écart 3 — Aucun contrôle d'accès métier dans le legacy (🟢 FERMÉ)

| | Détail |
|---|--------|
| **Statut** | 🟢 Fermé — Non applicable |
| **Règles** | § 2, § 6.1, § 6.2 |
| **Spec** | La spec demande un contrôle d'accès basé sur les rôles. |
| **Constat** | Le legacy ne contient aucune vérification de privilège ni d'ACL. |
| **Décision** | Aucune action — pas de migration. |

---

### Écart 4 — Dates concaténées dans le SQL sans validation (🟢 FERMÉ)

| | Détail |
|---|--------|
| **Statut** | 🟢 Fermé — Non applicable |
| **Règles** | RG-STA-002, RG-STA-007, RG-STA-016, § 6.1 point 3 |
| **Spec** | Requêtes paramétrées obligatoires. |
| **Constat** | Legacy utilise concaténation directe dans `Zend_Db_Expr`. |
| **Décision** | Aucune action — pas de migration. |

---

### Écart 5 — Aucune pagination prévisionnelle (🟢 FERMÉ)

| | Détail |
|---|--------|
| **Statut** | 🟢 Fermé — Non applicable |
| **Règles** | § 4.1, § 6.1 point 4, § 6.2 point 3 |
| **Spec** | La spec identifie l'absence de pagination comme problème à corriger. |
| **Constat** | Legacy affiche tous résultats sans limite. |
| **Décision** | Aucune action — pas de migration. |

---

### Écart 6 — Interface legacy avec fragilités fonctionnelles (🟢 FERMÉ)

| | Détail |
|---|--------|
| **Statut** | 🟢 Fermé — Non applicable |
| **Règles** | RG-STA-010 à RG-STA-017, § 6.1 points 5 à 7 |
| **Spec** | Interface doit être modernisée, libellés corrigés, date limite fiabilisée. |
| **Constat** | API jQuery dépréciée, libellé erroné « Prevenstionniste », logique fragile. |
| **Décision** | Aucune action — pas de migration. |

---

## Recommandations

**Décision de fermeture (2026-07-07)** : Le module MOD-19 Statistiques n'est pas applicable à la migration Symfony. Raisons :

1. **Non utilisé en production** : Le module est inutilisé dans le contexte opérationnel actuel.
2. **Code legacy non fiable** : Le contrôleur est explicitement marqué comme « non fonctionnel et non utilisé » (en-tête du contrôleur legacy).
3. **Nombreuses erreurs structurelles** :
   - Absence totale de contrôle d'accès métier
   - Dates concaténées dans le SQL sans validation ni préparation
   - Pas de pagination malgré les volumes potentiels
   - Libellés erronés et UX fragile
4. **UX très pauvre** : Interface obsolète, API jQuery dépréciée, gestion d'erreurs incomplète.

**Conséquence** : 
- ✅ Tous les écarts MOD-19 sont fermés sans migration.
- ✅ Le module reste documenté à titre historique mais n'est pas intégré à Symfony.
- 🔓 Si un besoin métier future survient, une réécriture Symfony complète (contrôle d'accès, requêtes paramétrées, pagination, tests) sera requise plutôt qu'une migration du legacy.
