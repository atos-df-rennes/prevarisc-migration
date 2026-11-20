# Corriger le créateur des dossiers Plat'AU

Ce guide s'adresse aux administrateurs Prevarisc et aux équipes d'exploitation.
La mise à jour corrige les créateurs historiques à `0`. La configuration du
compte de service reste nécessaire pour les prochaines consultations.

## Reprise automatique des dossiers existants

La migration `Version20261120174617` applique les règles suivantes :

| Situation avant migration | Créateur après migration |
| --- | --- |
| Créateur à `0` et `ID_PLATAU` non NULL | Identifiant du compte de service `platau` |
| Créateur à `0` et `ID_PLATAU` NULL | `NULL` (pas de créateur renseigné) |
| Créateur différent de `0` ou déjà NULL | Inchangé |

**Sauvegardez la base et vérifiez que la sauvegarde est restaurable avant
d'appliquer les migrations.** La reprise est irréversible : un retour arrière
Doctrine ne peut pas retrouver les lignes qui étaient à `0`. Pour retrouver
les données d'avant la reprise, il faut restaurer la sauvegarde.

## Appliquer les migrations

Prévoyez une fenêtre de maintenance et suspendez l'import des consultations
jusqu'à la fin des vérifications. Depuis l'installation Prevarisc déployée,
utilisez l'environnement Symfony du client et vérifiez que la console cible
bien sa base. Pour un déploiement en production :

```bash
php bin/console doctrine:migrations:migrate --env=prod
```

La version corrigée de `Version20250220142015` crée le compte `platau` si
elle est encore à exécuter et que le compte n'existe pas. Si Doctrine
considère déjà cette version comme exécutée, la modification du fichier ne
la rejoue pas. Si la reprise s'arrête faute de compte, vérifiez l'historique
avec `doctrine:migrations:status` dans le même environnement et faites
rétablir le compte de service par l'équipe d'exploitation avant de relancer
la migration. N'utilisez pas l'identifiant d'un autre compte.

## Configurer le compte de service

Après les migrations, relevez l'identifiant sur la même base :

```bash
php bin/console doctrine:query:sql --env=prod "SELECT ID_UTILISATEUR FROM utilisateur WHERE USERNAME_UTILISATEUR = 'platau'"
```

La requête doit renvoyer **une seule ligne avec un identifiant strictement
positif**. Reportez cet entier dans `PREVARISC_DB_PLATAU_USER_ID`, sans
réutiliser la valeur `0` ni l'identifiant d'une autre instance.

Modifiez la source de configuration réellement chargée chez le client par
le processus qui importe les consultations (service, conteneur ou tâche
planifiée selon le déploiement). Un `export` dans le terminal d'administration
ne modifie pas l'environnement de ce processus. Si la passerelle reçoit ses
options via un fichier JSON, renseignez la même clé sous `prevarisc.options`
dans le fichier effectivement passé à la passerelle.

Appliquez le changement selon le déploiement : redémarrez le service ou
recréez le conteneur si son environnement est injecté au démarrage ; pour
une tâche planifiée, vérifiez qu'elle charge la configuration modifiée à sa
prochaine exécution. Si vous avez modifié des fichiers `.env*` Symfony et que
l'instance utilise un environnement compilé, régénérez-le selon la procédure
de déploiement (par exemple `composer dump-env prod`).

Reprenez les imports et vérifiez que le prochain dossier issu d'une
consultation Plat'AU porte l'identifiant du compte `platau` comme créateur.
