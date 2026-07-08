# Rapport d'écarts — MOD-22 À PROPOS ET DOCUMENTATION

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-22-A-PROPOS.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 10 (RG-APR-001 à RG-APR-010) |
| Écarts spec ↔ Symfony | 3 |
| Écarts spec ↔ Legacy | 2 |
| Écarts Symfony ↔ Legacy | 1 |
| Impact critique | 0 |

---

## Écarts détaillés

### Écart 1 — Le titre « Développeurs » contient une faute dans Symfony

| | Détail |
|---|--------|
| **Règles** | § 3.4, RG-APR-005 |
| **Spec** | La page technique doit être présentée comme page « Développeurs ». |
| **Symfony** | ✅ **RÉSOLU** — Le titre est maintenant corrigé : `À propos / Développeurs` (`prevarisc-migration/src/Controller/AboutController.php:32`). |
| **Legacy** | Le legacy n'ajoutait pas de titre spécifique, il se contentait d'une vue générique `display-text` (`prevarisc/application/controllers/AboutController.php:7-11`). |
| **Impact** | **Faible** — défaut de libellé visible sur la page technique Symfony. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/AboutController.php` |
**Retour utilisateur** : Écart de migration confirmé, aligner avec la spec.

---

### Écart 2 — Les liens de contribution restent relatifs et non fonctionnels dans l'application web

| | Détail |
|---|--------|
| **Règles** | Note 5, § 3.1, § 3.3 |
| **Spec** | Les pages « À propos » et « Aide » exposent un lien vers le guide de contribution ; la note de spec signale que ce lien relatif n'est pas fonctionnel en contexte web. |
| **Symfony** | Les fichiers Markdown pointent toujours vers `../CONTRIBUTING.md` (`prevarisc-migration/docs/about.md:11-17`, `prevarisc-migration/docs/support.md:17-25`). |
| **Legacy** | Même lien relatif hérité du legacy, donc même défaut fonctionnel. |
| **Impact** | **Moyen** — l'utilisateur clique vers une ressource qui n'est pas fournie par l'application. |
| **Fichiers concernés** | `prevarisc-migration/docs/about.md`, `prevarisc-migration/docs/support.md` |
**Retour utilisateur** : Référence vers le fichier en contexte web non fonctionnelle. Quelle suggestion ? Remplacer par un lien absolu vers Github ? Cela ne prend pas en compte les modifications du fichier effectuées au fil des versions et sert toujours la dernière version du fichier.

---

### Écart 3 — Un document absent ou un `doc` invalide produit une page vide sans retour utilisateur

| | Détail |
|---|--------|
| **Règles** | Note 4, RG-APR-003, RG-APR-004 |
| **Spec** | Le comportement en cas de fichier source absent ou illisible est noté comme non défini ; il n'existe pas de page d'erreur dédiée. |
| **Symfony** | La route accepte n'importe quelle valeur `{doc}` (`prevarisc-migration/src/Controller/AboutController.php:21-26`). `Doc::get()` appelle `file_get_contents()` puis retourne une chaîne vide si la lecture échoue (`prevarisc-migration/src/Service/Doc.php:21-38`). Le template affiche ensuite simplement `{{ text|raw }}` (`prevarisc-migration/templates/about/index.html.twig:5-7`). |
| **Legacy** | Le legacy faisait déjà un `file_get_contents()` direct sans garde ni message utilisateur (`prevarisc/application/controllers/AboutController.php:13-35`). |
| **Impact** | **Moyen** — robustesse insuffisante ; une URL `/about/xxx` inconnue débouche sur un écran vide plutôt qu'un 404 métier. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/AboutController.php`, `prevarisc-migration/src/Service/Doc.php`, `prevarisc-migration/templates/about/index.html.twig`, `prevarisc/application/controllers/AboutController.php` |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-APR-001 / RG-APR-002 | Accès aux pages documentaires pour tout utilisateur authentifié, sans rôle spécifique, en lecture seule | ✅ Aligné : toutes les routes applicatives exigent `ROLE_USER` (`prevarisc-migration/config/packages/security.php:58-67`) et `AboutController` ne propose aucune action d'écriture (`prevarisc-migration/src/Controller/AboutController.php:21-47`). |
| RG-APR-003 / RG-APR-004 | Contenu externalisé en Markdown, converti en HTML à l'affichage | ✅ Aligné : `Doc::get()` lit `docs/<page>.md` puis `Markdown::defaultTransform()` est appliqué (`prevarisc-migration/src/Service/Doc.php:21-38`, `prevarisc-migration/src/Controller/AboutController.php:24-26`). |
| RG-APR-005 | Vue unique partagée | ✅ Aligné : les 4 pages utilisent `templates/about/index.html.twig` et le legacy utilisait déjà une vue unique `display-text` (`prevarisc-migration/templates/about/index.html.twig:1-7`, `prevarisc/application/controllers/AboutController.php:7-11`). |
| RG-APR-006 / RG-APR-010 | Liens depuis le footer global + lien vers les notes de versions | ✅ Aligné : le footer Symfony expose Conditions, À propos, Développeurs, Aide et Notes de versions (`prevarisc-migration/templates/_footer.html.twig:1-11`) et il est injecté globalement via `IndexController::footer()` (`prevarisc-migration/templates/base.html.twig:69-71`, `prevarisc-migration/src/Controller/IndexController.php:274-284`). |
| RG-APR-007 / RG-APR-008 / RG-APR-009 | Version, copyright dynamique et liens institutionnels | ✅ Aligné : version + lien de code source dans le footer, année courante calculée dynamiquement, liens SDIS62 / Ministère présents (`prevarisc-migration/templates/_footer.html.twig:1-11`). |
| § 3.4 / Note 2 | Documentation développeurs avec HTML mixé au Markdown | ✅ Aligné : `docs/dev.md` contient des tableaux HTML et le template les rend via `|raw` (`prevarisc-migration/docs/dev.md:1-80`, `prevarisc-migration/templates/about/index.html.twig:5-7`). |

---

## Recommandations

1. **Corriger le libellé `Dévelopeurs`** dans `AboutController`.
2. **Remplacer les liens relatifs de contribution** par une URL GitHub absolue ou par une page interne réellement servie par l'application.
3. **Fiabiliser la route `/about/{doc}`** : whitelister les 4 documents attendus (`about`, `tos`, `support`, `dev`) et renvoyer un 404 ou un message métier clair si le document n'existe pas.
4. **Ajouter une garde dans `Doc::get()`** (`is_file`, `is_readable`) pour éviter les pages vides silencieuses.
