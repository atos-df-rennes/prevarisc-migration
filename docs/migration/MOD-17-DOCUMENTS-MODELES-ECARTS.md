# MOD-17 — Documents modèles — Rapport d'écarts

## Synthèse

| Indicateur | Valeur |
|---|---:|
| Règles de gestion analysées | 16 |
| Écarts confirmés | 4 |
| Points à vérifier | 5 |
| Niveau de risque | Moyen |
| Niveau de confiance | Bon |

### Constats clés

- Le périmètre principal est bien migré : administration des modèles, upload ODT, écrasement silencieux par nom, espace transversal « après verrouillage », génération depuis le dossier, compte-rendu unique par commission.
- Les validations métier majeures sont présentes : format ODT à l'upload, blocage de la génération sans modèle ni établissement, filtrage des fichiers ODT côté génération de rapport.
- Les écarts confirmés concernent surtout l'**écran d'administration** : absence de repli/dépli des commissions, affichage d'un badge `0` sur une commission vide, gestion non bloquante d'un répertoire manquant.
- Un point technique important subsiste : l'administration liste tous les fichiers présents dans le répertoire d'une commission, alors que la génération ne prend en compte que les `.odt`.
- Deux variables ODT signalées dans le code Symfony sont volontairement laissées vides ou remappées, ce qui mérite une validation métier complémentaire.

## Écarts détaillés

### Écarts confirmés

| Règle | Spec | Symfony | Legacy | Impact | Fichiers |
|---|---|---|---|---|---|
| §5.1.4 — commission sans modèle | Une commission sans modèle doit être affichée sans lien et sans compteur. | Le template Twig affiche un badge avec `0` dès que `documents` n'est pas `null`. | Le legacy affichait uniquement le libellé de la commission, sans compteur. | Écart UX visible par rapport à la spec et au comportement historique. | `templates/documents/index.html.twig`, `prevarisc/application/views/scripts/gestion-des-documents/index.phtml` |
| §5.1.5 — repli/dépli des commissions | Le clic sur le nom d'une commission doit dérouler / replier sa liste de fichiers. | Le nom de commission est un simple `<h5>` et le tableau est affiché directement. | Le legacy utilisait un lien `.commission` et un `toggle()` sur le tableau associé. | Régression UX : la navigation accordéon du legacy n'est plus présente. | `templates/documents/index.html.twig`, `prevarisc/application/views/scripts/gestion-des-documents/index.phtml` |
| §8.5 — répertoire de commission inexistant | Le répertoire manquant doit provoquer une erreur bloquante. | Le builder crée un objet avec `documents = null`, et la vue affiche une alerte non bloquante. | Le legacy faisait un `opendir(...) || exit(...)`, donc arrêt bloquant. | Divergence fonctionnelle : Symfony laisse la page vivre là où le legacy bloquait. | `src/Service/Commission/CommissionDocumentBuilder.php`, `templates/documents/index.html.twig`, `prevarisc/application/controllers/GestionDesDocumentsController.php` |
| RG-DOC-001 — liste d'administration limitée aux ODT | L'administration doit gérer des modèles ODT. | `Documents::findForCommission()` liste tous les fichiers du répertoire sans filtre `*.odt`. | La génération legacy restait centrée sur les ODT et l'administration ne distinguait pas mieux, mais Symfony diverge en interne car la page rapport filtre bien `*.odt`. | Incohérence interne : un fichier non-ODT peut apparaître dans l'admin alors qu'il n'est jamais proposé dans la génération. | `src/Service/Documents.php`, `src/Controller/Dossier/DossierRapportController.php` |

### Points à vérifier

