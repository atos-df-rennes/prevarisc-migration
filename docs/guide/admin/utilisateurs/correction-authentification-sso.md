# Corriger le mode d'authentification d'un compte

Ce guide s'adresse aux administrateurs Prevarisc et aux équipes qui déploient
la correction du mode SSO. Prévenez les administrateurs avant la mise à jour :
certains comptes peuvent nécessiter une correction après suppression des
fallbacks.

## Ce qui change

CAS reste prioritaire lorsqu'il est activé. Sinon, NTLM reste prioritaire si
l'identité Windows est fournie par le serveur. Dans ces deux cas, le compte
doit exister et être actif dans Prevarisc, indépendamment de sa case SSO.

Pour une connexion par le formulaire :

| Case « Connexion via LDAP/CAS/NTLM ? » | Vérification |
| --- | --- |
| Cochée, LDAP activé | LDAP uniquement |
| Cochée, LDAP désactivé | Connexion refusée |
| Décochée | Mot de passe local uniquement, même si LDAP est activé |

Un échec n'entraîne jamais d'essai sur l'autre mécanisme. Un identifiant ou un
mot de passe vide est refusé avant la recherche du compte et ne modifie aucun
compteur d'échecs. Le message affiché reste générique.

## Reprise automatique des comptes existants

La migration `Version20261118180352` applique les règles suivantes :

| Situation avant migration | Valeur SSO après migration |
| --- | --- |
| SSO NULL ou faux, login renseigné, mot de passe local NULL | Vrai |
| SSO NULL dans les autres cas | Faux |
| SSO déjà vrai | Conservé |
| SSO déjà faux dans les autres cas | Conservé |

Les fiches mairie sans login dont SSO était NULL restent donc non SSO. Elles
servent à porter des informations, pas à se connecter. Le mot de passe NULL
désigne l'absence de valeur en base, pas un mot de passe saisi vide.

La colonne SSO devient obligatoire avec la valeur faux par défaut. La migration
ne supprime ni ne modifie les mots de passe. Son retour arrière rend seulement
la colonne nullable : il ne reconstitue pas les anciennes valeurs NULL. Une
sauvegarde est nécessaire pour restaurer exactement les données antérieures.

Un mot de passe local présent ne prouve pas qu'un compte doit être local. La
reprise conserve ce classement présumé. En particulier, un compte historiquement
authentifié grâce au fallback LDAP peut maintenant échouer. Si ses mots de
passe local et LDAP sont identiques, il peut continuer à se connecter localement
sans produire d'échec, jusqu'à leur divergence. Une revue des comptes avec le
client permet de corriger aussi ces cas.

## Diagnostiquer un échec

En production, les traces de l'authentificateur de niveau `notice` et supérieur
sont conservées indépendamment des erreurs applicatives dans
`var/log/authentication-AAAA-MM-JJ.log` (rotation quotidienne, 30 fichiers).
En développement, elles figurent dans `var/log/dev-AAAA-MM-JJ.log`.

| Trace | Vérification à effectuer |
| --- | --- |
| `Auth BDD: échec sans repli LDAP`, avec `ldap_enabled: true` | Vérifier si le compte est réellement local ou doit être SSO |
| `Auth BDD: compte local sans mot de passe` | Définir un mot de passe local ou corriger le mode attendu |
| `Auth LDAP: échec sans repli local` | Vérifier le compte LDAP, ses identifiants et la disponibilité/configuration de l'annuaire |
| `Auth: utilisateur SSO ... tentant une connexion par formulaire` | Vérifier que le mécanisme délégué attendu est activé |
| `Auth formulaire: identifiant ou mot de passe vide` | Faire renseigner les deux champs |

Les journaux contiennent le login et le mécanisme concerné, jamais le mot de
passe ni son hachage. Leur accès doit rester réservé aux personnes habilitées.
Un échec BDD ne prouve pas que le mot de passe serait accepté sur LDAP :
**confirmer le mode attendu avant de modifier le compte**. Vérifier également
que le compte est actif et n'est pas bloqué par la protection anti-brute force.

## Procédure de correction par un administrateur

Pour un compte qui doit utiliser LDAP :

1. Confirmer avec le client que le compte relève bien de l'annuaire et que
   LDAP est activé sur l'instance.
2. Ouvrir le compte dans le panneau d'administration des utilisateurs.
3. Cocher « Connexion via LDAP/CAS/NTLM ? » puis enregistrer. L'enregistrement
   supprime le mot de passe local ; l'ancien mot de passe ne sera plus utilisable.
4. Si nécessaire, débloquer le compte avec le bouton prévu à cet effet.
5. Faire essayer une nouvelle connexion par formulaire avec les identifiants
   LDAP. Vérifier qu'un mot de passe incorrect est refusé sans recours à la BDD.

Pour un compte qui doit utiliser un mot de passe local, décocher la case,
définir un nouveau mot de passe puis enregistrer et vérifier la connexion.
La case n'apparaît que si un mécanisme délégué est activé ; si celui-ci a été
désactivé avant la correction d'un compte SSO, faire intervenir l'équipe
d'exploitation pour préparer la remise en état.

Ne pas réactiver de fallback pour contourner un échec : corriger le compte ou
la configuration du mécanisme sélectionné. Conserver un accès administrateur
opérationnel pendant la bascule et vérifier des comptes représentatifs de
chaque mode utilisé par le client.
