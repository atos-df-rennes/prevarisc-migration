# MOD-21 — Administration

## 1. Objet et périmètre

Le panneau d'administration présente des informations sur l'application et certains réglages généraux. Il est consultable en lecture seule : les réglages ne peuvent pas être modifiés depuis cette page.

## 2. Acteurs concernés

| Acteur | Rôle |
|---|---|
| Administrateur | Consulte les informations et réglages présentés dans ce panneau. |
| Responsable applicatif | Consulte la version de l'application et les informations de disponibilité d'une mise à jour. |

Seuls les utilisateurs disposant des droits d'administration peuvent ouvrir ce panneau.

## 3. Informations affichées

Le panneau présente notamment :

- La version de l'application et, lorsqu'elle est disponible, une indication de mise à jour.
- Les informations de configuration des services cartographiques.
- Le mode de connexion des utilisateurs et, lorsque nécessaire, les coordonnées du service de connexion.
- L'état de l'intégration avec Plat'AU.
- Les réglages utilisés pour calculer les échéances de visite et le nombre maximal de dossiers présentés dans les listes.
- Les utilisateurs actuellement connectés, avec leur nom, leur groupe et leur fonction.
- L'activation de certains services complémentaires.

## 4. Règles de gestion

| Référence | Règle |
|---|---|
| **RG-ADM-001** | La version de l'application est affichée dans le panneau d'administration. |
| **RG-ADM-002** | Une indication est affichée lorsqu'une mise à jour de l'application est disponible. |
| **RG-ADM-005** | Si la disponibilité d'une mise à jour ne peut pas être vérifiée, aucun message d'erreur ni indication de mise à jour n'est affiché. |
| **RG-ADM-009** | Un service cartographique sans adresse de service configurée est indiqué comme désactivé. |
| **RG-ADM-020** | Un utilisateur est considéré comme connecté si son activité est suffisamment récente au regard de la durée de connexion autorisée. |
| **RG-ADM-022** | Les utilisateurs connectés sont présentés avec leur identifiant, leur nom, leur groupe et leur fonction. |
| **RG-ADM-023** | Si aucun utilisateur n'est connecté, le panneau indique qu'il n'y a aucun résultat. |
| **RG-ADM-030** | Le panneau indique si les échéances sont calculées à partir de la date de visite ou de la date de commission. |
| **RG-ADM-031** | Le panneau indique si les délais sont exprimés en mois ou en années. |
| **RG-ADM-032** | L'intégration avec Plat'AU est indiquée comme activée ou désactivée. |
| **RG-ADM-033** | Le panneau indique le nombre maximal de dossiers affichés dans les listes. |
| **RG-ADM-034** | L'accès au panneau est réservé aux utilisateurs disposant des droits d'administration. |
| **RG-ADM-035** | Le panneau permet uniquement de consulter les informations ; il ne permet pas de modifier les réglages. |
