# Rapport d'écarts — MOD-12 TABLEAU DE BORD

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-12-TABLEAU-DE-BORD.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 23 (RG-TDB-001 à RG-TDB-023) |
| Écarts spec ↔ Symfony | 0 |
| Écarts spec ↔ Legacy | 0 |
| Écarts Symfony ↔ Legacy | 0 |
| Niveau de confiance | ✅ Excellent |

---

## Écarts détaillés

### Écart 1 — La suppression d'un message est limitée à son auteur dans Symfony ✅ VALIDÉ

| | Détail |
|---|--------|
| **Règles** | RG-TDB-015 |
| **Spec** | La suppression d'un message est immédiate, sans confirmation, et **seul l'auteur du message peut le supprimer**. |
| **Symfony** | `IndexController::deleteMessage()` refuse explicitement la suppression si le message appartient à un autre utilisateur (`AccessDeniedException` quand `news->getUtilisateur()->getId() !== currentUser->getId()`). |
| **Legacy** | `deleteMessageAction()` supprime directement le message demandé, sans contrôle supplémentaire sur l'auteur. |
| **Impact** | **Résolu** — La spec a été mise à jour (2026-07-02) pour refléter l'amélioration de sécurité implémentée en Symfony. C'est une amélioration intentionnelle qui renforce la sécurité du système. |
| **Fichiers concernés** | `docs/specs/MOD-12-TABLEAU-DE-BORD.md:270`, `prevarisc-migration/src/Controller/IndexController.php:239-247` |

**Retour utilisateur** : Écart de migration/spec accepté. Il s'agit d'une amélioration de sécurité qui aurait dû être présente lors de l'implémentation de la fonctionnalité et qui était référencée comme bug dans un ancien ticket sur notre outil de ticketing. Modifier la spec pour correspondre à ce comportement.

---

### Écart 2 — L'indicateur de connectivité Plat'AU n'affiche plus l'état de chargement ni le bandeau embarqué attendu ✅ RÉSOLU

| | Détail |
|---|--------|
| **Règles** | RG-TDB-016 / RG-TDB-017, §6.1 / §6.2 |
| **Spec** | Pendant la vérification, le message « Vérification de la connexion à Plat'AU » doit être affiché avec un indicateur de chargement, puis remplacé par un message embarqué de succès/erreur. |
| **Symfony** | Le dashboard lance bien l'appel AJAX au chargement. Un toast temporaire "Vérification de la connexion à Plat'AU" est affiché avant l'appel (avec `enCoursToastId`), puis remplacé par le résultat (succès/erreur) et éventuellement un message de maintenance. |
| **Legacy** | Le template Zend renseigne `#bloc__connexion-platau` avant l'appel (`Vérification... <img ...load.gif>`), puis remplace ce contenu par un bandeau Bootstrap persistant de succès ou d'erreur. |
| **Impact** | **Résolu** — Le comportement UX est maintenant conforme à la spec : toast "Vérification en cours..." puis toast de résultat. |
| **Fichiers concernés** | `prevarisc-migration/templates/index/index.html.twig:121-146` |

**Retour utilisateur** : Écart de migration accepté et implémenté (2026-07-02). Toast "Vérification en cours..." ajouté via le pattern `const enCoursToastId`. 

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-TDB-001 / 018 | Catalogue des blocs, droits et blocs Plat'AU conditionnels | ✅ Aligné : `DashboardBlocConfig` porte tous les blocs attendus, y compris les 3 blocs Plat'AU uniquement si le module est actif. |
| RG-TDB-002 / 003 | Compteurs calculés au chargement, contenu chargé au dépliage | ✅ Aligné : `IndexController::index()` calcule les compteurs, puis `index.html.twig` charge le contenu AJAX seulement à l'ouverture d'un bloc. |
| RG-TDB-004 / 005 / 006 / 007 | Ordre personnalisé, ajout d'un nouveau bloc, autosauvegarde différée, réinitialisation | ✅ Aligné : l'ordre est reconstitué depuis `UtilisateurPreferences`, complété avec les blocs manquants, sauvegardé après 1 seconde d'inactivité, et réinitialisable. |
| RG-TDB-011 / 012 / 013 / 014 / 023 | Fil de messages (groupes, publication, 5 derniers, liens cliquables, horodatage) | ✅ Aligné : `FeedService` et les templates conservent le filtrage par groupe, la diffusion restreignable, la limite à 5 messages et le rendu enrichi. |
| RG-TDB-016 / 017 | Vérification automatique Plat'AU et distinction origine Prevarisc / Plat'AU | ✅ Aligné sur le fond : l'appel automatique existe et distingue bien les erreurs locales des erreurs distantes. |
| RG-TDB-019 à 022 | Blocs métiers dossier/commission/commune | ✅ Aligné sur le périmètre fonctionnel observé : les blocs dashboard attendus et leurs filtres principaux sont présents côté Symfony. |
| Cas particuliers §8 | Instance sans Plat'AU, absence de préférences d'ordre, blocs vides | ✅ Aligné : les conditions de non-affichage et les états vides principaux sont gérés. |

---

## Recommandations

Tous les écarts importants ont été traités. Le module MOD-12 est conforme à la spec.
- **Écart 1** : ✅ Résolu — amélioration de sécurité acceptée et spec mise à jour (2026-07-02)
- **Écart 2** : ✅ Résolu — toast "Vérification en cours..." implémenté (2026-07-02)
