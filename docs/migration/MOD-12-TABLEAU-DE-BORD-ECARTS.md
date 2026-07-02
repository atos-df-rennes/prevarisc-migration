# Rapport d'écarts — MOD-12 TABLEAU DE BORD

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-12-TABLEAU-DE-BORD.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 23 (RG-TDB-001 à RG-TDB-023) |
| Écarts spec ↔ Symfony | 2 |
| Écarts spec ↔ Legacy | 0 |
| Écarts Symfony ↔ Legacy | 2 |
| Niveau de confiance | ✅ Bon |

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

### Écart 2 — L'indicateur de connectivité Plat'AU n'affiche plus l'état de chargement ni le bandeau embarqué attendu

| | Détail |
|---|--------|
| **Règles** | RG-TDB-016 / RG-TDB-017, §6.1 / §6.2 |
| **Spec** | Pendant la vérification, le message « Vérification de la connexion à Plat'AU » doit être affiché avec un indicateur de chargement, puis remplacé par un message embarqué de succès/erreur. |
| **Symfony** | Le dashboard lance bien l'appel AJAX au chargement, mais sans `beforeSend`, sans placeholder dédié dans la page, et restitue uniquement le résultat via des toasts temporaires. |
| **Legacy** | Le template Zend renseigne `#bloc__connexion-platau` avant l'appel (`Vérification... <img ...load.gif>`), puis remplace ce contenu par un bandeau Bootstrap persistant de succès ou d'erreur. |
| **Impact** | **Moyen** — la vérification existe, mais l'UX décrite dans la spec n'est pas reproduite : absence d'état intermédiaire visible et de zone dédiée en haut du tableau de bord. |
| **Fichiers concernés** | `docs/specs/MOD-12-TABLEAU-DE-BORD.md:226-248`, `prevarisc-migration/templates/index/index.html.twig:121-141`, `prevarisc/application/views/scripts/index/index.phtml:132-170` |

**Retour utilisateur** : Écart de migration partiellement confirmé. Le remplacement du bandeau par des toasts est intentionnel pour améliorer l'UI/UX. Cependant il manque en effet un toast temporaire "Vérification en cours..." -> ajouter ce toast via le pattern `const enCoursToastId`. 

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

1. **Priorité moyenne** : réintroduire une zone dédiée de statut Plat'AU sur le dashboard avec message de chargement puis bandeau persistant de résultat.
2. **Priorité moyenne** : conserver les toasts si souhaité, mais en complément du bandeau embarqué afin de rester conforme à la spec et au legacy.
