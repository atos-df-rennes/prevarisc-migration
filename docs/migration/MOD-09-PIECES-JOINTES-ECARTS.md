# Rapport d'écarts — MOD-09 PIÈCES JOINTES

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-09-PIECES-JOINTES.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse
| Indicateur | Valeur |
|---|---|
| Règles de gestion analysées | 22 (RG-PJ-001 à RG-PJ-022) |
| Écarts spec ↔ Symfony | 5 |
| Écarts spec ↔ Legacy | 2 |
| Écarts Symfony ↔ Legacy | 5 |

---

## Écarts détaillés
### Écart 1 — Le lien d'une pièce jointe de dossier vers un ou plusieurs établissements n'est pas porté
| | Détail |
|---|---|
| **Règles** | RG-PJ-005, § 5.1.1 |
| **Spec** | Lors du dépôt sur un dossier, l'utilisateur peut cocher un ou plusieurs établissements rattachés au dossier pour créer les liaisons associées. |
| **Symfony** | Le formulaire d'ajout de PJ dossier ne contient que `fichier`, `nomFichier` et `descriptionFichier`, sans liste d'établissements (`templates/dossier/pieces_jointes/form.html.twig:8-21`). L'action d'ajout crée seulement `PieceJointe` puis `DossierPieceJointe`, sans traiter d'éventuel `etab[]` (`src/Controller/Dossier/DossierPiecesJointesController.php:220-264`). |
| **Legacy** | Le legacy charge `listeEtablissement` pour le type `dossier` (`application/controllers/PieceJointeController.php:165-169`), affiche les cases à cocher `etab[]` (`application/views/scripts/piece-jointe/form.phtml:25-31`) puis crée les liaisons établissement↔PJ dans `addAction()` (`application/controllers/PieceJointeController.php:238-255`). **Cependant**, aucune miniature n'est générée pour l'affichage des PJ dossier liées aux établissements (miniatures créées uniquement pour le type `établissement` ligne 257-284). |
| **Impact** | **Nul** — fonctionnalité morte en legacy. Les liaisons établissement↔PJ sont créées mais jamais exploitées (aucune miniature, aucun affichage front). La suppression ne cause aucune régression. |
| **Fichiers concernés** | `prevarisc-migration/templates/dossier/pieces_jointes/form.html.twig`, `prevarisc-migration/src/Controller/Dossier/DossierPiecesJointesController.php`, `prevarisc/application/views/scripts/piece-jointe/form.phtml`, `prevarisc/application/controllers/PieceJointeController.php` |

**Validation utilisateur** (2026-07-01) : Confirmé — le formulaire legacy crée les liaisons mais aucune miniature n'est générée. Le code est mort. La migration Symfony supprime cette fonction inutilisée. **✅ Aucune action requise — amélioration implicite**.

---

### Écart 2 — La sélection unique d'une pièce jointe pour le passage en commission n'est pas disponible
| | Détail |
|---|---|
| **Règles** | RG-PJ-012, § 5.5 |
| **Spec** | Une seule pièce jointe de dossier peut être cochée à la fois pour le passage en commission ; cocher une ligne désactive les autres. |
| **Symfony** | La création initialise `pieceJointeCommission` à `false` (`src/Controller/Dossier/DossierPiecesJointesController.php:257-260`), mais l'interface ne propose aucune case à cocher ni aucune action de bascule (`templates/dossier/pieces_jointes/_piece_jointe_row.html.twig:1-90`). Aucun endpoint dédié n'est exposé dans le contrôleur dossier. |
| **Legacy** | La liste legacy embarque un comportement JS `.select` qui poste vers `/dossier/fonction?do=pjPassageCommission` et désactive les autres cases quand une PJ est cochée (`application/views/scripts/piece-jointe/index.phtml:150-195`). |
| **Impact** | **Élevé** — la règle métier est présente en base mais plus exploitable dans l'UI Symfony. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Dossier/DossierPiecesJointesController.php`, `prevarisc-migration/templates/dossier/pieces_jointes/_piece_jointe_row.html.twig`, `prevarisc/application/views/scripts/piece-jointe/index.phtml` |

**Retour utilisateur** : Je n'ai jamais vu cette fonctionnalité dans l'application. Vérifier qu'il ne s'agit pas de code mort inatteignable.

---

