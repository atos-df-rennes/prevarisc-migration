# MOD-12 — Tableau de bord

## 1. Objet et périmètre

Le tableau de bord est la page d'accueil de l'application Prevarisc. Il fournit à chaque utilisateur authentifié une vue synthétique et personnalisable de son activité : commissions à venir, établissements à surveiller, dossiers en attente d'action, messages internes et état de la connexion à Plat'AU.

Chaque utilisateur voit uniquement les blocs d'information auxquels son profil lui donne accès. Les blocs sont disposés sous forme de panneaux réorganisables par glisser-déposer. Leur contenu est chargé à la demande lorsque l'utilisateur déplie un panneau.

---

## 2. Acteurs concernés

| Acteur | Description |
|--------|-------------|
| Préventionniste | Utilisateur principal. Consulte les indicateurs relatifs à ses établissements suivis, dossiers et commissions. |
| Responsable de groupement | Dispose d'une vision élargie sur les établissements sans préventionniste et sous avis défavorable. |
| Agent communal | Voit les établissements de sa commune sous avis défavorable. |
| Administrateur | Accès complet à l'ensemble des blocs. Peut publier des messages et supprimer ceux dont il est l'auteur. |
| Tout utilisateur authentifié | Accède au fil de messages et peut personnaliser l'ordre des blocs. |

---

## 3. Blocs d'information disponibles

### 3.1 Blocs liés aux commissions

#### 3.1.1 Prochaines commissions (vue résumée)

