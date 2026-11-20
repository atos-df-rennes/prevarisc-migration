# Recette incrémentale de la passerelle Plat'AU

L'unité de livraison est la commande, pas le client HTTP entier. Exécuter chaque commande depuis le conteneur de services Symfony et simuler le réseau à la frontière HTTP. Les attentes portent sur ce que l'opérateur voit, ce que Plat'AU reçoit et, pour les commandes d'écriture, ce qui reste dans Prevarisc. Une réponse simulée ne valide ni les habilitations d'un SDIS ni son réseau.

| Tranche | Scénarios de recette | Preuve recherchée |
|---|---|---|
| 1 — `details-consultation` | Jeton PISTE, recherche par identifiant, affichage complet ou ciblé, résultat vide, refus, renouvellement et reprises bornées | Avec une réponse synthétique conforme à la structure v13, l'alias et la commande intégrée produisent le détail attendu. Une panne n'est pas affichée comme un résultat vide. |
| 2 — Réception des consultations | Notification initiale, pagination, offset, consultation déjà reçue, erreur après création du dossier, rejeu | Vérifier les lignes produites dans la base de test et l'état de reprise observé dans la passerelle, sans inventer une garantie d'idempotence. |
| 3 — Réception des pièces | Liste vide, téléchargement, pièce déjà reçue, échec de stockage, rejeu | Comparer les lignes **et** les fichiers dans un stockage temporaire. Avant bascule, vérifier séparément que l'application peut lire une pièce historique sur le volume cible. |
| 4 — Envoi de PEC | Dossier éligible ou non, calcul de délai, acceptation, refus, erreur de transport | Tester les règles de date à l'unité, puis comparer le corps de la requête et le statut persisté. Aucun envoi réel pour comparer les moteurs. |
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
