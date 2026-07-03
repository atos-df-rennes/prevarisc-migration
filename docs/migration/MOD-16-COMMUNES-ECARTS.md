# MOD-16 — Gestion des communes — Rapport d'écarts

## Synthèse

| Indicateur | Valeur |
|---|---:|
| Règles de gestion analysées | 19 |
| Écarts confirmés | 7 |
| Points à vérifier | 5 |
| Niveau de risque | Élevé |
| Niveau de confiance | Bon |

### Constats clés

- Le périmètre de consultation reste globalement aligné : liste alphabétique, chargement AJAX de la fiche détaillée, affichage du code INSEE et du code postal en lecture seule.
- Le périmètre de **fusion de communes** est en revanche partiellement migré seulement : le transfert des adresses et des rues existe, mais deux suppressions métier du legacy ont disparu.
- Les deux écarts les plus critiques sont l'absence de **suppression des liaisons groupement–commune** et l'absence de **suppression des règles de commission** lors d'une fusion.
- La logique d'arrêt global du traitement n'est plus respectée : Symfony continue sur la fusion suivante alors que la spec et le legacy imposent l'arrêt du lot.
- La sauvegarde des coordonnées de mairie n'est plus asynchrone et la fonction « Mairie » n'est pas initialisée explicitement lors de la création d'une nouvelle fiche.

## Écarts détaillés

### Écarts confirmés

| N° | Règle | Spec | Symfony | Legacy | Impact | Fichiers |
|---|---|---|---|---|---|---|
| **E-01** | RG-CMN-014 — suppression des liaisons groupement–commune | Lors d'une fusion, les associations groupement–commune des communes absorbées doivent être supprimées. | `FusionDesCommunes::fusionner()` ne fait que transférer les adresses, transférer les rues puis supprimer les communes. Aucune suppression de liaison groupement n'est prévue. | `Service_FusionCommand::mergeArrayCommune()` appelle explicitement `deleteArrayGroupementCommune()`. | Régression métier critique : des rattachements groupement obsolètes peuvent subsister après fusion. | `src/Service/FusionDesCommunes.php`, `prevarisc/application/services/FusionCommand.php` |
| **E-02** | RG-CMN-015 — suppression des règles de commission | Les règles de commission liées aux communes absorbées doivent être supprimées. | Aucune suppression des règles de commission dans le service Symfony. | Le legacy appelle explicitement `deleteArrayCommissionRegle()`. | Régression métier critique : des règles de compétence peuvent rester actives sur des communes supprimées. | `src/Service/FusionDesCommunes.php`, `prevarisc/application/services/FusionCommand.php` |
| **E-03** | RG-CMN-017 — arrêt global sur erreur de cohérence | Si un contrôle échoue sur une opération de fusion, le traitement global doit s'arrêter. | `communeExists()` retourne `false`, puis `fusionner()` fait `continue` et passe à l'opération suivante. | Le legacy fait `return true` dès la première erreur et stoppe tout le traitement. | Régression logique : le lot peut être exécuté partiellement alors que la spec impose un arrêt global. | `src/Service/FusionDesCommunes.php`, `prevarisc/application/services/FusionCommand.php` |
| **E-04** | RG-CMN-008 — sauvegarde asynchrone de la fiche mairie | La sauvegarde des coordonnées doit se faire en arrière-plan, sans rechargement de la page. | Le contrôleur traite un POST classique puis redirige vers la liste des communes. | La vue legacy utilise un `$.ajax()` sur le bouton Sauvegarder, sans rechargement complet. | Régression UX visible : perte du comportement d'édition fluide attendu. | `src/Controller/CommunesController.php`, `templates/communes/_informations.html.twig`, `prevarisc/application/views/scripts/gestion-des-communes/display.phtml` |
| **E-05** | RG-CMN-006 — fonction du contact fixée à « Mairie » | Le champ fonction doit être imposé à « Mairie » et masqué. | Le formulaire Symfony n'expose pas de champ `fonction`, mais ne met pas non plus de valeur par défaut visible dans le code. | Le legacy envoie silencieusement `ID_FONCTION=2` dans le formulaire de contact. | Risque de données incomplètes : une nouvelle fiche de mairie peut être créée sans fonction définie. | `src/Form/CommuneUtilisateurInformationsType.php`, `prevarisc/application/views/scripts/contact/form.phtml` |
| **E-06** | §7.6 — mode embarqué de la fiche commune | En mode embarqué, l'identité de la commune et le bouton Sauvegarder ne doivent pas être affichés. | Le template Symfony n'a pas de mode embarqué ni de condition équivalente. | Le legacy masque explicitement ces blocs via `if($this->ext != true)`. | Fonctionnalité non migrée pour les usages embarqués. | `templates/communes/_informations.html.twig`, `prevarisc/application/views/scripts/gestion-des-communes/display.phtml` |
| **E-07** | RG-CMN-009 — validation stricte du fichier JSON | Un seul fichier JSON doit être accepté par opération. | Le formulaire Symfony affiche seulement l'aide « Format JSON. » sans contrainte technique d'extension ou de nombre. | Le formulaire Zend imposait `Count=1` et `Extension=json`. | Régression de validation : le contrôle de type de fichier repose uniquement sur l'usage attendu. | `src/Form/FusionDesCommunesType.php`, `prevarisc/application/forms/FusionCommunes.php` |