- **Contenu** : Liste des commissions planifiées dans les prochains jours (par défaut 15 jours, configurable). Chaque commission affiche :
  - le libellé de la commission et le libellé de la date
  - la date (avec mention « Aujourd'hui », « Demain » ou jour de la semaine)
  - l'horaire de début et de fin
  - le type d'événement : « En salle », « Visite de sécurité » ou « Groupe de visite »
  - le ratio dossiers verrouillés / total de l'ordre du jour
- **Condition d'affichage** : L'utilisateur doit avoir le droit de consulter les prochaines commissions.
- **Périmètre des données** : Seules les commissions rattachées à l'utilisateur sont affichées.

#### 3.1.2 Prochaines commissions — Ordre du jour détaillé

- **Contenu** : Pour chaque commission à venir, affiche la liste complète des dossiers inscrits à l'ordre du jour (dossiers affectés et non affectés). Chaque dossier est présenté avec son résumé standard. Si aucune commission n'a de dossier, le message « Pas de dossier affecté » est indiqué.
- **Condition d'affichage** : L'utilisateur doit avoir le droit de consulter l'ordre du jour des prochaines commissions.
- **Périmètre des données** : Commissions rattachées à l'utilisateur, sur le même horizon temporel configurable.
- **Taille** : Ce bloc occupe une largeur double par défaut.

### 3.2 Blocs liés aux établissements

#### 3.2.1 Établissements suivis

- **Contenu** : Liste des établissements suivis par l'utilisateur connecté, répondant aux critères suivants :
  - ERP de 1ère à 4e catégorie, ouverts ou en projet, avec périodicité de visite définie
  - ERP de 5e catégorie sous avis défavorable, ouverts ou en projet, avec périodicité
  - ERP de 5e catégorie avec local à sommeil, ouverts ou en projet, avec périodicité
  - Bâtiments d'utilité publique (BUP), IGH, habitations et autres genres, ouverts ou en projet, avec périodicité
- **Condition d'affichage** : Droit de voir les établissements suivis.
- **Taille** : Largeur moyenne.

#### 3.2.2 Établissements sous avis défavorable

- **Contenu** : Tous les ERP ouverts ayant un avis défavorable en vigueur, tous services confondus.
- **Condition d'affichage** : Droit de voir les établissements sous avis défavorable.

#### 3.2.3 Établissements suivis sous avis défavorable

- **Contenu** : Sous-ensemble du bloc précédent, filtré sur les seuls établissements suivis par l'utilisateur connecté.
- **Condition d'affichage** : Droit de voir les établissements suivis sous avis défavorable.

#### 3.2.4 Établissements de la commune sous avis défavorable

- **Contenu** : ERP ouverts sous avis défavorable situés sur la commune de rattachement de l'utilisateur.
- **Condition d'affichage** : Droit dédié. Si l'utilisateur n'a pas de commune de rattachement, le bloc affiche zéro résultat.

#### 3.2.5 Établissements sans préventionniste

- **Contenu** : Liste des ERP qui n'ont aucun préventionniste affecté.
- **Condition d'affichage** : Droit de voir les établissements sans préventionniste.

#### 3.2.6 Établissements sans prochaine visite périodique cette année

- **Contenu** : ERP ouverts dont la date de la prochaine visite périodique théorique (calculée à partir de la dernière visite et de la périodicité) est dépassée par rapport au mois en cours. Seuls les établissements rattachés aux commissions de l'utilisateur sont comptés.
- **Condition d'affichage** : Droit de voir les ERP ouverts sans prochaine visite périodique.

### 3.3 Blocs liés aux dossiers

#### 3.3.1 Dossiers suivis sans avis du rapporteur

- **Contenu** : Dossiers affectés à l'utilisateur pour lesquels :
  - les études n'ont reçu ni avis du rapporteur ni avis de commission, ou
  - les courriers n'ont pas de date de réponse.
- **Condition d'affichage** : Droit de voir les dossiers suivis sans avis.
- **Tri** : Par date de création, du plus récent au plus ancien.

#### 3.3.2 Dossiers suivis non verrouillés

- **Contenu** : Dossiers affectés à l'utilisateur dont le statut de verrouillage est « non verrouillé ».
- **Condition d'affichage** : Droit de voir les dossiers suivis non verrouillés.
- **Tri** : Par date de visite (ou date de création si absente), du plus récent au plus ancien.

#### 3.3.3 Dossiers sans avis de commission

- **Contenu** : Dossiers dont la date de passage en commission est échue depuis un nombre de jours configurable (par défaut 15 jours) et qui n'ont toujours pas d'avis de commission. Seules les commissions rattachées à l'utilisateur sont prises en compte.
- **Condition d'affichage** : Droit de voir les dossiers sans avis de commission.

#### 3.3.4 Dossiers avec avis différés

- **Contenu** : Dossiers associés aux commissions de l'utilisateur dont l'avis a été reporté (avis différé).
- **Condition d'affichage** : Droit de voir les dossiers avec avis différé.

#### 3.3.5 Courriers sans réponse

- **Contenu** : Dossiers de type « courrier » n'ayant pas reçu de réponse dans un délai configurable (par défaut 10 jours).
- **Condition d'affichage** : Droit de voir les courriers sans réponse.

#### 3.3.6 Dossiers avec une date de levée des prescriptions

- **Contenu** : Dossiers ayant un délai de prescription défini, sauf ceux déjà liés à un dossier de nature « levée d'avis défavorable », « levée de prescriptions » ou « échéancier de travaux ».
- **Condition d'affichage** : Droit de voir les dossiers en levée de prescriptions.

#### 3.3.7 Dossiers ayant une absence de quorum

- **Contenu** : Dossiers marqués « absence de quorum », sauf ceux liés à un dossier de type « visite » ou « courrier ».
- **Condition d'affichage** : Droit de voir les dossiers avec absence de quorum.

#### 3.3.8 Dossiers avec statut « ne peut se prononcer »

- **Contenu** : Dossiers marqués « ne peut se prononcer » (NPSP), sauf ceux liés à un dossier de type « visite » ou « courrier ».
- **Condition d'affichage** : Droit de voir les dossiers NPSP.

#### 3.3.9 Dossiers incomplets

- **Contenu** : Dossiers marqués comme incomplets.
- **Condition d'affichage** : Droit de voir les dossiers incomplets.
- **Tri** : Par date de création, du plus récent au plus ancien.

### 3.4 Blocs liés à Plat'AU (conditionnels)

Ces trois blocs n'apparaissent que si la fonctionnalité Plat'AU est activée dans la configuration de l'instance.

#### 3.4.1 Dossiers Plat'AU à traiter

- **Contenu** : Dossiers issus de Plat'AU qui ne sont pas encore rattachés à un établissement dans Prevarisc. Chaque dossier est marqué :
  - « nouveau » s'il a été notifié depuis la dernière visite de l'utilisateur sur ce bloc
  - « nouvelle pièce jointe » si des pièces jointes ont été notifiées depuis la dernière visite
- **Condition d'affichage** : Plat'AU activé et droit de voir les dossiers Plat'AU sans établissement.
- **Effet de bord** : L'ouverture de ce bloc met à jour la date de dernière consultation, servant de référence pour les indicateurs « nouveau ».

#### 3.4.2 Dossiers Plat'AU avec des pièces jointes non envoyées

- **Contenu** : Dossiers Plat'AU ayant déjà un avis traité mais comportant des pièces jointes en erreur d'envoi ou en attente de traitement (statuts « à exporter », « en erreur », « en attente de statut »).
- **Condition d'affichage** : Plat'AU activé et droit dédié.

#### 3.4.3 Dossiers Plat'AU avec de nouvelles pièces jointes

- **Contenu** : Dossiers Plat'AU déjà rattachés à un établissement dont des pièces jointes ont été notifiées depuis la dernière visite de l'utilisateur sur la page des pièces du dossier.
- **Condition d'affichage** : Plat'AU activé et droit dédié.

### 3.5 Fil de messages

- **Contenu** : Messages publiés par les utilisateurs, filtrés selon le groupe de l'utilisateur connecté. Chaque message affiche l'avatar de l'auteur, son prénom et nom, la date (heure seule si aujourd'hui, jour et heure sinon), le type du message et un lien de suppression.
- **Condition d'affichage** : Aucun droit spécifique requis ; tous les utilisateurs authentifiés y accèdent.
- **Compteur** : Le nombre total de messages visibles est affiché dans l'en-tête du bloc.
- **Limite** : Les 5 derniers messages sont affichés par défaut.
- **Bouton d'action** : Un bouton « Ajouter un message » est présent dans l'en-tête du bloc.

