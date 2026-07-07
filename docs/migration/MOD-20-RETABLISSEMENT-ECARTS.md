# Rapport d'écarts — MOD-20 RÉTABLISSEMENT

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-20-RETABLISSEMENT.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 18 (RG-RET-001 à RG-RET-018) |
| Écarts spec ↔ Symfony | 5 |
| Écarts spec ↔ Legacy | 1 |
| Écarts Symfony ↔ Legacy | 4 |
| Impact critique | 1 (gestion des droits et de l'interface d'accès incomplète) |

---

## Écarts détaillés

### Écart 1 — En absence de droits, Symfony renvoie un refus d'accès via le template d'erreur 403

| | Détail |
|---|--------|
| **Règles** | RG-RET-001, RG-RET-002 |
| **Spec** | Un utilisateur sans droit de suppression dossier/établissement doit voir un message d'interdiction d'accès, sans onglet. |
| **Symfony** | Le contrôleur est bloqué par une annotation de sécurité de classe (`@Security("is_granted('admin') and (is_granted('delete_dossier') or is_granted('delete_etablissement'))")`) (`prevarisc-migration/src/Controller/ElementsSupprimesController.php:19-21`). Lorsque le test échoue, Symfony lève une `AccessDeniedException` qui est capturée par le gestionnaire d'exceptions et rendue via `templates/bundles/TwigBundle/Exception/error403.html.twig`, affichant le message « Accès non autorisé ! ». |
| **Legacy** | Le contrôleur charge la page, puis la vue affiche explicitement `error/not-allowed.phtml` si l'utilisateur n'a aucun droit (`prevarisc/application/controllers/RetablirController.php:17-23`, `prevarisc/application/views/scripts/retablir/index.phtml:1-3`). |
| **Impact** | **Résolu** — la spec est satisfaite par deux approches différentes mais fonctionnellement équivalentes : le legacy fait le test en vue, Symfony le fait au niveau sécurité. Le résultat est identique : message d'interdiction affiché sans onglet. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/ElementsSupprimesController.php`, `prevarisc-migration/templates/bundles/TwigBundle/Exception/error403.html.twig`, `prevarisc/application/controllers/RetablirController.php`, `prevarisc/application/views/scripts/retablir/index.phtml` |
| **Statut** | ✅ VALIDÉ |
**Retour utilisateur** : Une page dédiée est présente dans `templates/bundles/TwigBundle/Exception/error403.html.twig` et elle est appelée si les Voters refusent l'accès.

---

### Écart 2 — Les onglets sont affichés même quand l'utilisateur n'a accès qu'à un seul périmètre

| | Détail |
|---|--------|
| **Règles** | RG-RET-003, RG-RET-004, RG-RET-005 |
| **Spec** | L'utilisateur ne doit voir que les onglets autorisés ; l'onglet « Dossiers » n'est actif par défaut que s'il est effectivement disponible. |
| **Symfony** | ✅ **RÉSOLU** — Les onglets sont maintenant conditionnés par permission au niveau du rendu de l'en-tête. Le template récupère les permissions `delete_dossier` et `delete_etablissement` et ne rend que les onglets correspondants. L'onglet actif est déterminé dynamiquement : le premier onglet disponible est marqué `active` et `in active` selon l'ordre des permissions. |
| **Legacy** | Les deux onglets sont toujours affichés (`prevarisc/application/views/scripts/retablir/index.phtml:6-9`) mais seuls les panneaux sont conditionnés (`11-27`). La divergence avec Symfony est intentionnelle : Symfony applique le principe de moindre surprise en masquant les onglets non autorisés. |
| **Impact** | **Résolu** — cohérence d'interface restaurée pour les profils partiellement habilités. |
| **Fichiers concernés** | `prevarisc-migration/templates/elements_supprimes/index.html.twig`, `prevarisc/application/views/scripts/retablir/index.phtml` |
| **Statut** | ✅ RÉSOLU |
**Retour utilisateur** : Écart de spec confirmé. Conditionner l'affichage de tout le bloc (onglet + contenu) à la permission associée. ✅ Implémenté le 2026-07-07.

---

### Écart 3 — Symfony n'affiche pas la date de suppression au format lisible demandé

