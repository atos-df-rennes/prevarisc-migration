# MOD-15 — Cartographie — Rapport d'écarts

## Synthèse

| Indicateur | Valeur |
|---|---:|
| Règles de gestion analysées | 18 |
| Écarts confirmés | 4 |
| Points à vérifier | 3 |
| Niveau de risque | Moyen |
| Niveau de confiance | Bon |

### Constats clés

- Le périmètre principal est migré : CRUD des couches, aperçu d'administration, ajout IGN, réordonnancement, affichage des cartes sur les fiches établissement et dans la modale d'adresse.
- La priorité d'affichage des fournisseurs en administration reste alignée sur la spec : **IGN > GeoConcept > Google Maps**.
- Les comportements de liste restent cohérents avec le legacy : tri par ordre, mention « couche de base », mention « Ordre non renseigné », suppression sans confirmation.
- Trois écarts fonctionnels ressortent côté Symfony : **format WMS non modifiable à l'ajout IGN**, **contrainte min=1 absente sur l'ordre**, **absence de garde d'accès administrateur explicite**.
- Un bug technique est déjà signalé dans le code sur `autoconf_path` : le contrôleur transmet un booléen au lieu d'un chemin réel pour l'autoconfiguration IGN.

## Écarts détaillés

### Écarts confirmés

| # | Règle | Spec | Symfony | Legacy | Impact | Fichiers |
|---|---|---|---|---|---|---|
| **ÉCART-1** | RG-CAR-018 — format WMS modifiable lors de l'ajout IGN | Pour les couches IGN en **WMS**, l'utilisateur doit pouvoir choisir le format d'image ; pour **WMTS**, le format reste fixe. | Le template d'ajout IGN affiche `{{ couche.format }}` en texte fixe et poste cette valeur en champ caché pour tous les formats. | Le legacy affiche un `<select>` PNG/JPEG pour les couches WMS et un champ caché fixe pour WMTS. | Régression fonctionnelle : impossibilité de choisir PNG/JPEG pour une couche IGN WMS. | `src/Controller/CouchesCartographiquesController.php`, `templates/couches_cartographiques/ajoutIGN.html.twig`, `prevarisc/application/views/scripts/couches-cartographiques/add-couche-ign.phtml` |
| **ÉCART-2** | RG-CAR-008 — ordre d'affichage ≥ 1 et obligatoire | Le réordonnancement doit imposer un entier positif, minimum 1, champ requis. | `OrdreCoucheCartoType` déclare un `IntegerType` sans contrainte `min`, ni validation dédiée. | Le legacy utilisait un `<input type="number" required min="1">`. | Régression de validation : la saisie de `0`, d'une valeur négative ou vide n'est plus empêchée côté formulaire Symfony. | `src/Form/OrdreCoucheCartoType.php`, `prevarisc/application/views/scripts/couches-cartographiques/change-order.phtml` |
| **ÉCART-3** | RG-CAR-017 — accès réservé aux administrateurs | L'écran d'administration des couches doit être réservé au profil administrateur. | Le contrôleur n'a pas de `@IsGranted(...)` et la sécurité globale protège seulement l'accès authentifié (`ROLE_USER`). | Le legacy passait par le layout/menu admin ; la restriction explicite n'était pas portée dans le contrôleur, mais le module restait dans le périmètre administration. | Risque de sécurité : tout utilisateur connecté peut potentiellement accéder à la gestion des couches. | `src/Controller/CouchesCartographiquesController.php`, `config/packages/security.php`, `prevarisc/application/layouts/menu_admin.phtml` |
| **ÉCART-4** | Fichier d'autoconfiguration IGN | Si un fichier d'autoconfiguration IGN local existe, le visualiseur doit utiliser son chemin ; sinon il doit retomber sur le mode distant. | Le contrôleur transmet `file_exists(...)` à `autoconf_path`, donc un booléen, avec un `@todo` explicite indiquant que le chemin est incorrect. | Le legacy calculait et transmettait un chemin de fichier réel. | Bug potentiel sur l'initialisation du viewer IGN : l'autoconfiguration locale peut ne pas être utilisée correctement. | `src/Controller/CouchesCartographiquesController.php`, `prevarisc/application/views/scripts/couches-cartographiques/list.phtml` |

### Points à vérifier

