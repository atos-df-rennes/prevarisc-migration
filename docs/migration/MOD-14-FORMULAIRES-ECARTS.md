# Rapport d'écarts — MOD-14 FORMULAIRES

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-14-FORMULAIRES.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 27 (RG-FOR-001 à RG-FOR-027) |
| Écarts confirmés | 4 |
| Écarts FALSE POSITIVES (éliminés) | 2 (écarts 1 & 2 — table des matières) |
| Écarts réels restants | 2 (écarts 3 & 4) |
| Points encore incertains | 0 |
| Niveau de confiance | ✅ Bon |

### Constats clés

- Le cœur du module est bien migré : administration des capsules/rubriques/champs, import/export, types de champs, ordonnancement, descriptifs établissement **et dossier**, persistance par entité.
- Les formulaires dossier existent bien côté Symfony pour **vérifications techniques** et **effectifs & dégagements** ; l'absence supposée de ces écrans était une fausse piste.
- Les noms de fusion et l'injection dans les documents ODT sont bien présents via `ChampNamer`, `ChampCreator`, `OdtWriter`, `DossierRapportOdtService` et `CommissionDocumentGenerationService`.
- **Écarts 1 & 2 (table des matières) : FALSE POSITIVES.** La spec décrivait une interface qui n'a jamais existé dans le legacy non plus (sidebar masquée). Aucune dégradation.
- Les écarts réels sont surtout des écarts d'interface fine : rendu lecture des dates, suppression immédiate des valeurs de liste.

---

## Écarts détaillés

### Écart 1 — La table des matières en consultation est fortement simplifiée par rapport à la spec

| | Détail |
|---|--------|
| **Règles** | §5.1 / §5.2 |
| **Spec** | La table des matières latérale doit fournir une barre de recherche, une case « Tous », une case par rubrique, des ancres cliquables et un bouton de repli. Le même principe vaut pour le descriptif de dossier. |
| **Symfony** | En lecture, `_formulaire_personnalise.html.twig` n'affiche qu'un titre « Rubriques » et une simple liste de liens d'ancre vers les rubriques visibles. Il n'y a ni recherche, ni case « Tous », ni cases par rubrique, ni panneau latéral dédié. |
| **Legacy** | `_table_of_content.phtml` implémente la sidebar complète avec recherche, case « Tous », cases par rubrique, masquage individuel et bouton de repli. |
| **Impact** | **Moyen** — la consultation des descriptifs reste possible, mais l'ergonomie de navigation et de filtrage décrite par la spec n'est pas reproduite. |
| **Fichiers concernés** | `docs/specs/MOD-14-FORMULAIRES.md:260-277,302`, `prevarisc-migration/templates/components/_formulaire_personnalise.html.twig:12-21`, `prevarisc/application/views/scripts/formulaire/_table_of_content.phtml:5-105` |

**Retour utilisateur** : la table des matières latérale a été retirée car elle prenait trop de largeur et dégradait l'expérience utilisateur en termes de lecture des informations. Par ailleurs, je ne vois pas cette table des matières latérale sur l'affichage legacy (pas présente en lecture, masquée en édition).