---

## 4. Personnalisation du tableau de bord

### 4.1 Réorganisation des blocs

L'utilisateur peut réorganiser les blocs en les faisant glisser par leur barre de titre. La nouvelle disposition est enregistrée automatiquement dans les préférences de l'utilisateur une seconde après la fin du déplacement.

### 4.2 Réinitialisation de l'ordre

Un bouton « Réinitialiser » permet de revenir à l'ordre par défaut des blocs. L'action supprime la préférence d'ordre personnalisé et recharge la page.

### 4.3 Dépliage / repliage des blocs

Chaque bloc est replié par défaut à l'arrivée sur le tableau de bord. Un clic sur l'icône de chevron dans la barre de titre déplie le bloc et déclenche le chargement de son contenu. Un second clic replie le bloc sans perdre les données déjà chargées.

### 4.4 Ajout de nouveaux blocs

Lorsqu'un nouveau type de bloc est ajouté par l'administration, il apparaît automatiquement en fin de liste pour les utilisateurs ayant déjà personnalisé leur ordre, sans perturber l'agencement existant.

---

## 5. Fil de messages

### 5.1 Consulter les messages

L'utilisateur consulte les messages en dépliant le bloc « Messages ». Les messages sont triés du plus récent au plus ancien. Chaque message affiche :

- l'avatar de l'auteur
- le prénom et le nom de l'auteur
- la date et l'heure de publication (heure seule si le message date d'aujourd'hui)
- le type du message (« Message de service », « Informations », « Message personnel », « Autre »)
- le texte du message, dans lequel les adresses de sites Internet deviennent cliquables

Si aucun message n'est disponible pour le groupe de l'utilisateur, le texte « Aucun message disponible » est affiché.

### 5.2 Publier un message

L'utilisateur clique sur « Ajouter un message » dans le bloc Messages. Une fenêtre modale s'ouvre avec :

1. **Zone de texte** : Champ libre pour saisir le message d'information.
2. **Confidentialité** : Liste de cases à cocher correspondant aux groupes d'utilisateurs. Tous les groupes sont cochés par défaut. L'utilisateur décoche les groupes qui ne doivent pas recevoir le message.
3. **Type du message** : Liste déroulante avec les valeurs :
   - Message de service
   - Informations
   - Message personnel
   - Autre

À la validation :
- Le message est enregistré et associé à l'identité de l'auteur.
- Les groupes cochés sont enregistrés comme destinataires.
- L'utilisateur est redirigé vers le tableau de bord avec un message de confirmation « Message ajouté ! ».
- En cas d'erreur, un message d'alerte est affiché avec le détail de l'erreur.

### 5.3 Supprimer un message

Chaque message visible comporte un lien « Supprimer », quel que soit son auteur. La présence de ce lien ne signifie pas que tout utilisateur peut supprimer le message : lors de la demande, le serveur vérifie l'auteur associé au message. Si celui-ci est différent de l'utilisateur connecté, la suppression est refusée. L'auteur peut supprimer son message sans confirmation ; il est ensuite redirigé vers le tableau de bord avec un message de confirmation.

