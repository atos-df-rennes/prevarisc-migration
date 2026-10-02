# MOD-18 — Alertes et notifications

## 1. Objet et périmètre

Le module Alertes et notifications permet d'informer par e-mail des utilisateurs habilités lorsqu'un changement significatif survient sur un établissement. Il couvre :

- La **configuration** des modèles de messages d'alerte par un administrateur.
- La **préparation** d'un e-mail d'alerte pré-rempli à partir d'un établissement ou d'un dossier.
- L'**envoi** de l'e-mail aux destinataires éligibles.
- Le **retrait manuel** de destinataires avant envoi.

Le module ne couvre pas les notifications internes à l'application (fil d'activité, tableau de bord). Il concerne exclusivement l'envoi d'e-mails sortants.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|---|---|
| **Administrateur** | Configure les modèles de messages d'alerte depuis l'espace d'administration. |
| **Préventionniste / Utilisateur métier** | Déclenche manuellement l'envoi d'une alerte depuis la fiche d'un établissement ou d'un dossier. Peut retirer des destinataires avant envoi et modifier le message. |
| **Destinataire de l'alerte** | Reçoit l'e-mail d'alerte. N'interagit pas avec l'application pour ce module. |

---

## 3. Déclenchement d'une alerte

### 3.1 Événements déclencheurs

L'application propose trois types d'alertes, chacun lié à un événement métier survenant sur un établissement :

| N° | Événement déclencheur | Objet de l'e-mail généré |
|---|---|---|
| 1 | **Changement de statut** (ouvert / fermé) | `Passage au statut "<nouveau statut>"` |
| 2 | **Changement d'avis** (favorable / défavorable / avis différé) | `Passage en avis "<nouvel avis>"` |
| 3 | **Changement de classement** (catégorie, type, activité) | `Changement de classement "<catégorie> - <type principal> <activité principale>"` |

L'objet de l'e-mail est automatiquement complété par le **libellé de l'établissement**, le nom de la **commune** et, si l'établissement possède un parent, le libellé du parent est préfixé.

**Format de l'objet :** `<libellé établissement> (<commune>) - <description du changement>`

Le déclenchement est **manuel** : lorsqu'un changement d'avis, statut ou classement survient sur un établissement, l'utilisateur reçoit un **lien « Alerter »** dans le message de succès de la sauvegarde. Ce lien permet d'ouvrir le formulaire d'alerte pour envoyer une notification aux utilisateurs habilités.

**Note sur le lien « Alerter »** : Le lien « Alerter » n'est pas un bouton permanent sur la fiche dossier ou établissement. Il apparaît exclusivement dans le **message flash de succès** affiché après la sauvegarde de l'entité lorsqu'un changement significatif est détecté (changement d'avis, statut ou classement). Cet accès contextuel assure que l'utilisateur est informé de la possibilité d'alerter au moment où c'est pertinent.

### 3.2 Destinataires automatiques

Les destinataires sont calculés automatiquement selon deux critères croisés :

1. **Droit d'alerte** — Le groupe de l'utilisateur autorise l'envoi du type d'alerte concerné :
   - Alerte de changement de statut.
   - Alerte de changement d'avis.
   - Alerte de changement de classement.

2. **Périmètre géographique** — L'utilisateur est rattaché à la même commune que l'établissement concerné. Ce rattachement est vérifié de deux façons (les résultats sont fusionnés) :
   - **Rattachement direct** : la commune d'affectation de l'utilisateur correspond à la commune de l'établissement.
   - **Rattachement par groupement** : l'utilisateur est membre d'un groupement de communes contenant la commune de l'établissement.

Seuls les utilisateurs possédant une **adresse e-mail renseignée et non vide** sont retenus.

---

## 4. Configuration des messages d'alerte

### 4.1 Balises dynamiques disponibles

Les modèles de messages d'alerte peuvent contenir des codes entre accolades. Lors de la préparation du courriel, ces codes sont remplacés par les informations correspondantes de l'établissement.