| Règle | Spec | Symfony | Legacy | Impact | Fichiers |
|---|---|---|---|---|---|
| RG-CAR-012 — cache des couches invalidé au CRUD | La liste des couches doit être mise en cache et invalidée à chaque ajout/modification/suppression. | La liste affichée vient directement du repository Doctrine ; le seul cache visible concerne les capacités IGN (`CouchesCarto`) et n'est pas invalidé sur le CRUD. | Le comportement de cache n'est pas visible dans les fichiers legacy inspectés. | Point d'architecture à confirmer : la spec parle d'un cache applicatif qui n'apparaît pas clairement dans Symfony. | `src/Controller/CouchesCartographiquesController.php`, `src/Service/CouchesCarto.php` |
| RG-CAR-013 — plusieurs clés IGN | La configuration legacy supporte plusieurs clés IGN et l'UI prévient que le chargement peut être long. | Le message d'avertissement reste affiché, mais le service Symfony interroge `data.geopf.fr` sans logique visible de multi-clés. | Le legacy reposait explicitement sur des clés IGN, avec avertissement correspondant. | Adaptation probable plutôt qu'une régression, mais la spec n'est plus totalement alignée avec l'implémentation actuelle. | `templates/couches_cartographiques/index.html.twig`, `src/Service/CouchesCarto.php`, `prevarisc/application/controllers/CouchesCartographiquesController.php` |
| §4.2.2 / §7 — avertissement aussi sur la page d'ajout IGN | La page d'ajout IGN doit prévenir que le chargement peut être long. | L'avertissement est présent sur la liste, pas sur l'écran d'ajout IGN lui-même. | Le legacy reposait sur un chargement JS spécifique ; l'avertissement était surtout exposé avant l'ouverture. | Écart UX mineur à confirmer selon le niveau d'exigence attendu sur l'écran d'ajout. | `templates/couches_cartographiques/index.html.twig`, `templates/couches_cartographiques/ajoutIGN.html.twig` |

## Comportements alignés

| Règle | Constat | Symfony | Legacy |
|---|---|---|---|
| RG-CAR-001 | Priorité fournisseur en administration | `if/elseif` IGN → GeoConcept → Google dans la vue d'index | Même priorité dans la vue legacy |
| RG-CAR-003 | Tri des couches par ordre croissant | `findAllOrdered()` du repository | Liste legacy triée de la même manière |
| RG-CAR-004 | Mention « couche de base » | Affichée dans la liste Twig | Affichée dans la liste legacy |
| RG-CAR-005 | Couche masquée / transparente | Champ `transparent` sur les formulaires et l'ajout IGN | Champ `TRANSPARENT_COUCHECARTO` côté legacy |
| RG-CAR-006 | Type limité à WMS / WMTS | `ChoiceType` WMS/WMTS | Sélecteur legacy WMS/WMTS |
| RG-CAR-007 | Format limité à PNG / JPEG sur l'ajout manuel | `ChoiceType` image/png / image/jpeg | Sélecteur legacy PNG/JPEG |
| RG-CAR-009 | Suppression immédiate sans confirmation | Lien de suppression direct | Même comportement |
| RG-CAR-010 | Pré-remplissage des couches IGN depuis le catalogue | Données IGN injectées dans les formulaires de la page d'ajout | Même logique côté legacy via le catalogue IGN |
| RG-CAR-011 | Coordonnées par défaut de l'instance | Valeurs par défaut fournies par la config Symfony | Valeurs fallback équivalentes côté legacy |
| RG-CAR-014 | Onglets cartographiques affichés seulement si coordonnées disponibles | Condition sur latitude/longitude dans la vue établissement | Même condition dans la fiche legacy |
| RG-CAR-015 | Réordonnancement de toutes les couches en une opération | `flush()` unique après boucle | Même intention métier dans le legacy |
| RG-CAR-016 | Coexistence de plusieurs fournisseurs sur la fiche établissement | Blocs d'onglets indépendants IGN / GeoConcept / Google | Même logique |

## Recommandations

1. **Corriger l'ajout IGN WMS** pour réintroduire un sélecteur PNG/JPEG quand le format est WMS, en conservant un format fixe pour WMTS.
2. **Sécuriser le module** en ajoutant une garde d'accès explicite sur `CouchesCartographiquesController` (profil administrateur).
3. **Ajouter une validation Symfony** sur l'ordre (`GreaterThanOrEqual(1)` + attribut `min=1`), afin d'aligner le comportement avec la spec et le legacy.
4. **Corriger `autoconf_path`** pour transmettre le chemin réel du fichier d'autoconfiguration IGN et non le résultat booléen de `file_exists()`.
5. **Arbitrer la partie multi-clés IGN** : soit adapter la spec à la nouvelle intégration IGN, soit remettre en cohérence les messages UI avec l'implémentation actuelle.