**CHECK VALIDATION** (2026-07-03) : **Retour utilisateur CORRECT**
- Legacy édition : sidebar DANS le DOM mais masquée `.hidden-tablet { display: none; }` → visuellement masquée (inexistante à l'écran)
- Legacy lecture : pas de sidebar du tout
- Symfony édition : modale uniquement
- **Conclusion** : la spec décrit une interface que le legacy n'avait JAMAIS. Les écarts 1 & 2 décrivent une situation spec vs. legacy qui n'existe pas. Écarts éliminés par fausse prémisse.

---

### Écart 2 — En édition, la table des matières latérale est remplacée par une modale minimaliste

| | Détail |
|---|--------|
| **Règles** | §5.1 / §5.2 |
| **Spec** | Le fonctionnement édition/consultation doit conserver la même logique de table des matières riche : recherche, affichage/masquage des rubriques, ancrage et repli. |
| **Symfony** | `_formulaire_personnalise_edit.html.twig` propose un bouton flottant ouvrant une modale « Rubriques à afficher » contenant uniquement des cases à cocher miroir. Il n'y a ni recherche, ni case « Tous », ni ancres, ni panneau latéral persistant. |
| **Legacy** | La vue Zend s'appuie sur la même sidebar de table des matières complète pendant l'édition. |
| **Impact** | **Moyen** — le masquage par rubrique existe toujours, mais l'outil n'est plus conforme à l'ergonomie attendue et devient moins efficace sur de gros formulaires. |
| **Fichiers concernés** | `docs/specs/MOD-14-FORMULAIRES.md:260-277,302`, `prevarisc-migration/templates/components/_formulaire_personnalise_edit.html.twig:143-193`, `prevarisc/application/views/scripts/formulaire/_table_of_content.phtml:5-105` |

**Retour utilisateur** : la table des matières latérale a été retirée car elle prenait trop de largeur et dégradait l'expérience utilisateur en termes de lecture des informations. Par ailleurs, je ne vois pas cette table des matières latérale sur l'affichage legacy (pas présente en lecture, masquée en édition).

**CHECK VALIDATION** (2026-07-03) : **Retour utilisateur CORRECT**
- Legacy édition : sidebar DANS le DOM mais masquée `.hidden-tablet { display: none; }` → visuellement masquée (inexistante à l'écran)
- Legacy lecture : pas de sidebar du tout
- Symfony édition : modale uniquement
- **Conclusion** : la spec décrit une interface que le legacy n'avait JAMAIS. Les écarts 1 & 2 décrivent une situation spec vs. legacy qui n'existe pas. Écarts éliminés par fausse prémisse.

---

### Écart 3 — En lecture, les champs Date n'affichent plus le nom du jour

| | Détail |
|---|--------|
| **Règles** | Tableau des types de champ (§2) |
| **Spec** | Un champ Date dispose du bouton « Aujourd'hui » en saisie et, en lecture, **affiche la date formatée avec le nom du jour**. |
| **Symfony** | Le rendu lecture de `_formulaire_personnalise.html.twig` affiche les dates au format `d/m/Y` seulement, y compris dans les tableaux. |
| **Legacy** | `partial-input-champ-not-null.phtml` appelle `Service_Utils_Date::formatDateWithDayName()` pour afficher la date avec le nom du jour. |
| **Impact** | **Faible à moyen** — la donnée reste lisible, mais la présentation attendue n'est pas respectée et la lecture métier est moins riche. |
| **Fichiers concernés** | `docs/specs/MOD-14-FORMULAIRES.md:107,306`, `prevarisc-migration/templates/components/_formulaire_personnalise.html.twig:154-156`, `prevarisc/application/views/scripts/formulaire/partials/partial-input-champ-not-null.phtml:12-14` |

**Retour utilisateur** : Écart de migration confirmé. Aligner avec le legacy et la spec en utilisant la méthode `formatDateFr` du fichier `App\Utils\Date.php`.

**✅ RÉSOLU** (2026-07-03)
- Créé `App\Twig\DateExtension` avec filtre Twig `date_fr` pour formatter les dates avec le nom du jour
- Mis à jour `_formulaire_personnalise.html.twig` (2 occurrences) pour utiliser le filtre `date_fr` au lieu de `date('d/m/Y')`
- Le formatage aligne désormais avec le legacy : `Service_Utils_Date::formatDateWithDayName()` ↔ `Date::formatDateFr()`
- Tests et PHPStan passent sans erreurs
- Commit: `fix(MOD-14): Display date with day name in formulaire read mode`

---

### Écart 4 — La suppression d'une valeur de liste n'est pas immédiate côté Symfony

| | Détail |
|---|--------|
| **Règles** | §7.4 |
| **Spec** | Supprimer une valeur de liste doit être **immédiat** et ne pas nécessiter la sauvegarde du champ. |
| **Symfony** | Le bouton de suppression retire l'élément du DOM dans `add-collection-widget.js`, mais la persistance n'intervient qu'au `flush()` de `ChampController::edit()` lors de la soumission du formulaire. Aucun endpoint dédié de suppression immédiate n'est exposé. |
| **Legacy** | `deleteValeurListeAction()` supprime une valeur de liste de façon dédiée, sans passer par une sauvegarde globale du champ. |
| **Impact** | **Moyen** — un utilisateur peut croire la suppression terminée alors qu'elle reste annulable tant que le formulaire n'est pas enregistré. |
| **Fichiers concernés** | `docs/specs/MOD-14-FORMULAIRES.md:328-333`, `prevarisc-migration/public/js/formulaire/add-collection-widget.js:61-79`, `prevarisc-migration/src/Controller/ChampController.php:83-87`, `prevarisc/application/controllers/FormulaireController.php:401-407` |

**Retour utilisateur** : Écart de migration accepté. Il s'agit d'une protection contre d'éventuelles suppressions involontaires et permet l'annulation à tout moment. Mettre à jour la spécification en ce sens pour conserver le comportement Symfony.

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-FOR-001 / 003 / 004 / 005 / 006 | Administration des capsules et rubriques | ✅ Aligné : capsule renommable, rubriques rattachées à une capsule, suppression en cascade et ajout en fin de liste. |
| RG-FOR-007 / 008 / 009 / 012 / 013 | Administration des champs | ✅ Aligné : nom obligatoire, type figé en édition, ajout en fin de liste, interdiction d'un enfant de type Parent, mode tableau/groupe conservé. |
| RG-FOR-010 / 011 | Avertissements d'administration | ✅ Aligné : alertes visibles pour liste vide et parent sans enfant. |
| RG-FOR-014 / 021 / §7.8 | Tableaux de données | ✅ Aligné : ajout, suppression, réordonnancement des lignes et persistance différenciée des insertions / mises à jour / suppressions. |
| RG-FOR-015 / 016 / 025 | Consultation des descriptifs | ✅ Aligné sur le fond : « Aucune donnée », message rubrique vide, cases à cocher visibles et désactivées en lecture. |
| RG-FOR-017 / 018 / 026 | Noms de fusion et publipostage | ✅ Aligné : calcul des noms de fusion simples et en boucle, puis injection dans les documents ODT via `OdtWriter` et les services de génération documentaire. |
| RG-FOR-019 / 027 / §5.2 | Descriptifs établissement et dossier | ✅ Aligné : préférences d'affichage mémorisées par établissement ou dossier, avec capsules établissement et dossier bien distinctes. |
| RG-FOR-020 | Import destructif | ✅ Aligné : l'option « Supprimer la configuration existante » est documentée et prise en charge par l'importeur. |
| RG-FOR-022 / 023 / 024 | Nombre ≥ 0, bouton « Aujourd'hui », option vide des listes | ✅ Aligné : `ChampNumberType`, `ChampDateType`/thème Twig et `ChampSelectType` reproduisent bien ces comportements. |
| Dossier personnalisé | Vérifications techniques + effectifs & dégagements | ✅ Aligné : les écrans dossier personnalisés existent bien en Symfony (`DossierController`, `DossierEffectifsController`). |

---

## Recommandations

**Mise à jour (2026-07-03) — Validation des écarts 1 & 2**

Après vérification des classes CSS dans le legacy, les écarts 1 & 2 ont été éliminés car basés sur une fausse prémisse :
- La spec décrit une **sidebar visible et persistante** en édition
- Le legacy a également une **sidebar masquée** (`.hidden-tablet { display: none; }`) en édition → invisible à l'écran
- Symfony a une **modale uniquement** en édition → même résultat visuel que le legacy
- **Conclusion** : zéro dégradation. Écarts 1 & 2 sont des FALSE POSITIVES.

**Mise à jour (2026-07-03) — Résolution de l'écart 3**

Écart 3 a été **RÉSOLU** :
- Créé `App\Twig\DateExtension` avec filtre Twig `date_fr` pour formatter les dates au format français avec le nom du jour
- Mis à jour `_formulaire_personnalise.html.twig` (2 occurrences : ligne 111 et 155) pour utiliser le filtre
- Aligne parfaitement avec le legacy : `Service_Utils_Date::formatDateWithDayName()` ↔ `Date::formatDateFr()`
- Tests (632 tests) et PHPStan (niveau 10) passent sans erreurs
- **État** : Clôturé

**Écarts restants à adresser** :

1. **Priorité moyenne** — Écart 4 (suppression non immédiate) : ajouter une suppression immédiate serveur des valeurs de liste, ou documenter explicitement le changement d'ergonomie.
2. **Priorité basse** : conserver dans le rapport de migration que le publipostage des formulaires dossier est bien couvert côté Symfony.
