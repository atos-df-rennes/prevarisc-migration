# Recette incrémentale de la passerelle Plat'AU

L'unité de livraison est la commande, pas le client HTTP entier. Exécuter chaque commande depuis le conteneur de services Symfony et simuler le réseau à la frontière HTTP. Les attentes portent sur ce que l'opérateur voit, ce que Plat'AU reçoit et, pour les commandes d'écriture, ce qui reste dans Prevarisc. Une réponse simulée ne valide ni les habilitations d'un SDIS ni son réseau.

| Tranche | Scénarios de recette | Preuve recherchée |
|---|---|---|
| 1 — `details-consultation` | Jeton PISTE, recherche par identifiant, affichage complet ou ciblé, résultat vide, refus, renouvellement et reprises bornées | Avec une réponse synthétique conforme à la structure v13, l'alias et la commande intégrée produisent le détail attendu. Une panne n'est pas affichée comme un résultat vide. |
| 2 — Réception des consultations | Notification initiale, pagination, offset, consultation déjà reçue, erreur après création du dossier, rejeu | Vérifier les lignes produites dans la base de test et l'état de reprise observé dans la passerelle, sans inventer une garantie d'idempotence. |
| 3 — Réception des pièces | Liste vide, téléchargement, pièce déjà reçue, échec de stockage, rejeu | Comparer les lignes **et** les fichiers dans un stockage temporaire. Avant bascule, vérifier séparément que l'application peut lire une pièce historique sur le volume cible. |
| 4 — Envoi de PEC | Dossier éligible ou non, calcul du délai, avec ou sans documents, dépôt partiel, acceptation, refus, erreur de transport | Comparer les dates, les références des fichiers déposés, le corps de la PEC et les statuts en base. Aucun envoi réel pour comparer les moteurs. |
| 5 — Envoi d'avis | Avec et sans pièces, ticket de dépôt, échec de dépôt, refus d'avis et renvoi | Vérifier l'ordre des appels, les transitions de statut et l'absence de jeton Plat'AU dans les appels au stockage tiers. |

Pour chaque tranche, partir des routes effectivement utilisées et de leur schéma dans le contrat **Démat.ADS v13**. Utiliser les nomenclatures **v13.9** lorsque le traitement interprète un code. Les jeux de données doivent être synthétiques ou anonymisés ; aucun identifiant de connexion réel dans les tests. Les commandes de diagnostic, d'enrôlement et les alias dépréciés feront l'objet de scénarios de parité avant leur propre livraison.

## Rejouer la première tranche

- Dans le dépôt d'infrastructure, `castor symfony:validate` exécute les contrôles et la suite PHPUnit par défaut. Les tests de commande doivent en faire partie, sans groupe `functional` historique.
- La lecture par identifiant utilise **`POST /dau/consultations/recherche`**, avec le critère `criteresSurConsultations.idConsultation`. Ce `POST` de recherche n'est pas une transmission métier. La fixture conserve l'enveloppe `nombrePages` / `resultats` et la liste imbriquée `dossier.consultations`.

## Utilisation par l'exploitant

```shell
bin/console --env=prod platau:details-consultation <identifiant>
bin/console --env=prod details-consultation <identifiant> --champ=dossier.consultations.0.dtLimiteDeReponse
```

Remplacer `<identifiant>` par celui de la consultation. `details-consultation` est l'alias historique. `--env` (ou `-e`) est une option globale de Symfony : elle sélectionne l'environnement de **Prevarisc**, pas une plateforme Plat'AU de test. Utiliser `--env=prevision` si ce service dispose de cet environnement sur l'installation. Sans l'option, c'est l'environnement configuré par `APP_ENV` qui s'applique. Les identifiants PISTE et l'acteur Plat'AU doivent être configurés pour le service sélectionné ; il n'y a pas d'option `--config` dans la commande intégrée.

Sans `--champ`, la commande affiche une ligne `chemin : valeur` par champ. Les points séparent les niveaux et les indices de liste (`0` pour le premier élément). Un champ absent affiche `Aucune donnée` sans erreur. Une consultation introuvable ou une lecture refusée produit un diagnostic et un code de sortie non nul.

**En cas de `401`, identifier le service qui refuse l'appel :**

- **PISTE** authentifie l'application avec ses identifiants client (HTTP Basic sur HTTPS) pour délivrer un jeton. Si PISTE les refuse, vérifier `PISTE_CLIENT_ID` et `PISTE_CLIENT_SECRET` dans la configuration effective de l'environnement, puis relancer.
- **Plat'AU** reçoit le jeton (`Authorization: Bearer …`) et l'identifiant d'acteur. Au premier `401` de Plat'AU, la commande écarte le jeton et réessaie avec un nouveau. Au second, elle écarte aussi ce dernier jeton et s'arrête. Il n'y a ni verrouillage local ni purge manuelle à effectuer avant une nouvelle exécution.

Un `403` indique un accès refusé : vérifier les habilitations auprès du service indiqué et, pour Plat'AU, l'acteur configuré. Les diagnostics HTTP indiquent le service et le statut, sans reproduire le corps de la réponse. En cas d'erreur de transport, la commande indique le service injoignable sans afficher le détail réseau ou les paramètres du proxy.