### Écart 3 — Le téléchargement des pièces jointes de date de commission dépend encore du routing legacy
| | Détail |
|---|---|
| **Règles** | § 3, § 5.2.1, RG-PJ-020 |
| **Spec** | Les pièces jointes peuvent être consultées/téléchargées pour les dossiers, établissements et dates de commission. En cas de fichier absent, le système doit renvoyer une ressource introuvable. |
| **Symfony** | La ligne Twig des commissions pointe en dur vers `/piece-jointe/get/id/{id}/idpj/{idpj}/type/dateCommission`, c'est-à-dire l'URL Zend historique (`templates/calendrier_des_commissions/_piece_jointe_row.html.twig:1-4`). Le contrôleur Symfony `PieceJointeController` ne gère que le cas établissement via `/piece-jointe/etablissement/{id}/recuperer/{idpj}` (`src/Controller/PieceJointeController.php:39-48`). |
| **Legacy** | Le contrôleur Zend `PieceJointeController::getAction()` traite encore le téléchargement générique pour les différents types via `type`/`id` (`application/controllers/PieceJointeController.php:121-143`). |
| **Impact** | **Élevé** — la consultation des PJ de commission reste couplée au legacy ; la migration n'est pas autonome sur ce flux. |
| **Fichiers concernés** | `prevarisc-migration/templates/calendrier_des_commissions/_piece_jointe_row.html.twig`, `prevarisc-migration/src/Controller/PieceJointeController.php`, `prevarisc/application/controllers/PieceJointeController.php` |

---

### Écart 4 — Les valeurs de placement établissement ne sont pas compatibles avec le legacy et la génération des miniatures est plus large que prévu
| | Détail |
|---|---|
| **Règles** | RG-PJ-006, RG-PJ-007, § 5.4 |
| **Spec** | La mise en avant ne concerne que les images ; la miniature 450 px doit être générée pour les placements « diaporama » ou « plans ». |
| **Symfony** | Le formulaire Symfony code `0 = liste`, `1 = diaporama`, `2 = plans` (`templates/etablissement/add_piece_jointe.html.twig:14-20`, `src/Constant/PlacementEtablissementPj.php:5-18`). Le repository `getPlans()` ne remonte que `placement = 2` (`src/Repository/EtablissementPieceJointeRepository.php:64-76`). En plus, `EtablissementPieceJointeService` génère une miniature pour **toute** image, quel que soit le placement (`src/Service/EtablissementPieceJointeService.php:124-133`). |
| **Legacy** | Le formulaire Zend stockait `null = liste`, `1 = diaporama`, `0 = plans` (`application/views/scripts/piece-jointe/form.phtml:8-15`). Les données legacy existantes avec `PLACEMENT_ETABLISSEMENTPJ = 0` correspondaient donc aux plans. |
| **Impact** | **Élevé** — risque d'interprétation incorrecte des données existantes (plans legacy vus comme « liste principale »), plus création de miniatures superflues pour les images non mises en avant. |
| **Fichiers concernés** | `prevarisc-migration/templates/etablissement/add_piece_jointe.html.twig`, `prevarisc-migration/src/Constant/PlacementEtablissementPj.php`, `prevarisc-migration/src/Repository/EtablissementPieceJointeRepository.php`, `prevarisc-migration/src/Service/EtablissementPieceJointeService.php`, `prevarisc/application/views/scripts/piece-jointe/form.phtml` |

---

### Écart 5 — La date d'ajout est stockée/affichée sans heure alors que la spec parle d'une date-heure
| | Détail |
|---|---|
| **Règles** | RG-PJ-004, § 4.1 |
| **Spec** | La date d'ajout doit contenir la date **et l'heure** de création. |
| **Symfony** | L'ajout enregistre `new \DateTime()` (`src/Controller/Dossier/DossierPiecesJointesController.php:230-232`, `src/Controller/Commission/CalendrierDesCommissionsPiecesJointesController.php:116-120`), mais l'entité mappe `DATE_PIECEJOINTE` en `type="date"` (`src/Entity/PieceJointe.php:47-52`) et les templates n'affichent que `d/m/Y` (`templates/dossier/pieces_jointes/_piece_jointe_row.html.twig:43`). |
| **Legacy** | Le legacy écrit aussi une date courante lors du dépôt (`application/controllers/PieceJointeController.php:194-205`) puis n'affiche que la date côté liste (`application/views/scripts/piece-jointe/display.phtml:68-71`). |
| **Impact** | **Moyen** — la précision horaire demandée par la spec n'est ni conservée ni visible dans les deux implémentations. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Dossier/DossierPiecesJointesController.php`, `prevarisc-migration/src/Controller/Commission/CalendrierDesCommissionsPiecesJointesController.php`, `prevarisc-migration/src/Entity/PieceJointe.php`, `prevarisc-migration/templates/dossier/pieces_jointes/_piece_jointe_row.html.twig`, `prevarisc/application/controllers/PieceJointeController.php`, `prevarisc/application/views/scripts/piece-jointe/display.phtml` |

---