### Points à vérifier

| N° | Règle | Spec | Symfony | Legacy | Impact | Fichiers |
|---|---|---|---|---|---|---|
| **V-01** | RG-CMN-004 — création automatique de la fiche mairie | Si la commune n'a pas encore de fiche, elle doit être créée à la première sauvegarde. | Le comportement semble reposer sur le binding Symfony du sous-formulaire et le cascade persist, sans logique métier explicite. | Le legacy créait explicitement une fiche à la première sauvegarde. | À confirmer par test fonctionnel : le comportement peut dépendre de la façon dont le sous-formulaire est instancié. | `src/Controller/CommunesController.php`, `src/Form/CommuneType.php`, `prevarisc/application/controllers/GestionDesCommunesController.php` |
| **V-02** | RG-CMN-005 — recréation si la fiche référencée est incohérente | Si la fiche liée n'existe plus, elle doit être recréée et reliée à la commune. | Aucune logique explicite visible côté Symfony. | Le legacy testait explicitement l'existence puis recréait la fiche si besoin. | Risque de comportement divergent en cas de données corrompues. | `src/Controller/CommunesController.php`, `prevarisc/application/controllers/GestionDesCommunesController.php` |
| **V-03** | §5.2.5 — coexistence message succès + erreur | Certaines opérations peuvent réussir et d'autres échouer, les deux messages peuvent coexister. | Le contrôleur Symfony renvoie soit un flash `danger`, soit un flash `success`, jamais les deux. | Le legacy affichait `processComplete` et `hasErrors` indépendamment. | Retour utilisateur moins fidèle au comportement historique lors des lots mixtes. | `src/Controller/FusionDesCommunesController.php`, `prevarisc/application/controllers/FusionDesCommunesController.php`, `prevarisc/application/views/scripts/fusion-des-communes/index.phtml` |
| **V-04** | RG-CMN-019 — suppression du fichier import après traitement | Le fichier importé doit être supprimé après traitement, succès ou échec. | Aucun `unlink()` explicite côté Symfony ; on dépend du cycle de vie standard du fichier uploadé. | Le legacy supprimait explicitement le fichier après traitement. | Point d'exploitation à confirmer selon l'environnement d'exécution. | `src/Controller/FusionDesCommunesController.php`, `prevarisc/application/controllers/FusionDesCommunesController.php` |
| **V-05** | Droits d'accès communes | L'accès aux écrans d'administration est réservé au profil administrateur. | `CommunesController` est protégé par `@IsGranted("communes")`, tandis que la fusion l'est par `@IsGranted("admin")`. | Le legacy restait dans le périmètre administration sans distinction fine visible dans les fichiers consultés. | À confirmer avec le voter / la matrice ACL : la gestion simple et la fusion n'ont pas exactement le même garde-fou. | `src/Controller/CommunesController.php`, `src/Controller/FusionDesCommunesController.php` |

## Comportements alignés

| Règle | Constat | Symfony | Legacy |
|---|---|---|---|
| RG-CMN-001 | Code INSEE en lecture seule | Affiché hors formulaire | Affiché hors formulaire |
| RG-CMN-002 | Liste triée par libellé | `findBy([], ['libelle' => 'ASC'])` | `fetchAll(..., 'LIBELLE_COMMUNE')` |
| RG-CMN-003 | Une seule fiche de coordonnées par commune | Relation `OneToOne` | Référence unique côté legacy |
| RG-CMN-007 | Champ « Société » non proposé | Champ absent du formulaire commune | Champ non affiché pour une commune |
| §4.1 | Message si aucune commune | Message Twig explicite | Message legacy explicite |
| §4.1 | Chargement AJAX de la commune sélectionnée | `.load(...)` sur changement de liste | `.load(...)` sur changement de liste |
| §4.1 | Première commune sélectionnée par défaut | `.change()` déclenché à l'initialisation | Même logique |
| RG-CMN-011 | Vérification stricte du libellé cible | `strcmp(...) !== 0` | `strcmp(...) !== 0` |
| RG-CMN-012 | Transfert des adresses d'établissements | `updateAdressesEtablissements()` | `setNewNumINSEE()` |
| RG-CMN-013 | Transfert des rues | `updateRues()` | `setAdresseRueFk()` |
| RG-CMN-016 | Suppression finale de la commune absorbée | `deleteCommunes()` | `deleteArrayAdresseCommune()` |

## Recommandations

1. **Compléter la fusion Symfony** avec la suppression des liaisons groupement–commune et des règles de commission avant la suppression des communes absorbées.
2. **Remplacer le `continue` par un arrêt global** (exception ou retour bloquant) pour respecter la logique métier de la spec et du legacy.
3. **Rendre la sauvegarde commune asynchrone** avec une réponse JSON et un message utilisateur sans rechargement de page.
4. **Initialiser explicitement la fonction “Mairie”** lors de la création d'une fiche de coordonnées, même si le champ reste masqué.
5. **Ajouter une vraie validation sur l'upload de fusion** (JSON uniquement, un seul fichier).
6. **Décider du niveau de sévérité attendu** pour les répertoires / incohérences / imports : si l'objectif est l'iso-fonctionnalité stricte, les comportements de blocage et les messages mixtes doivent être réalignés.