## Rejouer la réception des consultations

Effectuer la recette sur la base de test habituelle. Vérifier les URL PISTE et Plat'AU, l'acteur et la base effectivement configurés : `--env` sélectionne l'environnement Symfony, mais n'isole pas à lui seul la plateforme, l'acteur ou la base. Ne pas modifier le cron historique.

1. Configurer les accès PISTE et Plat'AU et `PREVARISC_DB_PLATAU_USER_ID`, l'identifiant d'un utilisateur technique existant dans la base de test.
2. Dans cet environnement, exécuter `bin/console platau:lecture-notifications` (alias `lecture-notifications`), éventuellement avec `--env=…`. Sans `--offset`, la plateforme retourne les notifications jamais récupérées pour l'acteur. La commande traite un lot : l'événement consultation `6/19` crée un dossier et importe ses pièces initiales ; les événements de pièce `5/15`, `5/17` et `5/71` complètent les dossiers déjà présents.
3. Vérifier le dossier d'étude, sa nature, le numéro d'urbanisme lorsqu'il est fourni, la date de réponse attendue et la PEC en attente de complétude. Aucune PEC ni aucun avis ne doit être transmis à Plat'AU.

Pour un rejeu sur la base de test, `bin/console platau:lecture-notifications --offset=42` demande le lot **à partir** de 42, pas la seule notification 42. Une consultation déjà présente est ignorée. Si une erreur survient avant la validation de la création du dossier, le rejeu peut retenter l'import. Si le dossier a été validé mais que l'initialisation de la PEC a échoué, le rejeu ne la répare pas. Contrôler les diagnostics de chaque notification, pas uniquement le code de sortie.

Les messages d'erreur peuvent contenir des données techniques ou des données de dossier : protéger l'accès à la sortie et aux journaux de la commande.

### Vérifier la réception des pièces

Utiliser un stockage de recette isolé (`PREVARISC_REAL_DATA_PATH`) et vérifier les droits d'écriture du compte qui exécute la commande. Une base de test seule n'isole ni les fichiers ni le flux de notifications.

1. Pour une nouvelle consultation avec pièces, comparer la liste renvoyée par `GET /dau/dossiers/{idDossier}/pieces` avec l'onglet **Pièces jointes**. Vérifier le nom versionné, les métadonnées, le lien `dossierpj` et le contenu du fichier téléchargé depuis Prevarisc. Une liste vide ne doit produire ni ligne ni fichier.
2. Pour une notification de pièce ultérieure, vérifier que seule la pièce désignée est ajoutée, à chaque consultation du dossier Plat'AU déjà importée. Rejouer depuis l'offset : les lignes et fichiers déjà présents doivent rester inchangés.
3. En cas d'échec de stockage, vérifier l'absence de nouvelle pièce et de liaison pour la tentative. Après correction, rejouer la **notification de pièce**. Rejouer une **notification de consultation** déjà importée ne reprend pas les pièces initiales manquantes.

**Avant toute bascule**, télécharger depuis l'interface une pièce historique connue sur le volume cible, avec le compte et la configuration prévus en exploitation. Un fichier nouvellement créé ou un test automatisé sur un volume temporaire ne prouve pas que l'ancien volume est monté. Ne pas changer le cron : les notifications de documents relatives aux envois peuvent encore être consommées sans traitement.

## Vérifier l'envoi d'une PEC

`bin/console platau:export-pec` (alias `export-pec`) traite le lot de consultations. `--consultation-id=<identifiant>` limite le traitement à une consultation ; `--delai-reponse=10` remplace son délai par dix jours calendaires. **Ce n'est pas un mode simulation**, même avec un identifiant ciblé : utiliser la base et le stockage de recette et simuler PISTE, Plat'AU, Syncplicity et le stockage du ticket. Ne pas activer le cron intégré en parallèle de la passerelle.

1. Comparer une PEC positive, une PEC négative avec observations et un renvoi daté. Contrôler les versions de dossier et de consultation, l'acteur, l'auteur, la date de PEC et l'échéance du `POST /dau/pecMetier/consultations`.
2. Marquer deux pièces en attente, puis simuler l'échec de dépôt de la première. Seule la seconde doit apparaître dans la PEC (type documentaire `47`) et recevoir son propre `idDocument`. Vérifier que le dépôt utilise le ticket Syncplicity, pas le jeton Plat'AU. Sans configuration Syncplicity, les fichiers restent en attente et la PEC peut partir seule.
3. Retirer la complétude en laissant une pièce en attente. La commande ne doit ni demander de ticket ni transmettre la PEC. Une consultation refusée n'est traitée qu'en renvoi ou en erreur.
4. Distinguer une PEC **négative acceptée** (PEC transmise, avis en cours, aucun avis envoyé) d'un **refus de transmission** (PEC en erreur). Lire les diagnostics de chaque consultation, pas uniquement le code de sortie. Après une rupture réseau pendant l'envoi, vérifier l'état distant avant toute relance : le client peut avoir perdu la réponse alors que la plateforme a reçu la PEC.

Le dépôt réussi laisse la pièce en attente de retour. Les notifications documentaires `84`/`85`, l'envoi des avis et la bascule d'exploitation ne font pas partie de cette tranche.
