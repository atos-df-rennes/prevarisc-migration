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

|                        | Détail                                                                                                                                                                                                                                                                                                     |
|------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Règles**             | Note 5, § 3.1, § 3.3                                                                                                                                                                                                                                                                                       |
| **Spec**               | Les pages « À propos » et « Aide » exposent un lien vers le guide de contribution ; la note de spec signale que ce lien relatif n'est pas fonctionnel en contexte web.                                                                                                                                     |
| **Symfony**            | ✅ **RÉSOLU** — Les fichiers Markdown ont été mis à jour pour pointer vers une URL GitHub absolue (`https://github.com/atos-df-rennes/prevarisc-migration/blob/main/CONTRIBUTING.md`) dans les pages « À propos » et « Aide » (`prevarisc-migration/docs/about.md`, `prevarisc-migration/docs/support.md`). |
| **Legacy**             | Même lien relatif hérité du legacy, donc même défaut fonctionnel.                                                                                                                                                                                                                                          |
| **Impact**             | **Moyen** — l'utilisateur clique maintenant vers une ressource fonctionnelle et accessible en contexte web.                                                                                                                                                                                                |
| **Fichiers concernés** | `prevarisc-migration/docs/about.md`, `prevarisc-migration/docs/support.md`                                                                                                                                                                                                                                 |
**Retour utilisateur** : Écart résolu en remplaçant les liens relatifs par une URL GitHub absolue. Cette approche garantit une fonctionnalité complète tout en servant toujours la dernière version du fichier sur le dépôt.

---

### Écart 3 — Un document absent ou un `doc` invalide produit une page vide sans retour utilisateur

| | Détail                                                                                                                                                                                                                                                                                                                                                                                    |
|---|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Règles** | Note 4, RG-APR-003, RG-APR-004                                                                                                                                                                                                                                                                                                                                                            |
| **Spec** | Le comportement en cas de fichier source absent ou illisible est noté comme non défini ; il n'existe pas de page d'erreur dédiée.                                                                                                                                                                                                                                                         |
| **Symfony** | ✅ **VALIDÉ** — La route accepte uniquement les 4 documents connus : `about`, `dev`, `support`, `tos` (`prevarisc-migration/src/Constant/AboutDocument.php`). Les valeurs invalides lèvent une exception `NotFoundException` qui produit un 404 HTTP (`prevarisc-migration/src/Controller/AboutController.php:25-27`). Cette approche est plus robuste que le legacy et répond à la note 4 de la spec. |
| **Legacy** | Le legacy faisait un `file_get_contents()` direct sans garde ni message utilisateur (`prevarisc/application/controllers/AboutController.php:13-35`).                                                                                                                                                                                                                                         |
| **Impact** | **Éliminé** — l'application est maintenant robuste ; une URL `/about/xxx` inconnue produit un 404 HTTP convenable.                                                                                                                                                                                                                                                                         |
| **Fichiers concernés** | `prevarisc-migration/src/Constant/AboutDocument.php` (nouveau), `prevarisc-migration/src/Controller/AboutController.php` (corrigé)                                                                                                                                                                                                                                                         |
**Retour utilisateur** : Correction implémentée et validée. Écart résolu par whitelisting des 4 documents + levée d'exception 404.

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

1. **Écart #1** — ✅ **FERMÉ** — Le libellé « Développeurs » a été corrigé dans le contrôleur (voir MOD-22-ECARTS.md).
2. **Écart #2** — ✅ **RÉSOLU** — Les liens de contribution relatifs ont été remplacés par une URL GitHub absolue. L'utilisateur accède maintenant à la ressource de manière fonctionnelle en contexte web (2026-07-08).
3. **Écart #3** — ✅ **VALIDÉ** — Whitelisting des 4 documents + exception 404 pour les valeurs invalides. Route `/about/{doc}` maintenant robuste et en ligne avec la spec (2026-07-08).
