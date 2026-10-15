# Suppression définitive des utilisateurs d'un groupe

Commande d'exploitation qui supprime définitivement les utilisateurs d'un groupe. Les règles fonctionnelles sont décrites dans `docs/specs/MOD-05-UTILISATEURS.md` (§3.5, RG-USR-026 à RG-USR-032).

## Usage

```bash
php bin/console app:utilisateurs:supprimer-groupe            # suppression réelle
php bin/console app:utilisateurs:supprimer-groupe --dry-run  # simulation : rien n'est supprimé
```

La commande est exclusivement interactive :

1. Elle affiche la liste des groupes. L'exploitant saisit l'**identifiant** du groupe (affiché entre crochets) ou son libellé complet.
2. Elle affiche une analyse : nombre d'utilisateurs, nombre de suppressions possibles et utilisateurs conservés avec leurs motifs.
3. Elle demande une confirmation, refusée par défaut. En simulation, aucune confirmation n'est demandée.
4. Elle affiche un bilan. Le code retour est `1` si au moins une erreur est survenue.

Avec `--no-interaction`, la commande refuse de s'exécuter (code retour `1`) et trace ce refus.

## Garde-fous

- **Groupes protégés :** le groupe par défaut (ID 1) et tout groupe disposant du privilège `admin` sont refusés.
- **Utilisateurs conservés :** un utilisateur encore référencé par des données métier n'est pas supprimé : créateur ou préventionniste de dossier, préventionniste d'établissement ou de groupement, auteur d'historique d'établissement, de suppression logique ou d'actualité. Les références sont vérifiées à nouveau juste avant la suppression. Cette vérification est faite par l'application, car certaines clés étrangères (préventionnistes) sont en `ON DELETE CASCADE`.
- **Transaction par utilisateur :** l'utilisateur est verrouillé (`SELECT … FOR UPDATE`) et doit toujours appartenir au groupe. Sinon, il est compté comme « absent ». Une erreur annule uniquement la transaction de l'utilisateur concerné, et le traitement continue.
- **Fiche d'informations partagée :** si la fiche `utilisateurinformations` est utilisée ailleurs (`adressecommune`, `groupement`, tables `*contact`, autre compte), le compte est supprimé mais la fiche est conservée. Ces partages ne sont pas créés par l'application ; ils proviennent de liaisons manuelles en base, par exemple des comptes « Maires » liés aux coordonnées de la mairie.

## Journal d'audit

| Élément | Valeur |
|---|---|
| Canal Monolog | `suppression_utilisateurs` (`config/packages/{dev,prod}/monolog.php`) |
| Fichier | `var/log/suppression_utilisateurs-AAAA-MM-JJ.log` (un fichier par jour d'exécution) |
| Niveau minimal | `info` |
| Rétention | `rotating_file` avec `max_files: 30` |

`rotating_file` conserve les 30 fichiers les plus récents. Comme un fichier n'est créé que les jours où la commande s'exécute, un ancien fichier peut être conservé plus de 30 jours si la commande est peu utilisée. La rétention est donc d'au moins 30 jours.

En environnement `test`, le canal est déclaré sans handler dédié.

### Contenu des traces

Chaque trace porte un contexte JSON avec :

- `execution` : identifiant aléatoire commun à toutes les traces d'une exécution ;
- `groupe_id` et `groupe_libelle` ;
- `simulation`.

Les utilisateurs sont identifiés uniquement par leur ID technique ; ni identifiant de connexion, ni nom, ni adresse électronique ne sont tracés.

| Message | Niveau | Contexte spécifique |
|---|---|---|
| Lancement de la suppression définitive des utilisateurs du groupe | info | `operateur` (utilisateur système, `SUDO_USER` en priorité) |
| Groupe protégé : suppression refusée | warning | — |
| Analyse du groupe | info | `nombre_utilisateurs`, `nombre_supprimables`, `nombre_conserves` |
| Utilisateur conservé : références métier | info | `utilisateur_id`, `motifs` |
| Suppression annulée par l'opérateur | info | — |
| Simulation : utilisateurs qui seraient supprimés | info | `utilisateurs` (liste d'ID) |
| Utilisateur supprimé | info | `utilisateur_id`, `fiche_informations_partagee_conservee` |
| Utilisateur absent du groupe : non supprimé | warning | `utilisateur_id` |
| Erreur lors de la suppression de l'utilisateur | error | `utilisateur_id`, `exception`, `message` |
| Fin de la suppression définitive des utilisateurs du groupe | info, ou error en cas d'erreur | compteurs du bilan |
| Exécution non interactive refusée | warning | `operateur` uniquement |

Pour suivre une exécution : `grep '"execution":"<id>"' var/log/suppression_utilisateurs-*.log`.