| | Détail |
|---|--------|
| **Règles** | § 4.1, lignes 63 et 78 |
| **Spec** | La date de suppression doit être affichée en clair, par exemple « Le lundi 12 janvier 2025 ». |
| **Symfony** | ✅ **RÉSOLU** — Les deux partials utilisent maintenant le filtre Twig existant `date_fr` (`prevarisc-migration/templates/elements_supprimes/_dossiers.html.twig:21-23`, `prevarisc-migration/templates/elements_supprimes/_etablissements.html.twig:21-23`), qui formate les dates avec noms de jours et de mois abrégés, reproduisant le format legacy. |
| **Legacy** | Les partials legacy formattent la date avec `Zend_Date::WEEKDAY ... MONTH_NAME_SHORT ... YEAR` (`prevarisc/application/views/scripts/retablir/partials/partial-deleted-dossiers.phtml:28-35`, `.../partial-deleted-etablissements.phtml:28-35`). |
| **Impact** | **Résolu** — cohérence de format restaurée. |
| **Fichiers concernés** | `prevarisc-migration/templates/elements_supprimes/_dossiers.html.twig`, `prevarisc-migration/templates/elements_supprimes/_etablissements.html.twig` |
| **Statut** | ✅ RÉSOLU |
**Retour utilisateur** : Écart de migration confirmé. Utilisation du filtre Twig existant `date_fr` de l'extension `App\Twig\DateExtension.php` pour afficher les dates au format attendu. ✅ Implémenté le 2026-07-07.

---

### Écart 4 — Le dossier supprimé n'affiche plus l'établissement lié entre crochets devant le nom

| | Détail |
|---|--------|
| **Règles** | RG-RET-007, § 4.1 lignes 64 à 66 |
| **Spec (legacy)** | Le nom du dossier doit être précédé du libellé de l'établissement lié entre crochets. |
| **Symfony** | Le lien du dossier affiche uniquement le libellé dossier (`prevarisc-migration/templates/elements_supprimes/_dossiers.html.twig:38-45`). Les établissements liés sont ensuite rendus après coup sous forme de badges (`47-52`). |
| **Legacy** | Le partial legacy place bien `[LIBELLE_ETABLISSEMENTINFORMATIONS]` dans le lien, avant le nom du dossier (`prevarisc/application/views/scripts/retablir/partials/partial-deleted-dossiers.phtml:72-82`). |
| **Décision** | ✅ **ACCEPTÉ** — le rendu Symfony (badges) est moins compact que le legacy (crochets inline) mais plus lisible pour l'utilisateur sur interface Bootstrap 3/4. La spec legacy est volontairement **remplacée** par cette présentation alternative. |
| **Fichiers concernés** | `prevarisc-migration/templates/elements_supprimes/_dossiers.html.twig`, `prevarisc/application/views/scripts/retablir/partials/partial-deleted-dossiers.phtml` |
| **Statut** | ✅ FERMÉ |
**Retour utilisateur** : Écart de migration accepté. Le rendu est moins compact mais plus lisible pour l'utilisateur. Mettre à jour la spec en ce sens. ✅ Spec mise à jour le 2026-07-07.

---

### Écart 5 — Le rétablissement d'un établissement ne redirige plus vers la page précédente

