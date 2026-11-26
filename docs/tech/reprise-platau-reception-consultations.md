# Plat'AU — reprise du lundi 23 novembre 2026

**Première action : tester la réception d'une consultation sur la base Prevarisc de test habituelle.** La réception n'a pas encore été essayée sur la plateforme. L'essai manuel concluant de la tranche précédente portait sur la **lecture du détail**, pas sur l'import.

## Ce qui est terminé

| Tranche | État au 20 novembre |
|---|---|
| 1 — Lecture du détail | Intégrée dans `develop` (`4bfa0827`). `platau:details-consultation` lit le détail brut, sans écriture locale. Les clients Symfony gèrent les accès PISTE et Plat'AU. |
| 2 — Réception des consultations | Sur la branche de recette. `platau:lecture-notifications` lit un lot, traite uniquement les événements de consultation `6/19`, crée le dossier d'étude et met la PEC en attente **sans l'envoyer**. Les pièces ne sont pas importées. Aucun cron modifié. |
| Refactor de la tranche 2 | `5d1f860b` : le dénormaliseur Symfony construit un dossier typé avec sa consultation ; l'importeur ne parcourt plus le JSON. La recherche ciblée attend toujours **une seule consultation avec l'identifiant demandé**. `621e2577` : les tests présentent les réponses HTTP dans l'ordre des appels. |

Dernière validation locale : `castor symfony:validate` depuis la racine de `prevarisc-infra` — **1 239 tests, aucun échec, trois dépréciations et un test ignoré**. Les tests de commande utilisent la vraie console Symfony et la base de test, avec HTTP simulé. Ils couvrent notamment le lot mixte, les refus, la pagination, le doublon et le rejeu. Le cas « dossier créé, PEC non initialisée » est préparé en base : le test prouve l'effet du rejeu, pas la survenue d'une panne réelle.

## Lundi : fermer la tranche 2

1. **Vérifier la configuration avant toute lecture.** Confirmer les URL PISTE/Plat'AU, l'acteur, la base de test et l'existence de l'utilisateur technique `PREVARISC_DB_PLATAU_USER_ID`. Prévoir une notification de consultation connue sur cet acteur. `--env` choisit l'environnement Symfony, pas la plateforme ; la lecture peut consommer les notifications de pièces et de documents sans les traiter.
2. **Importer un lot** avec `bin/console platau:lecture-notifications`. Noter l'offset de la consultation et lire chaque diagnostic : une erreur individuelle n'empêche pas la poursuite du lot et peut laisser un code de sortie `0`. Un lot vide ne valide pas l'import.
3. **Vérifier le résultat dans Prevarisc.** Pour l'identifiant attendu : un seul dossier d'étude, sans établissement, avec le bon créateur, la nature, les demandeurs et le numéro d'urbanisme lorsqu'ils sont fournis. Contrôler l'échéance, la complétude non renseignée et la PEC en attente. Aucun fichier créé, aucune PEC ni aucun avis envoyé.
4. **Rejouer en recette** avec `bin/console platau:lecture-notifications --offset=<offset-noté>` (remplacer le marqueur). L'offset est un **point de départ**, pas un filtre : les notifications suivantes peuvent aussi être traitées. Le dossier déjà présent doit rester inchangé, sans doublon. Si sa création avait réussi mais pas l'initialisation de sa PEC, le rejeu ne la répare pas.
5. **Clôturer la revue vers `develop`.** Consigner le résultat anonymisé de l'essai. En cas d'écart, en faire un scénario reproductible avant de corriger ; relancer `castor symfony:validate` après correction. Fusionner après recette et revue, **sans basculer le cron**.

## Après la fusion

Repartir de `develop` à jour sur une branche dédiée aux **pièces** : pièces initiales et notifications ultérieures, téléchargement, liens en base, doublons, échec de stockage et rejeu. Vérifier les lignes **et** les fichiers ; avant toute bascule, confirmer qu'une pièce historique est lisible sur le volume cible. Viendront ensuite l'envoi de PEC, puis l'envoi d'avis, en tranches séparées. La bascule d'exploitation reste une décision distincte.

**Repères :** [procédure opérateur et matrice des tranches](recette-passerelle-platau.md) · [règles métier MOD-13](../specs/MOD-13-PLATAU.md) · [scénarios de réception](../../tests/Command/PlatauLectureNotificationsTest.php). Dans le dépôt d'infrastructure : `docs/platau/integration_application/{review.md,plan.md}` et contrat API sous `docs/platau/v13/`. La branche `feature/integration-passerelle` reste une référence à reprendre sélectivement, pas une branche à fusionner en bloc.
