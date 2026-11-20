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
- Pour une vérification d'exploitation autorisée : `bin/console platau:details-consultation <identifiant> --champ=dossier.consultations.0.idConsultation --env=prod`. Sélectionner l'environnement du service concerné. Aucun changement de cron, de tableau de bord ou d'émetteur actif n'est nécessaire.