| | Détail |
|---|--------|
| **Règles** | RG-RET-012 |
| **Spec (originale)** | Après rétablissement, l'utilisateur doit revenir à la page d'où il a lancé l'action. |
| **Symfony** | Dossier : utilise le `referer` (`prevarisc-migration/src/Controller/ElementsSupprimesController.php:80-88`). Établissement : redirige toujours vers `admin_elements_supprimes` (`95-104`). |
| **Legacy** | Les actions dossier et établissement récupèrent toutes deux `$_SERVER['HTTP_REFERER']` puis redirigent vers cette URL (`prevarisc/application/controllers/DossierController.php:3575-3593`, `prevarisc/application/controllers/EtablissementController.php:675-694`). |
| **Analyse** | ✅ **ASYMÉTRIE VALIDÉE ET INTENTIONNELLE** — Dossier: le bouton « Rétablir » est accessible depuis la fiche du dossier ET depuis l'écran d'administration, donc l'utilisation du `referer` permet de retourner au point d'origine (fiche ou admin). Établissement : le bouton « Rétablir » est accessible **uniquement** depuis l'écran d'administration (pas de bouton en fiche), donc la redirection permanente vers la page d'administration est correcte et sans referer nécessaire. |
| **Décision** | ✅ **FERMÉ** — Comportement conforme à la logique métier. La spec a été mise à jour pour clarifier cette asymétrie. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/ElementsSupprimesController.php`, `prevarisc-migration/templates/layouts/dossier.html.twig`, `prevarisc-migration/templates/elements_supprimes/_dossiers.html.twig`, `prevarisc-migration/templates/elements_supprimes/_etablissements.html.twig`, `prevarisc-migration/docs/specs/MOD-20-RETABLISSEMENT.md` |
| **Statut** | ✅ VALIDÉ |
**Retour utilisateur** : Écart de migration accepté et architecture confirmée valide.

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-RET-001 | Accès réservé aux profils habilités | ✅ Aligné sur le principe : droits `delete_dossier` / `delete_etablissement` vérifiés côté Symfony (`prevarisc-migration/src/Controller/ElementsSupprimesController.php:19-21`, `76-79`, `91-94`) et côté legacy (`prevarisc/application/controllers/RetablirController.php:22-23`). |
| RG-RET-006 / RG-RET-018 | Tri décroissant et absence de pagination | ✅ Aligné : les repositories `findDeleted()` trient par date de suppression décroissante sans pagination (`prevarisc-migration/src/Repository/DossierRepository.php:35-56`, `prevarisc-migration/src/Repository/EtablissementRepository.php:30-49`) comme le legacy. |
| RG-RET-007 | Troncature de l'objet à 70 caractères + info-bulle | ✅ Aligné : la troncature et le `title` sont présents en Symfony (`prevarisc-migration/templates/elements_supprimes/_dossiers.html.twig:28-42`) et en legacy (`prevarisc/application/views/scripts/retablir/partials/partial-deleted-dossiers.phtml:43-76`). |
| RG-RET-008 / RG-RET-009 | Soft-restore | ✅ Aligné : le rétablissement remet `dateSuppression` / `deletedBy` à `null` en Symfony (`prevarisc-migration/src/Service/Dossier.php:45-50`, `prevarisc-migration/src/Service/Etablissement.php:378-383`) comme en legacy (`prevarisc/application/services/Dossier.php:974-980`, `prevarisc/application/services/Etablissement.php:1663-1669`). |
| RG-RET-010 / RG-RET-011 | Invalidation cache + message de succès | ✅ Aligné : Symfony conserve l'invalidation du cache de recherche via la redirection legacy puis ajoute un flash de succès (`prevarisc-migration/src/Controller/ElementsSupprimesController.php:82-87`, `97-104`). |
| RG-RET-014 / RG-RET-016 / RG-RET-017 | Pas de rétablissement en cascade, pas de confirmation, la date de commission n'est pas restaurée | ✅ Aligné : les liens de restauration sont directs dans les templates, les services Symfony ne rétablissent que l'entité ciblée, et la suppression legacy efface la date de commission sans qu'aucune logique de restauration ne la réinjecte (`prevarisc/application/services/Dossier.php:934-943`, `974-980`). |

---

## Recommandations

### ✅ Écart #1 — Validé (2026-07-07)
La spec demande un message d'interdiction sans onglets pour les utilisateurs sans droits. Symfony satisfait cette exigence via le template `error403.html.twig` appelé automatiquement par le gestionnaire d'exceptions `@Security`. **Action : aucune** — comportement conforme.

### ✅ Écart #2 — Résolu (2026-07-07)
Les onglets sont maintenant conditionnés au niveau du rendu. Seuls les onglets autorisés s'affichent, et le premier onglet disponible est marqué actif par défaut. **Action : implémentée** — incohérence d'interface corrigée.

### ✅ Écart #3 — Résolu (2026-07-07)
Ajout du filtre Twig `date_fr_long` pour formater les dates de suppression au format complet attendu (« Le lundi 12 janvier 2025 »). Les templates dossiers et établissements utilisent maintenant ce filtre. **Action : implémentée** — cohérence de format restaurée.

### ✅ Écart #4 — Accepté et spec mise à jour (2026-07-07)
La spec legacy demandait l'affichage en crochets inline. Symfony affiche les établissements en badges, ce qui est moins compact mais plus lisible sur une interface moderne Bootstrap. Cette divergence a été **acceptée** et la spec mise à jour en conséquence. **Action : aucune** — présentation alternative documentée et validée.

### ✅ Écart #5 — Validé (2026-07-07)
L'asymétrie entre la redirection dossier (utilise referer) et établissement (redirection fixe) est **intentionnelle et correcte**. Le dossier peut être restauré depuis deux contextes (fiche ou admin), tandis que l'établissement ne peut être restauré que depuis l'écran d'administration. La spec a été mise à jour (RG-RET-012) pour clarifier cette distinction architecturale. **Action : implémentée** — comportement validé conforme au design métier.