**Limite technique constatée :** si un message n'a pas d'utilisateur auteur associé, le contrôle de propriété est ignoré et la suppression n'est pas restreinte à un auteur. Ce cas concerne les données sans auteur associé ; il ne constitue pas une règle d'accès souhaitée.

En cas d'erreur technique interceptée pendant la suppression, l'utilisateur est redirigé vers le tableau de bord avec un message d'erreur. Une demande refusée parce que l'utilisateur n'est pas l'auteur retourne une erreur d'accès et ne suit pas ce parcours de confirmation.

---

## 6. Indicateur de connectivité Plat'AU

À l'ouverture du tableau de bord, si Plat'AU est activé et que la vérification automatique n'est pas désactivée, une vérification de la connectivité est lancée en arrière-plan.

### 6.1 Déroulement

1. Pendant la vérification, le message « Vérification de la connexion à Plat'AU » est affiché accompagné d'un indicateur de chargement.
2. Prevarisc vérifie la disponibilité de Plat'AU.

### 6.2 Résultats possibles

Le résultat est affiché sous forme de notification toast, et non d'un bandeau d'alerte :

| Résultat | Notification visible |
|----------|----------------------|
| Connexion réussie | Toast de succès confirmant la connexion ; la version, le modèle et la nomenclature de Plat'AU sont ajoutés lorsqu'ils sont disponibles. |
| Erreur côté Prevarisc | Toast d'erreur expliquant la cause connue, par exemple un problème de configuration ou de jeton PISTE, ou une réponse Plat'AU impossible à récupérer ou à lire. Le message peut renvoyer à la consultation des journaux de l'application. |
| Plat'AU répond avec une erreur ou un état dégradé | Toast d'erreur précisant, selon le cas, le code HTTP ou l'état dégradé, et invitant à réessayer ultérieurement. |
| Délai d'attente de la requête dépassé | Toast d'erreur indiquant que le service est indisponible ou lent. Une autre erreur de requête affiche un message générique d'échec de vérification. |

Si la réponse contient des informations de maintenance, un toast d'avertissement distinct est également affiché. La réponse technique distingue une erreur côté Prevarisc d'une erreur côté Plat'AU ; l'utilisateur voit le message contextualisé, tandis que les détails techniques sont consignés dans les journaux de l'application.

### 6.3 Conditions de non-affichage

L'indicateur ne s'affiche pas si :
- Plat'AU est désactivé dans la configuration de l'instance.
- La vérification automatique de santé est explicitement désactivée dans la configuration.

---

## 7. Règles de gestion