| Balise | Description |
|---|---|
| `{activitePrincipaleEtablissement}` | L'activité principale de l'établissement |
| `{categorieEtablissement}` | Le libellé complet de la catégorie effective (RG-ETB-062) : pour une cellule, celle du parent direct non supprimé ; valeur vide si ce parent n'a pas de catégorie |
| `{etablissementAvis}` | L'avis de l'établissement (voir règles de gestion) |
| `{etablissementLibelle}` | Le libellé de l'établissement |
| `{etablissementNumeroId}` | Le numéro d'identification de l'établissement |
| `{etablissementStatut}` | Le statut (Ouvert ou Fermé) de l'établissement |
| `{typePrincipalEtablissement}` | Le type principal de l'établissement |

### 4.2 Personnalisation du message

L'administrateur accède à l'écran de configuration via le menu **Administration > Messages des alertes**.

- Un **modèle de message** est défini pour chaque type d'alerte (statut, avis, classement).
- L'éditeur de texte prend en charge le **format riche** (gras, italique, listes, liens, etc.).
- L'administrateur peut consulter la **liste des informations pouvant être insérées dans le courriel**. Un tableau en présente les libellés.
- La sauvegarde met à jour simultanément **tous les modèles** affichés à l'écran.
- Un message de confirmation s'affiche en cas de succès ; un message d'erreur s'affiche en cas d'échec.

---

## 5. Fonctionnalités

### 5.1 Consulter les alertes configurées

**Accès :** Administration > Messages des alertes

**Description :** L'administrateur visualise la liste de tous les modèles de messages d'alerte existants. Chaque modèle affiche :

- Le **libellé du type d'alerte** (ex. : « Message de changement de statut »).
- Le **contenu actuel** du message dans un éditeur de texte riche.

L'administrateur peut modifier le contenu de chaque message et sauvegarder l'ensemble en une seule action.

### 5.2 Envoyer une alerte

**Accès :** Fiche d'un établissement ou fiche d'un dossier, bouton « Alerter »

**Étapes du parcours utilisateur :**

1. L'utilisateur clique sur le bouton « Alerter » en indiquant le type d'alerte concerné.
2. Une **fenêtre modale** s'ouvre et affiche :
   - La **liste des destinataires** (nom, prénom de chaque utilisateur éligible).
   - L'**objet** de l'e-mail, pré-rempli et modifiable.
   - Le **corps du message**, pré-rempli à partir du modèle configuré (balises déjà remplacées par les valeurs réelles), modifiable via un éditeur de texte riche.
3. L'utilisateur peut **retirer un destinataire** en cliquant sur le lien « Retirer » à côté de son nom. Le destinataire disparaît de la liste et son adresse est supprimée de l'envoi.
4. L'utilisateur clique sur « Envoyer ».
5. L'e-mail est envoyé à tous les destinataires restants.
6. La fenêtre modale se ferme automatiquement après l'envoi.

**Cas sans destinataire :**
Si aucun destinataire éligible n'est trouvé (ou si tous ont été retirés), un message d'information s'affiche :
> « Aucun destinataire pour l'envoi par mail de l'alerte. Pour y remédier, vérifier que les utilisateurs concernés ont les droits concernant les alertes par emails. »

Le bouton « Envoyer » est alors remplacé par « Fermer ».

---

## 6. Règles de gestion