| Règle | Spec | Symfony | Legacy | Impact | Fichiers |
|---|---|---|---|---|---|
| §5.2 — barre de progression à l'upload | Une barre de progression doit s'afficher pendant l'envoi. | Aucun composant de progression visible dans les templates Symfony consultés. | Le legacy ouvrait une petite boîte de chargement avec une barre GIF. | Écart UX probable si l'on applique la spec à la lettre. | `templates/documents/_modal_ajout.html.twig`, `templates/documents/ajout.html.twig`, `prevarisc/application/views/scripts/gestion-des-documents/index.phtml` |
| §5.4 — décrémentation du compteur sans reload | Après suppression, la ligne disparaît et le compteur est décrémenté. | Symfony recharge toute la page après succès/erreur de suppression. | Le legacy retirait la ligne et décrémentait le compteur en place. | Résultat final identique, mais expérience utilisateur moins fluide. | `templates/documents/index.html.twig`, `prevarisc/application/views/scripts/gestion-des-documents/index.phtml` |
| §3.1 — variables `gn2` / `gn3` | Les champs de fusion doivent refléter correctement les données métier. | Le code Symfony remappe `gn2/gn3` à partir de `ID_GENRE`, avec commentaire précisant que la colonne legacy n'existe plus dans la base migrée. | Le legacy utilisait la donnée métier d'origine. | Risque de divergence sur certains modèles ODT selon l'interprétation métier attendue. | `src/Service/DossierRapportOdtService.php` |
| §3.1 — effectifs / dégagements | Les champs doivent être injectés dans les modèles si disponibles. | Plusieurs champs (`descriptionEffectifDossier`, `descriptionDegagementDossier`, etc.) sont explicitement ajoutés à `null`. | Le legacy disposait vraisemblablement d'un calcul ou d'une alimentation dédiée. | Les modèles utilisant ces variables produiront des champs vides, sans erreur, mais potentiellement avec perte de contenu. | `src/Service/DossierRapportOdtService.php` |
| §5.1.1 — libellé de l'espace transversal | Le libellé attendu est « Documents visibles après verrouillage ». | Le titre Twig contient une faute : `Visbles après verrouillage`. | Le legacy affichait un libellé proche, également imparfait, mais l'intention fonctionnelle restait claire. | Écart cosmétique mineur. | `templates/documents/index.html.twig` |

## Comportements alignés

| Règle | Constat | Symfony | Legacy |
|---|---|---|---|
| RG-DOC-001 | Upload limité au format ODT | Contrainte MIME sur `DocumentType` | Validation d'extension `.odt` |
| RG-DOC-002 | Répertoire commission créé automatiquement | Service dédié appelé à la création d'une commission | `mkdir()` à la création côté legacy |
| RG-DOC-003 | Espace transversal non rattaché à une commission | Commission `0` utilisée pour « visibles après verrouillage » | Même logique avec répertoire `0` |
| RG-DOC-004 | Dossier verrouillé : seuls les documents transversaux sont proposés | La liste des commissions est masquée quand le dossier est verrouillé | Même comportement |
| RG-DOC-005 | Commission du dossier présélectionnée et modifiable | Option sélectionnée par défaut dans le rapport | Même logique |
| RG-DOC-006 | Écrasement silencieux par même nom | Déplacement direct du fichier uploadé | Même comportement |
| RG-DOC-007 | Suppression confirmée avant exécution | Modale de confirmation conforme | Confirmation legacy équivalente |
| RG-DOC-008 | Nom de fichier conservé | `basename()` / nom original conservé | Nom original conservé |
| RG-DOC-009 | Tri alphabétique des fichiers | `sortByName()` | `sort(...)` |
| RG-DOC-010 | Accès depuis l'administration | Contrôleur d'administration dédié | Entrée legacy via menu admin |
| RG-DOC-011 | Génération bloquée sans modèle ou sans établissement | Contrôle JS + message utilisateur | Contrôle legacy équivalent |
| RG-DOC-012 | Un document généré par établissement | Boucle sur les établissements sélectionnés | Même logique |
| RG-DOC-013 | Commissions regroupées par type | Groupement dans l'admin et les listes | Même logique |
| RG-DOC-014 | Compte-rendu unique par commission | Remplacement explicite du fichier précédent | Même logique métier |
| RG-DOC-015 | Séparation entre courriers membres et modèles de rapport | Contrôleurs / vues séparés | Même séparation |
| RG-DOC-016 | Balises non résolues remplacées par vide | `OdtWriter` injecte `''` / ignore les variables absentes | Même comportement tolérant |

## Recommandations

1. **Filtrer les fichiers `.odt` dans `Documents::findForCommission()`** pour aligner l'administration avec la génération des rapports.
2. **Rétablir le comportement de repli/dépli** des commissions si l'objectif est l'iso-fonctionnalité stricte de l'écran d'administration.
3. **Supprimer le badge `0`** quand une commission n'a aucun modèle afin d'aligner l'UI avec la spec.
4. **Décider du niveau de sévérité attendu pour un répertoire manquant** : si la spec prime, l'erreur doit redevenir bloquante ; si l'amélioration UX est conservée, il faut documenter l'écart.
5. **Valider métier les variables ODT non portées** (`gn2/gn3`, effectifs, dégagements) avant de conclure à une iso-fonctionnalité sur les modèles complexes.