| N° | Règle |
|----|-------|
| RG-TDB-001 | Chaque bloc n'est affiché que si l'utilisateur dispose du droit d'accès correspondant. Le bloc « Messages » est l'exception : il est visible par tous les utilisateurs authentifiés. |
| RG-TDB-002 | Le contenu d'un bloc n'est chargé que lorsque l'utilisateur le déplie. Tant qu'un bloc est replié, seul le compteur (nombre d'éléments) est affiché dans son en-tête. |
| RG-TDB-003 | Le compteur de chaque bloc est calculé au chargement initial du tableau de bord, pour tous les blocs visibles par l'utilisateur. |
| RG-TDB-004 | L'ordre d'affichage des blocs est déterminé par les préférences de l'utilisateur. En l'absence de préférences, l'ordre par défaut est appliqué. |
| RG-TDB-005 | Lorsqu'un nouveau bloc est ajouté au catalogue et que l'utilisateur possède un ordre personnalisé, le nouveau bloc est automatiquement ajouté en dernière position. |
| RG-TDB-006 | Le réarrangement par glisser-déposer est enregistré automatiquement dans les préférences de l'utilisateur après un délai d'une seconde sans nouveau déplacement, à condition que tous les blocs aient fini de charger. |
| RG-TDB-007 | La réinitialisation de l'ordre supprime la préférence personnalisée et provoque un rechargement complet de la page. |
| RG-TDB-008 | L'horizon temporel des commissions à venir est de 15 jours par défaut. Cette valeur est configurable par l'administrateur de l'instance. |
| RG-TDB-009 | Le délai de référence pour les dossiers sans avis de commission est de 15 jours par défaut. Cette valeur est configurable par l'administrateur de l'instance. |
| RG-TDB-010 | Le délai de référence pour les courriers sans réponse est de 10 jours par défaut. Cette valeur est configurable par l'administrateur de l'instance. |
| RG-TDB-011 | Les messages du fil sont filtrés par le groupe d'appartenance de l'utilisateur connecté. Un utilisateur ne voit que les messages destinés à son groupe. |
| RG-TDB-012 | Lors de la publication d'un message, tous les groupes sont cochés par défaut. L'auteur peut restreindre la diffusion en décochant des groupes. |
| RG-TDB-013 | Les 5 derniers messages sont affichés dans le bloc Messages. |
| RG-TDB-014 | Les adresses de sites Internet présentes dans un message sont automatiquement transformées en liens cliquables. |
| RG-TDB-015 | La suppression d'un message associé à un auteur est immédiate et sans confirmation, et seul cet auteur est autorisé à la réaliser. Le lien de suppression reste visible pour les autres utilisateurs qui voient le message, mais leur demande est refusée. Limite technique : si aucun auteur n'est associé au message, le contrôle de propriété est ignoré. |
| RG-TDB-016 | La vérification de connectivité Plat'AU est exécutée automatiquement au chargement du tableau de bord si Plat'AU est activé et la vérification n'est pas désactivée. |
| RG-TDB-017 | L'origine de l'erreur de connectivité Plat'AU est distinguée entre Prevarisc (configuration locale) et Plat'AU (service distant). |
| RG-TDB-018 | Les trois blocs Plat'AU (dossiers à traiter, pièces jointes en erreur, nouvelles pièces jointes) ne sont disponibles que si Plat'AU est activé dans la configuration de l'instance. |
| RG-TDB-019 | L'ouverture du bloc « Dossiers Plat'AU à traiter » enregistre la date de dernière visite de l'utilisateur. Cette date sert de référence pour marquer les nouveaux dossiers et les nouvelles pièces jointes dans ce bloc. |
| RG-TDB-020 | Les blocs « Dossiers avec levée de prescriptions », « Absence de quorum » et « Ne peut se prononcer » excluent les dossiers déjà liés à un dossier correctif (respectivement : levée d'avis défavorable / levée de prescriptions / échéancier de travaux pour les prescriptions ; dossier de type visite ou courrier pour le quorum et le NPSP). |
| RG-TDB-021 | Le bloc « Établissements de la commune sous avis défavorable » renvoie zéro résultat si l'utilisateur n'a pas de commune de rattachement. |
| RG-TDB-022 | Les blocs liés aux commissions et aux dossiers sans avis ne prennent en compte que les commissions auxquelles l'utilisateur est rattaché. |
| RG-TDB-023 | L'horodatage d'un message est affiché sous forme d'heure seule si le message date du jour, ou sous forme « Jour JJ Mois HH:MM » sinon. |

---

## 8. Cas particuliers

| Cas | Comportement attendu |
|-----|---------------------|
| Utilisateur sans commission rattachée | Les blocs liés aux commissions et les dossiers sans avis de commission affichent un compteur à 0 et une liste vide. |
| Utilisateur sans commune de rattachement | Le bloc « Établissements de la commune sous avis défavorable » affiche un compteur à 0. |
| Instance sans Plat'AU | Les trois blocs Plat'AU et l'indicateur de connectivité ne sont pas générés du tout. La page ne contient aucune référence à Plat'AU. |
| Plat'AU activé mais vérification de santé désactivée | Les blocs Plat'AU sont présents mais l'indicateur de connectivité en haut de page n'apparaît pas. |
| Aucun message dans le fil | Le bloc Messages affiche le texte « Aucun message disponible. » |
| Aucun dossier dans un bloc dossiers | Le bloc affiche le texte « Aucun dossier. » |
| Aucun établissement dans un bloc établissements | Le bloc affiche le texte « Aucun établissement. » |
| Aucune commission à venir | Le bloc affiche le texte « Aucune prochaine commission. » |
| Premier accès (pas de préférences d'ordre) | L'ordre par défaut du catalogue de blocs est appliqué. |
| Nouveau bloc ajouté après personnalisation | Le bloc apparaît en fin de liste sans altérer l'ordre existant (cf. RG-TDB-005). |
| Message avec URL dans le texte | Les URL sont rendues cliquables automatiquement (cf. RG-TDB-014). |
| Erreur lors de l'ajout d'un message | Un bandeau d'alerte rouge apparaît avec le message d'erreur. L'utilisateur est redirigé vers le tableau de bord. |
| Erreur technique interceptée lors de la suppression d'un message | Un bandeau d'alerte rouge apparaît avec le message d'erreur et l'utilisateur est redirigé vers le tableau de bord. Une tentative par un utilisateur autre que l'auteur associé est refusée par une erreur d'accès, sans suppression. |