| Référence | Règle |
|---|---|
| **RG-ALT-001** | Trois types d'alertes existent : changement de statut, changement d'avis, changement de classement. |
| **RG-ALT-002** | Le déclenchement d'une alerte est **manuel**. L'utilisateur initie l'envoi depuis la fiche de l'établissement ou du dossier. |
| **RG-ALT-003** | Les destinataires sont déterminés par les droits d'alerte de leur groupe et par le périmètre géographique (commune de l'établissement). |
| **RG-ALT-004** | Le périmètre géographique est vérifié par **rattachement direct** à la commune ou par **appartenance à un groupement** contenant cette commune. Les deux ensembles sont fusionnés (union). |
| **RG-ALT-005** | Seuls les utilisateurs disposant d'une **adresse e-mail renseignée et non vide** figurent parmi les destinataires. |
| **RG-ALT-006** | L'objet de l'e-mail suit le format : `<libellé établissement> (<commune>) - <description du changement>`. Si l'établissement a un parent, le libellé du parent est préfixé au libellé de l'établissement. |
| **RG-ALT-007** | Le corps du message est généré à partir du modèle configuré en administration. Toutes les **balises** sont remplacées par les données réelles de l'établissement. |
| **RG-ALT-008** | L'utilisateur peut **retirer manuellement** un ou plusieurs destinataires avant l'envoi. |
| **RG-ALT-009** | Si la liste des destinataires est **vide** (aucun éligible ou tous retirés), l'envoi est impossible et un message explicatif est affiché. |
| **RG-ALT-010** | Les destinataires ne voient pas les adresses des autres personnes destinataires du courriel. |
| **RG-ALT-012** | L'envoi d'e-mails est conditionné à l'**activation globale** du service de messagerie dans la configuration de l'application. Si le service est désactivé, aucun e-mail n'est envoyé. |
| **RG-ALT-013** | Le code `{etablissementAvis}` est remplacé par « Présence d'un dossier avec avis différé » si un tel dossier existe. Sinon, il reprend le libellé de l'avis disponible ; pour une cellule, la mention « à l'exploitation » est retirée. Si aucun avis n'est disponible, il est remplacé par « Avis d'exploitation indisponible ». |
| **RG-ALT-014** | L'objet et le message sont **modifiables** par l'utilisateur avant envoi. Les valeurs pré-remplies servent de proposition par défaut. |
| **RG-ALT-015** | La sauvegarde des modèles en administration met à jour **tous les modèles affichés** en une seule opération. |
| **RG-ALT-016** | L'accès à la configuration des modèles d'alerte est réservé au **périmètre d'administration** de l'application. |

---

## 7. Cas particuliers

| Cas | Comportement |
|---|---|
| **Établissement sans adresse** | La commune est vide dans l'objet de l'e-mail. Le périmètre géographique ne peut pas être évalué ; aucun destinataire n'est retourné. |
| **Établissement avec parent** | Le libellé du parent est préfixé au libellé de l'établissement dans l'objet de l'e-mail (ex. : « Centre commercial X Boutique Y (Rennes) - … »). |
| **Établissement de genre « cellule »** | La mention « à l'exploitation » est supprimée de l'avis. |
| **Avis différé** | Si un dossier avec avis différé existe sur l'établissement, l'avis affiché est « Présence d'un dossier avec avis différé », indépendamment de l'avis courant. |
| **Libellés d'avis configurés** | Le code `{etablissementAvis}` reprend le libellé de l'avis défini dans l'application. |
| **Aucun avis disponible** | La balise avis est résolue en « Avis d'exploitation indisponible ». |
| **Service de messagerie désactivé** | Aucun e-mail n'est envoyé. L'utilisateur reçoit un message d'erreur explicite dans une notification toast : « L'envoi d'email est désactivé dans la configuration ». La modale reste ouverte pour permettre une éventuelle correction. |
| **Erreur technique lors de l'envoi** | L'envoi retourne un résultat négatif. Un message d'erreur est affiché dans une notification toast. La modale reste ouverte pour permettre la réédition du message et une nouvelle tentative. |
| **Tous les destinataires retirés** | Le formulaire bascule en mode « aucun destinataire ». Le bouton « Envoyer » devient « Fermer ». L'utilisateur peut fermer et rouvrir la modale pour retrouver la liste initiale. |
| **Balise non résolvable** | Si une donnée de l'établissement est absente ou non renseignée, la balise est remplacée par une chaîne vide. |