### Écart 6 — Le legacy ne respecte pas toujours le nom par défaut « sans extension »
| | Détail |
|---|---|
| **Règles** | RG-PJ-003 |
| **Spec** | Si le libellé est vide, le nom par défaut doit reprendre le nom d'origine du fichier **sans extension**. |
| **Symfony** | Symfony utilise `pathinfo(..., PATHINFO_FILENAME)` ou la longueur réelle de l'extension, ce qui gère correctement `.jpeg`, `.docx`, etc. (`src/Controller/Dossier/DossierPiecesJointesController.php:220-224`, `src/Controller/Commission/CalendrierDesCommissionsPiecesJointesController.php:110-113`). |
| **Legacy** | Le legacy coupe systématiquement les 4 derniers caractères (`substr($_FILES['fichier']['name'], 0, -4)`), ce qui ne respecte pas la règle pour les extensions de longueur différente (`application/controllers/PieceJointeController.php:200-203`). |
| **Impact** | **Faible à moyen** — nom par défaut potentiellement tronqué de manière incorrecte en legacy ; Symfony corrige ce point. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Dossier/DossierPiecesJointesController.php`, `prevarisc-migration/src/Controller/Commission/CalendrierDesCommissionsPiecesJointesController.php`, `prevarisc/application/controllers/PieceJointeController.php` |

---

## Comportements alignés (confirmés)
| Règles | Sujet | Constat |
|---|---|---|
| RG-PJ-002 | Extensions interdites | ✅ Aligné : blacklist `.php/.php4/.php5/.sh/.ksh/.csh` présente en Symfony et legacy (`src/Controller/Dossier/DossierPiecesJointesController.php:195-207`, `src/Controller/Commission/CalendrierDesCommissionsPiecesJointesController.php:104-108`, `application/controllers/PieceJointeController.php:188-191`). |
| RG-PJ-008 / RG-PJ-009 / RG-PJ-010 | Suppression conditionnelle | ✅ Aligné : Symfony bloque la suppression si dossier verrouillé ou statut Plat'AU protégé (`src/Controller/Dossier/DossierPiecesJointesController.php:326-343`) comme le legacy qui masque l'action dans ces cas (`application/views/scripts/piece-jointe/display.phtml:106-112`). |
| RG-PJ-011 | Fichiers illisibles masqués | ✅ Aligné : filtrage `is_readable()` côté Symfony (`src/Controller/Dossier/DossierPiecesJointesController.php:126-129`) et côté legacy (`application/controllers/PieceJointeController.php:142-143` pour le téléchargement, logique de filtrage reprise dans l'affichage). |
| RG-PJ-013 / RG-PJ-014 / RG-PJ-015 / RG-PJ-016 | Cycle de statut Plat'AU | ✅ Aligné : statut `not_exported` à la création, proposition des PJ non envoyées, badge d'état et bouton `Renvoyer` présents dans Symfony comme en legacy (`src/Controller/Dossier/DossierPiecesJointesController.php:234-238`, `templates/dossier/pieces_jointes/_piece_jointe_row.html.twig:45-66`, `application/views/scripts/piece-jointe/display.phtml:73-102`). |
| RG-PJ-017 / RG-PJ-018 | Métadonnées et badge « Nouveau ! » | ✅ Aligné : l'identifiant Plat'AU et le badge `Nouveau !` sont affichés dans les listes Symfony et legacy (`templates/dossier/pieces_jointes/_piece_jointe_row.html.twig:5-16`, `application/views/scripts/piece-jointe/display.phtml:5-31`). |
| RG-PJ-019 | Téléchargement ZIP dossier | ✅ Aligné : le téléchargement groupé est proposé uniquement s'il existe au moins une PJ lisible (`src/Controller/Dossier/DossierPiecesJointesController.php:406-431`). |
| RG-PJ-020 / RG-PJ-021 / RG-PJ-022 | Fichier manquant, stockage Plat'AU distinct, message de taille | ✅ Aligné : chemin distinct pour dossiers Plat'AU, erreur 404 si fichier absent et message « contactez votre service informatique » en cas d'échec d'upload (`src/Controller/Dossier/DossierPiecesJointesController.php:86-98`, `src/Controller/PieceJointeController.php:64-78`, `src/Controller/Dossier/DossierPiecesJointesController.php:245-254`). |

---

## Recommandations
1. Porter en priorité la liaison PJ dossier ↔ établissements et la sélection « passage en commission », ce sont les deux régressions métier les plus visibles.
2. Ajouter une vraie route Symfony de téléchargement pour les PJ de date de commission afin de supprimer la dépendance au contrôleur Zend.
3. Clarifier puis migrer la sémantique des valeurs `PLACEMENT_ETABLISSEMENTPJ` avant toute reprise de données, sinon les plans legacy seront interprétés à tort.
4. Décider explicitement si la date d'ajout doit rester une simple date (comme historiquement) ou devenir une vraie date-heure conforme à la spec.
5. Conserver le correctif Symfony sur le nom par défaut et documenter ce bug legacy dans le rapport de migration.
