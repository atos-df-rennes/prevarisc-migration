# MOD-02 — Dossiers

## 1. Objet et périmètre

Un **dossier** est l'unité de travail centrale du module de prévention incendie dans Prevarisc. Il matérialise toute action administrative instruite par un SDIS concernant un ou plusieurs établissements recevant du public (ERP), installations classées (ICPE) ou chapiteaux/tentes/structures (CTS).

On distingue deux grandes familles de dossiers :

- **Étude** : instruction d'une demande administrative (permis de construire, autorisation de travaux, dérogation, etc.). L'étude aboutit à un avis du préventionniste, éventuellement confirmé ou modifié par la commission de sécurité.
- **Visite** : constatation sur le terrain (réception de travaux, visite périodique, visite inopinée, etc.). La visite peut donner lieu à un avis de la commission et génère des prescriptions.

Un troisième type, le **groupe de visite**, permet de traiter plusieurs établissements lors d'une même opération de visite (cas typique des centres commerciaux).

Au-delà de ces trois types principaux, l'application gère également des dossiers de type **courrier/courriel**, **réunion**, **intervention** et **arrêté**, qui complètent le suivi administratif d'un établissement.

Le dossier porte l'ensemble des informations d'instruction : descriptif, prescriptions, textes applicables, documents consultés, avis rendu, affectation en commission, pièces jointes et contacts associés.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|---|---|
| **Préventionniste** | Crée, instruit et renseigne les dossiers. Rédige les prescriptions et émet un avis technique. |
| **Secrétariat** | Enregistre les dates de réception (mairie, secrétariat, SDIS), gère les documents manquants et la complétude du dossier. |
| **Chef de groupement** | Supervise les dossiers, peut verrouiller/déverrouiller, modifier l'avis de la commission selon ses droits. |
| **Commission de sécurité** | Examine les dossiers qui lui sont affectés et rend un avis (favorable, défavorable, etc.). |
| **Service instructeur** | Commune ou groupement de communes qui a transmis la demande. |
| **Demandeur / Pétitionnaire** | Personne physique ou morale à l'origine de la demande (exploitant, propriétaire, maître d'ouvrage, etc.). |

---

## 3. Caractérisation d'un dossier

### 3.1 Type de dossier

Chaque dossier appartient à l'un des types suivants :

| Type | Description |
|---|---|
| **Étude** | Instruction d'une demande administrative liée à un ERP (permis, autorisation, dérogation, etc.). |
| **Visite** | Constatation sur site pour un seul établissement (périodique, réception de travaux, inopinée, etc.). |
| **Groupe de visite** | Visite portant sur plusieurs établissements simultanément (ex. : commission de visite d'un centre commercial). |
| **Courrier / Courriel** | Correspondance entrante ou sortante (lettre, avis écrit motivé, mise en demeure, demande de visite, etc.). |
| **Réunion** | Compte-rendu d'une réunion (en locaux SDIS, à l'extérieur ou téléphonique). |
| **Intervention** | Rapport d'intervention opérationnelle (incendie, SAP, intervention diverse). |
| **Arrêté** | Acte administratif (ouverture, fermeture, mise en demeure, utilisation exceptionnelle de locaux, courrier). |

Le type détermine le formulaire de saisie affiché et les champs disponibles.

### 3.2 Nature du dossier

Chaque type se décline en **natures** qui précisent le motif du dossier. Les natures sont ordonnées et filtrées selon le type sélectionné. Voici les principales natures identifiées :

**Natures d'étude :**
- Permis de construire (PC)
- Autorisation de travaux (AT)
- Dérogation
- Cahier des charges fonctionnel du SSI
- Cahier des charges de type T
- Salon de type T
- Levée de prescriptions
- Documents divers
- Changement de directeur unique de sécurité (DUS)
- Suivi d'organisme de formation SSIAP
- Demande de registre de sécurité CTS
- Demande d'implantation CTS (courte ou longue durée)
- Permis d'aménager
- Permis de démolir
- Compte-rendu de visite d'organismes d'inspection
- Étude suite à un avis « ne se prononce pas »
- Utilisation exceptionnelle de locaux
- Levée d'avis défavorable (levée de réserves)
- Échéancier de travaux
- Déclaration préalable
- Diagnostic de sécurité (RVRMD)
- Autorisation d'une ICPE
- Certificat d'urbanisme (CU)
- Demande d'organisation de manifestation temporaire
- Déclassement / Reclassement

**Natures de visite :**
- Réception de travaux
- Avant ouverture
- Visite périodique
- Visite de chantier
- Visite de contrôle
- Visite inopinée
- Visite conseil

**Natures de groupe de visite :**
Les mêmes que les visites (réception de travaux, avant ouverture, périodique, chantier, contrôle, inopinée) mais concernant plusieurs établissements.

**Natures de courrier/courriel :**
- Avis écrit motivé
- Lettre
- Consultation PLU
- Demande de renseignements
- Mise en demeure
- Rapport d'organisme agréé
- Demande de visite hors programme
- Demande de visite périodique
- Demande de visite technique
- Demande de visite inopinée
- Demande de visite de réception
- Autorisation de travaux

**Natures de réunion :** Locaux SDIS, extérieur SDIS, téléphonique.

**Natures d'intervention :** Incendie, SAP (secours à personne), intervention diverse.

**Natures d'arrêté :** Ouverture, fermeture, mise en demeure, utilisation exceptionnelle, courrier.

La nature détermine précisément les champs affichés dans le formulaire d'instruction (voir RG-DOS-003).

### 3.3 Établissement(s) concerné(s)

Un dossier est rattaché à un ou plusieurs établissements :

- **Cas standard (étude, visite)** : le dossier est lié à un seul établissement, renseigné à la création.
- **Cas du groupe de visite** : le dossier peut concerner plusieurs établissements (ex. : les cellules d'un centre commercial). Des établissements peuvent être ajoutés ou retirés après la création.
- **Recherche d'établissement** : un champ d'autocomplétion permet de rechercher un établissement par son libellé. Le genre de l'établissement (établissement, cellule) est pris en compte.

Lorsqu'un établissement est retiré d'un dossier qui lui donnait avis, le système recalcule automatiquement le dernier dossier donnant avis pour cet établissement.

### 3.4 Affectation des préventionnistes

Un ou plusieurs préventionnistes peuvent être affectés à un dossier. L'affectation se fait via un champ d'autocomplétion qui recherche les utilisateurs ayant la fonction de préventionniste.

À la création du dossier, le système propose les préventionnistes déjà affectés à l'établissement concerné.

Les préventionnistes affectés sont enregistrés lors de chaque sauvegarde. La liste complète est remplacée à chaque mise à jour (suppression puis réinsertion).

### 3.5 Données de l'instruction

Selon la nature du dossier, différents champs d'instruction sont disponibles :

**Dates clés :**
- Date de création du dossier
- Date de dépôt en mairie
- Date de dépôt au secrétariat
- Date de réception au SDIS
- Date de passage en commission
- Date de visite (pour les visites et groupes de visite)
- Date de réponse
- Date de la préfecture
- Date d'envoi en transit
- Date de transfert à la commission compétente
- Date de réception par la commission compétente
- Date de réception du RVRAT (rapport de vérification réglementaire après travaux)
- Date de levée des prescriptions (délai prescriptions)
- Date d'échéancier de travaux
- Date de signature (pour les arrêtés)
- Date et heure d'intervention (pour les interventions)
- Date de réunion
- Date du dossier incomplet

**Informations générales :**
- Objet du dossier
- Numéro chronologique
- Numéro(s) de document d'urbanisme (un dossier peut avoir plusieurs numéros)
- Commission compétente
- Service instructeur (groupement de communes ou commune)

Le service instructeur est renseigné dans un sélecteur unique proposant des groupements et des communes par autocomplétion, ainsi que la saisie libre. La sélection d'une suggestion enregistre son libellé et son type (commune ou groupement), sans champ de type visible supplémentaire. Deux suggestions homonymes de types différents restent distinctes. Une saisie libre enregistre seulement le libellé ; son type reste vide. L'effacement du service efface aussi son type.

En modification, le libellé et le type existants sont conservés tant que la sélection n'est pas modifiée. Aucun type manquant n'est déduit automatiquement du libellé : pour le compléter, l'utilisateur doit resélectionner une suggestion puis sauvegarder le dossier.
- Descriptif général
- Descriptif libre (champ de texte riche)
- Observations

**Informations spécifiques aux visites :**
- Coordination SSI
- Date de réception du RVRAT
- Date limite de levée des prescriptions

**Informations spécifiques aux dérogations :**
- Justification de la dérogation
- Mesures compensatoires
- Mesures complémentaires
- Règle dérogatoire

**Informations spécifiques aux interventions :**
- Numéro d'intervention
- Opération SDIS
- RCCI (recherche des causes et circonstances d'incendie)
- REX (retour d'expérience)
- Durée d'intervention

**Informations spécifiques aux courriers :**
- Lieu de réunion (réunions extérieures)

**Indicateurs booléens :**
- Dossier incomplet
- Hors délai
- Absence de quorum
- Ne peut se prononcer (NPSP)
- Avis différé
- Charge de sécurité (pour salons type T)
- CNE (commission nationale d'examen)

**Documents manquants :**
Lorsqu'un dossier est marqué incomplet, une liste de documents manquants peut être renseignée. Chaque document manquant comporte un libellé, une date d'envoi de la demande et une date de réception.

**Documents consultés :**
La liste des documents consultés varie selon le type et la nature du dossier (documents d'étude ou documents de visite). Chaque document consultable possède un libellé, une référence et une date. L'utilisateur peut également ajouter des documents supplémentaires non prévus dans la liste standard.

**Textes applicables :**
Les textes réglementaires applicables au dossier sont sélectionnés parmi un référentiel organisé par type de texte. Pour certaines natures de visite (périodique, chantier, inopinée), les textes applicables de l'établissement sont automatiquement repris à la création.

**Vérifications techniques :**
Un onglet dédié permet de renseigner les vérifications techniques réalisées, via un formulaire configurable composé de rubriques et de champs personnalisés.

**Effectifs et dégagements :**
Un onglet dédié permet de saisir les effectifs et les caractéristiques des dégagements, via un formulaire configurable. Les données peuvent être reprises depuis un dossier antérieur, récupérées depuis l'établissement, ou copiées vers l'établissement.

### 3.6 Avis rendu

Deux niveaux d'avis coexistent :

- **Avis du préventionniste** : avis technique émis par le préventionniste instructeur.
- **Avis de la commission** : avis officiel rendu par la commission de sécurité. C'est cet avis qui est propagé à l'établissement.

Les valeurs d'avis possibles sont : favorable, défavorable, et d'autres valeurs paramétrables (sursis à statuer, ne se prononce pas, etc.).

Un onglet **Avis et dérogations** permet de gérer des avis complémentaires associés au dossier, avec la possibilité de lier chaque avis à un autre dossier de l'établissement.

L'avis de la commission peut être masqué dans certaines situations (voir RG-DOS-015).

### 3.7 Pièces jointes

Des pièces jointes peuvent être associées au dossier (documents numérisés, rapports générés, photos, etc.). Chaque pièce jointe comporte un nom, une extension, une description et une date.

Certaines pièces jointes peuvent être marquées comme « pièces jointes de commission » (pour être intégrées aux documents de la commission).

Un compteur de nouvelles pièces jointes est affiché sur l'onglet correspondant.

L'ensemble des pièces jointes d'un dossier peut être téléchargé sous forme d'archive compressée (ZIP).

### 3.8 Contacts du dossier

Les contacts associés à un dossier sont des personnes jouant un rôle spécifique :

- Exploitant
- Directeur unique de sécurité (DUS)
- Responsable unique de sécurité
- Propriétaire
- Maître d'œuvre
- Maître d'ouvrage
- Pétitionnaire / Demandeur
- Contrôleur technique
- Participant
- Demandeur
- Préfet

À la création du dossier, les contacts de l'établissement (exploitant, DUS, responsable de sécurité, propriétaire) sont automatiquement repris dans le dossier.

### 3.9 Dossiers liés

Deux dossiers peuvent être liés entre eux de manière bidirectionnelle. Le lien est symétrique : si le dossier A est lié au dossier B, le dossier B est automatiquement lié au dossier A.

La liaison se fait à partir de la liste des dossiers des établissements concernés. Les dossiers déjà liés sont signalés pour éviter les doublons.

Un lien entre deux dossiers peut être supprimé.

---

## 4. Fonctionnalités

### 4.1 Créer un dossier

**Prérequis :** L'utilisateur peut créer un dossier depuis la fiche d'un établissement ou de manière indépendante.

**Déroulement :**

1. L'utilisateur sélectionne le **type** de dossier (étude, visite, groupe de visite, etc.).
2. La liste des **natures** disponibles s'affiche dynamiquement en fonction du type choisi.
3. L'utilisateur sélectionne une nature. Les champs du formulaire s'adaptent à la nature sélectionnée.
4. Si le dossier est créé depuis un établissement, celui-ci est automatiquement associé.
5. L'utilisateur renseigne les champs d'instruction disponibles.
6. L'utilisateur peut affecter un ou plusieurs préventionnistes.
7. L'utilisateur peut affecter le dossier à une commission et à une date de commission.
8. Le système enregistre l'identité du créateur du dossier.

**Automatismes à la création :**
- Les contacts de l'établissement (exploitant, DUS, resp. sécurité, propriétaire) sont copiés dans le dossier.
- Pour les natures de visite périodique, chantier et inopinée : les textes applicables de l'établissement sont repris.
- Pour les natures de visite périodique : les documents consultés de la précédente visite périodique de l'établissement sont repris (documents standard et documents ajoutés).
- Pour les études et visites : les prescriptions de rappel réglementaire correspondant au type sont automatiquement ajoutées.
- Pour les natures de levée de prescriptions et de levée d'avis défavorable : cinq documents « Attestation de » sont automatiquement créés.

### 4.2 Instruire un dossier

**Déroulement :**

1. L'utilisateur ouvre un dossier existant.
2. Il renseigne ou modifie les champs d'instruction. Les champs affichés dépendent de la nature du dossier.
3. Il peut modifier le type, la nature, la commission, les préventionnistes et les dates.
4. En cas de changement de nature, les documents consultés sont transférés ou supprimés selon la compatibilité.
5. Il peut marquer le dossier comme incomplet et renseigner les documents manquants.
6. Il peut renseigner le service instructeur (commune ou groupement).
7. Il renseigne l'avis du préventionniste et/ou l'avis de la commission.
8. La sauvegarde met à jour l'ensemble des données du dossier.

**Onglets disponibles pour l'instruction :**
- Informations générales (formulaire principal)
- Documents consultés
- Textes applicables
- Prescriptions
- Descriptif libre
- Vérifications techniques
- Effectifs et dégagements
- Avis et dérogations
- Pièces jointes
- Contacts
- Liaison établissements
- Dossiers liés
- Suivi (historique des événements et des avis)
- Génération de documents

### 4.3 Gérer les prescriptions d'un dossier

Les prescriptions sont organisées en **trois catégories** :

1. **Rappels réglementaires** : rappels des textes réglementaires applicables.
2. **Prescriptions liées à l'exploitation** : prescriptions opérationnelles pour l'exploitation de l'établissement.
3. **Recommandations d'amélioration** : suggestions pour améliorer le niveau de sécurité.

**Actions possibles (si le dossier n'est pas verrouillé) :**

- **Ajouter une prescription type** : sélection dans un référentiel organisé par catégorie, texte et article. Chaque prescription type comporte un libellé, un texte de référence et un article. La recherche peut se faire par navigation hiérarchique (catégorie → texte → article) ou par mots-clés.
- **Ajouter une prescription libre** : saisie manuelle du libellé et des associations texte/article.
- **Modifier une prescription** : édition du libellé, des associations texte/article, de la date de levée et du justificatif de levée.
- **Supprimer une prescription** : suppression après confirmation.
- **Réordonner les prescriptions** : glisser-déposer pour changer l'ordre. La numérotation est recalculée automatiquement.
- **Reprendre les prescriptions d'un dossier antérieur** : sélection d'un ou plusieurs dossiers de l'établissement et copie de leurs prescriptions dans le dossier courant, avec traçabilité du dossier d'origine (dossier de reprise).

Les prescriptions de rappel réglementaire sont numérotées séparément. Les prescriptions d'exploitation et d'amélioration partagent une numérotation continue.

Chaque prescription peut porter une **date de levée** et un **justificatif de levée**. Les prescriptions levées sont exclues de la génération de documents.

### 4.4 Générer des documents (rapport, compte-rendu)

L'utilisateur peut générer un document officiel (rapport, compte-rendu de visite, PV) à partir d'un modèle (template).

**Déroulement :**

1. L'utilisateur accède à l'onglet « Génération de documents ».
2. Il sélectionne un modèle de document parmi ceux disponibles pour la commission du dossier (ou les modèles communs à toutes les commissions).
3. Il sélectionne le ou les établissements pour lesquels générer le document.
4. Le système injecte dans le modèle l'ensemble des données du dossier et de l'établissement : informations générales, adresse, catégorie, types et activités, contacts, prescriptions (actuelles et antérieures, hors levées), documents consultés, textes applicables, avis, dates, vérifications techniques, effectifs et dégagements.
5. Le document généré est automatiquement sauvegardé comme pièce jointe du dossier au format ODT.
6. Un lien de téléchargement est affiché.

**Cas particulier des centres commerciaux :**
Pour les visites et levées portant sur un centre commercial, les prescriptions de toutes les cellules concernées (programmées à la même date de commission) sont incluses dans le document.

### 4.5 Affecter un dossier à une commission

**Pour les études :**
Le dossier est affecté à une date de commission (passage en salle). La date de commission est synchronisée avec le champ « date de commission » du dossier.

**Pour les visites et groupes de visite :**
Deux affectations sont possibles :
- Une date de **visite** (terrain) — peut comporter des dates liées (visite sur plusieurs jours).
- Une date de **commission en salle** (passage en commission pour délibération).

La date de visite est synchronisée avec le champ « date de visite » du dossier. La date de commission est synchronisée avec le champ « date de commission ».

Chaque affectation est liée à une date de commission planifiée. Le changement de date réinitialise les horaires d'affectation et le numéro d'ordre.

La suppression de la commission associée au dossier entraîne la suppression de toutes les affectations.

### 4.6 Verrouiller / déverrouiller un dossier

**Verrouiller :**
- Le système passe le dossier en mode lecture seule.
- L'identité de l'utilisateur ayant verrouillé est enregistrée.
- L'ensemble des champs du formulaire et les actions de modification (prescriptions, documents consultés, etc.) deviennent indisponibles.

**Déverrouiller :**
- Le système rend le dossier modifiable à nouveau.
- L'identité de l'utilisateur ayant verrouillé est effacée.

Le droit de verrouiller/déverrouiller est contrôlé par le système de permissions. Des droits spécialisés peuvent être définis par nature de dossier (ex. : seuls certains profils peuvent verrouiller les visites périodiques). Si aucun droit spécialisé n'est configuré, le droit est accordé par défaut.

### 4.7 Lier des dossiers entre eux

L'utilisateur peut lier le dossier courant à d'autres dossiers du même établissement :

1. Le système affiche la liste des dossiers des établissements concernés.
2. L'utilisateur sélectionne un ou plusieurs dossiers à lier.
3. Le lien est créé de manière bidirectionnelle.
4. Les dossiers déjà liés sont identifiés et exclus de la sélection.
5. Un lien peut être supprimé.

### 4.8 Reprendre les prescriptions d'un dossier antérieur

1. Le système présente la liste des dossiers de l'établissement (études, visites, autres).
2. L'utilisateur sélectionne un ou plusieurs dossiers sources.
3. Les prescriptions des trois catégories (rappels réglementaires, exploitation, amélioration) sont copiées dans le dossier courant.
4. Chaque prescription reprise conserve la référence du dossier d'origine (dossier de reprise), affichée sous forme d'étiquette avec le lien vers le dossier source.

### 4.9 Supprimer un dossier

La suppression d'un dossier est une **suppression logique** (le dossier est marqué comme supprimé avec une date de suppression, il n'est pas effacé physiquement de la base).

**Déroulement :**

1. L'utilisateur clique sur « Supprimer » et confirme via une boîte de dialogue modale.
2. Le système effectue la suppression logique.
3. Si le dossier donnait avis à un ou plusieurs établissements, le système recalcule le dernier dossier donnant avis pour chacun.
4. Le cache de recherche est invalidé.
5. L'utilisateur est redirigé vers la page de recherche des dossiers.

Un dossier supprimé peut être **rétabli** par un utilisateur ayant les droits suffisants. Le rétablissement annule la date de suppression.

---

## 5. Règles de gestion

### Création et structure

**RG-DOS-001** — Un dossier possède obligatoirement un type et une nature. La nature est conditionnée par le type.

**RG-DOS-002** — Chaque type de dossier possède une liste de natures ordonnées. Seules les natures ayant un ordre défini sont présentées à l'utilisateur.

**RG-DOS-003** — Les champs affichés dans le formulaire d'instruction sont déterminés par la nature du dossier. Chaque nature possède une liste de champs spécifique. Lorsqu'un dossier porte plusieurs natures, les champs affichés sont l'union des champs de chaque nature.

**RG-DOS-004** — À la création, l'identité du créateur est enregistrée dans le dossier. Elle est affichée en consultation.

**RG-DOS-005** — Si le dossier est créé depuis un établissement, cet établissement est automatiquement associé au dossier. D'autres établissements peuvent être ajoutés ultérieurement.

**RG-DOS-006** — À la création, les contacts de l'établissement ayant les fonctions exploitant, directeur unique de sécurité (DUS), responsable unique de sécurité et propriétaire sont automatiquement copiés dans le dossier.

### Prescriptions

**RG-DOS-007** — Les prescriptions sont réparties en trois catégories : rappels réglementaires, prescriptions liées à l'exploitation, et recommandations d'amélioration du niveau de sécurité.

**RG-DOS-008** — Les prescriptions de rappel réglementaire sont numérotées séquentiellement à partir de 1 au sein du dossier. Les prescriptions d'exploitation et d'amélioration partagent une numérotation continue.

**RG-DOS-009** — À la création d'un dossier de type étude, les prescriptions de rappel réglementaire prédéfinies pour les études sont automatiquement ajoutées. Pour les visites et groupes de visite, ce sont les prescriptions de rappel réglementaire prédéfinies pour les visites.

**RG-DOS-010** — Le réordonnancement des prescriptions par glisser-déposer recalcule automatiquement la numérotation.

**RG-DOS-011** — Lors de la reprise des prescriptions d'un dossier antérieur, chaque prescription copiée conserve la référence au dossier d'origine (dossier de reprise). Cette référence est visible dans l'interface et dans les documents générés.

**RG-DOS-012** — Une prescription peut porter une date de levée et un justificatif de levée. Les prescriptions levées sont exclues de la génération de documents.

### Textes applicables et documents consultés

**RG-DOS-013** — Lors de la création d'un dossier de visite périodique, visite de chantier ou visite inopinée (y compris en groupe de visite), les textes applicables de l'établissement sont automatiquement repris dans le dossier.

**RG-DOS-014** — Lors de la création d'un dossier de visite périodique, les documents consultés de la dernière visite périodique de l'établissement (documents standard et documents ajoutés par l'utilisateur) sont automatiquement repris.

### Avis

**RG-DOS-015** — L'avis du préventionniste et la date de commission/visite sont masqués dans le formulaire lorsque l'une des conditions suivantes est remplie : absence de quorum, hors délai, ne peut se prononcer (NPSP), avis différé, ou dossier incomplet.

**RG-DOS-016** — Seules certaines natures de dossier sont « donnant avis » pour l'établissement : levée de prescriptions, compte-rendu d'organisme, étude suite à avis NSP, levée d'avis défavorable, visite périodique, visite de contrôle, visite inopinée, avant ouverture (visite et groupe de visite). Pour ces natures, lorsque l'avis de la commission est favorable ou défavorable, cet avis est propagé comme avis d'exploitation de l'établissement.

**RG-DOS-017** — La propagation de l'avis peut être répercutée sur les établissements associés si l'option correspondante est activée lors de la sauvegarde.

**RG-DOS-018** — Lorsqu'un dossier de visite avant ouverture reçoit un avis favorable de la commission et que l'établissement est en statut « projet », le système affiche un avertissement invitant à passer le statut à « ouvert ».

**RG-DOS-019** — Lorsqu'un dossier passe d'une nature donnant avis à une nature non donnant avis (en édition), le système recalcule le dernier dossier donnant avis pour chaque établissement concerné.

**RG-DOS-020** — La modification de l'avis de la commission est soumise à un droit d'accès spécifique. Seuls les utilisateurs ayant le privilège correspondant peuvent modifier l'avis de commission.

### Verrouillage

**RG-DOS-021** — Lorsqu'un dossier est verrouillé, les modifications suivantes sont interdites : formulaire principal (informations générales), descriptif, prescriptions (ajout, modification, suppression, réordonnancement), liaisons (ajout, suppression), documents consultés (ajout, modification, suppression), pièces jointes, contacts.

**RG-DOS-021bis** — Les actions suivantes restent autorisées sur un dossier verrouillé (comportement iso-legacy) : textes applicables, vérifications techniques, effectifs et dégagements, avis et dérogations (ajout, modification, suppression), génération de rapports.

**RG-DOS-021ter** — Côté interface, les boutons d'action des fonctionnalités interdites (RG-DOS-021) sont masqués lorsque le dossier est verrouillé. Côté serveur, le `DossierVoter` refuse l'attribut `edit_doss` pour tout dossier verrouillé. Les actions autorisées malgré le verrou (RG-DOS-021bis) utilisent l'attribut `edit_doss_ignore_verrou` qui vérifie le privilège sans condition de verrouillage.

**RG-DOS-022** — Le verrouillage enregistre l'identité de l'utilisateur ayant verrouillé. Le déverrouillage efface cette information.

**RG-DOS-023** — Le droit de verrouillage est contrôlé par nature de dossier. Si aucune ressource spécialisée n'est configurée dans les permissions, le droit est accordé par défaut. Si une ressource spécialisée existe (pour toutes natures ou pour une nature spécifique), le droit n'est accordé que si l'une d'elles l'autorise.

### Suppression et rétablissement

**RG-DOS-024** — La suppression d'un dossier est logique : une date de suppression est positionnée. Le dossier n'est pas effacé de la base de données.

**RG-DOS-025** — Un dossier supprimé est exclu des listes de recherche et des dossiers liés aux établissements.

**RG-DOS-026** — Lors de la suppression d'un dossier, si celui-ci donnait avis à un ou plusieurs établissements, le système recalcule le dernier dossier donnant avis pour chacun d'eux.

**RG-DOS-027** — La suppression d'un dossier nécessite un droit d'accès spécifique (privilège de suppression).

**RG-DOS-028** — Un dossier supprimé peut être rétabli. Le rétablissement annule la date de suppression et réintègre le dossier dans les résultats de recherche.

### Commission et affectation

**RG-DOS-029** — Pour les études, l'affectation à une commission correspond à une date de passage en salle. La date de commission du dossier est synchronisée avec cette affectation.

**RG-DOS-030** — Pour les visites et groupes de visite, deux affectations distinctes sont possibles : une date de visite (terrain) et une date de commission en salle. Chacune synchronise le champ de date correspondant dans le dossier.

**RG-DOS-031** — Si l'affectation à une commission est supprimée (commission non renseignée), toutes les affectations existantes sont supprimées et les dates correspondantes sont effacées.

**RG-DOS-032** — Le changement de date d'affectation réinitialise les horaires de début et de fin ainsi que le numéro d'ordre de passage.

### Établissements

**RG-DOS-033** — Un dossier non supprimé n'affiche que les établissements non supprimés.

**RG-DOS-034** — L'ajout d'un établissement à un dossier ayant un avis de commission favorable ou défavorable et une nature donnant avis entraîne la propagation de cet avis au nouvel établissement.

**RG-DOS-035** — Le retrait d'un établissement d'un dossier qui lui donnait avis déclenche le recalcul du dernier dossier donnant avis pour cet établissement. Si aucun autre dossier donnant avis n'existe, l'établissement n'a plus de dossier donnant avis.

### Spécificités métier

**RG-DOS-036** — Pour les natures de levée de prescriptions et de levée d'avis défavorable, cinq documents de type « Attestation de » sont automatiquement créés à la création du dossier.

**RG-DOS-037** — La liste des documents consultés varie selon le type et la nature du dossier :
- Études (sauf levée de prescriptions et levée d'avis défavorable) : liste des documents d'étude.
- Études de type levée de prescriptions ou levée d'avis défavorable : liste des documents de visite.
- Visites de réception de travaux : liste des documents de visite spécifique « réception de travaux ».
- Visites avant ouverture : liste des documents de visite spécifique « avant ouverture ».
- Autres visites : liste des documents de visite générale.

**RG-DOS-038** — Pour les visites périodiques, si le champ « date de visite périodique » est renseigné, cette date est enregistrée comme date de visite du dossier afin de servir au calcul de la prochaine périodicité.

**RG-DOS-039** — Si le champ « objet » ne fait pas partie des champs affichables pour la nature du dossier, il est automatiquement effacé (mis à null) lors de la sauvegarde.

**RG-DOS-040** — Lors de la sauvegarde, les valeurs vides ou égales à zéro pour les champs avis (préventionniste et commission) sont traitées comme des valeurs nulles.

**RG-DOS-041** — Les champs booléens (hors délai, absence de quorum, NPSP, avis différé, CNE) sont explicitement positionnés à 0 si non cochés et à 1 si cochés, à chaque sauvegarde.

**RG-DOS-042** — En cas de changement de type ou de nature en édition, les documents consultés sont transférés ou supprimés selon la compatibilité entre l'ancienne et la nouvelle nature.

### Intégration Plat'AU

**RG-DOS-043** — Un dossier peut être lié à une consultation Plat'AU (plateforme des autorisations d'urbanisme). Lorsqu'un identifiant Plat'AU est présent, le système affiche le statut de prise en charge, le statut d'avis, les dates associées et le temps restant avant la date de réponse attendue.

**RG-DOS-044** — Si l'intégration Plat'AU est désactivée dans la configuration de l'application, l'accès à un dossier lié à Plat'AU génère une erreur.

**RG-DOS-045** — Lors de la sauvegarde, les pièces jointes sélectionnées pour export vers Plat'AU sont transmises à la plateforme.

### Cache et performances

**RG-DOS-046** — Après chaque sauvegarde ou suppression de dossier, le cache de recherche global est invalidé pour garantir la cohérence des résultats.

### Notifications

**RG-DOS-047** — Lorsqu'un avis est propagé à un établissement et que l'envoi de mails est activé, une notification par courriel peut être déclenchée si l'utilisateur dispose du droit correspondant. Un lien vers l'alerte est proposé dans un message de confirmation.

---

## 6. Cycle de vie d'un dossier

```
                         ┌──────────────┐
                         │   Création   │
                         └──────┬───────┘
                                │
                     Automatismes :
                     - Copie contacts établissement
                     - Copie textes applicables (visites)
                     - Copie docs consultés VP précédente
                     - Ajout prescriptions rappel régl.
                     - Création attestations (levées)
                                │
                                ▼
                    ┌───────────────────────┐
                    │  En cours (déverrouillé)│◄────────────────┐
                    └──────────┬────────────┘                  │
                               │                               │
          ┌────────────────────┼────────────────────┐          │
          │                    │                    │          │
          ▼                    ▼                    ▼          │
  ┌──────────────┐   ┌──────────────┐   ┌──────────────┐      │
  │  Instruction │   │  Affectation │   │  Prescriptions│      │
  │  (édition)   │   │  commission  │   │  (ajout/     │      │
  │              │   │              │   │   reprise)   │      │
  └──────┬───────┘   └──────┬───────┘   └──────┬───────┘      │
          │                    │                    │          │
          └────────────────────┼────────────────────┘          │
                               │                               │
                               ▼                               │
                    ┌───────────────────────┐                  │
                    │   Avis rendu          │                  │
                    │   (préventionniste    │                  │
                    │    et/ou commission)  │                  │
                    └──────────┬────────────┘                  │
                               │                               │
                    ┌──────────┼──────────┐                    │
                    │                     │                    │
                    ▼                     ▼                    │
          ┌──────────────┐      ┌──────────────┐              │
          │  Propagation │      │  Génération  │              │
          │  avis vers   │      │  de document │              │
          │  établiss.   │      │              │              │
          └──────────────┘      └──────────────┘              │
                               │                               │
                               ▼                               │
                    ┌───────────────────────┐                  │
                    │   Verrouillé          │──Déverrouillage──┘
                    │   (lecture seule)     │
                    └──────────┬────────────┘
                               │
              ┌────────────────┼───────────────┐
              │                                │
              ▼                                ▼
   ┌───────────────────┐           ┌───────────────────┐
   │   Archivé         │           │   Supprimé        │
   │   (verrouillé,    │           │   (logique)       │
   │    accessible en  │           │                   │
   │    lecture)        │           └─────────┬─────────┘
   └───────────────────┘                     │
                                              ▼
                                   ┌───────────────────┐
                                   │   Rétabli         │
                                   │   (retour en      │
                                   │    cours)          │
                                   └───────────────────┘
```

**États principaux :**

1. **Création** : le dossier est initialisé avec ses automatismes.
2. **En cours (déverrouillé)** : le dossier est modifiable — instruction, prescriptions, affectation, contacts, etc.
3. **Avis rendu** : un avis du préventionniste et/ou de la commission a été posé. L'avis peut être propagé à l'établissement.
4. **Verrouillé** : le dossier est en lecture seule. Seule la génération de documents et la consultation restent possibles.
5. **Supprimé** : le dossier est marqué comme supprimé (suppression logique). Il peut être rétabli.

> **Note** : il n'existe pas de workflow formel bloquant dans le système actuel. Le passage d'un état à l'autre n'est pas contraint séquentiellement : un dossier peut être verrouillé avant d'avoir reçu un avis, ou supprimé à tout moment (selon les droits).

---

## 7. Cas particuliers et limites

### Dossier de groupe (plusieurs établissements)

Les dossiers de type « groupe de visite » peuvent être liés à plusieurs établissements. Cette configuration est courante pour les centres commerciaux, où un dossier unique couvre le site et ses cellules.

La génération de documents traite chaque établissement individuellement : un document est produit par établissement sélectionné.

L'ajout ou le retrait d'un établissement dans un dossier de groupe peut impacter l'avis d'exploitation de l'établissement concerné (voir RG-DOS-034, RG-DOS-035).

### Centre commercial (activité principale de type centre commercial)

La génération de documents pour un centre commercial suit une logique spécifique :
- Pour les visites (périodiques, contrôle, inopinées), les prescriptions de toutes les cellules programmées à la même date de commission sont incluses dans le rapport.
- Pour les levées de prescriptions et d'avis défavorable, les dossiers des cellules ayant la même nature et la même date de commission sont regroupés.
- Les cellules au statut « fermé » ou « erreur » sont exclues du regroupement.

### Levée de prescriptions

Le dossier de nature « levée de prescriptions » permet de valider que les prescriptions émises lors d'un dossier antérieur ont été satisfaites. Cinq documents de type « Attestation de » sont créés automatiquement pour faciliter la vérification.

### Levée d'avis défavorable

Le dossier de nature « levée d'avis défavorable » (levée de réserves) fonctionne de manière similaire à la levée de prescriptions. Les cinq attestations automatiques sont également créées.

### Visite périodique et calcul de la prochaine visite

La date de visite enregistrée dans un dossier de visite périodique sert au calcul de la prochaine date de visite périodique par le module d'établissement. Le système retrouve la dernière visite périodique d'un établissement pour déterminer l'échéance suivante.

### Dossier incomplet et documents manquants

Un dossier marqué comme incomplet comporte une liste de documents manquants, chacun avec :
- Le libellé du document manquant (sélection dans un référentiel ou saisie libre)
- La date de signalement du document manquant
- La date de réception du document

Le statut « incomplet » est un des critères qui masquent l'affichage de l'avis (voir RG-DOS-015).

### Avis différé

L'indicateur « avis différé » est utilisé lorsque la commission repousse son avis à une séance ultérieure. Cet indicateur masque l'avis dans le formulaire. Les dossiers avec avis différé sont identifiés dans les tableaux de bord.

### Absence de quorum

Lorsque le quorum de la commission n'est pas atteint, l'indicateur correspondant est activé. L'avis est masqué et le dossier doit être reporté.

### Dossier « ne peut se prononcer » (NPSP)

Applicable aux visites de réception de travaux et avant ouverture. Lorsque la commission ne peut se prononcer, l'avis est masqué.

### Effectifs et dégagements — copie bidirectionnelle

Les effectifs et dégagements peuvent circuler dans les deux sens :
- **Établissement → Dossier** : récupérer les données de l'établissement dans le dossier (uniquement si un seul établissement est lié).
- **Dossier → Établissement** : copier les données du dossier vers l'établissement (uniquement si un seul établissement est lié).
- **Dossier antérieur → Dossier** : reprendre les effectifs et dégagements d'un autre dossier.

### Dossiers sans avis de commission échus

Le système identifie les dossiers affectés à une commission dont la date est passée et pour lesquels aucun avis de commission n'a été rendu. Ces dossiers sont signalés dans les tableaux de bord.

### Courriers sans réponse

Les dossiers de type courrier/courriel pour lesquels aucune date de réponse n'est renseignée au-delà d'un délai paramétrable sont identifiés comme courriers sans réponse dans les tableaux de bord.

### Intégration Plat'AU

Les dossiers liés à une consultation Plat'AU affichent un suivi spécifique (statut de prise en charge, statut d'avis, temps restant). La désactivation de Plat'AU dans la configuration bloque l'accès à ces dossiers.

### Limites identifiées

- Il n'existe pas de contrôle de cohérence empêchant de verrouiller un dossier sans avis.
- La suppression logique ne supprime pas les liens avec les commissions ni les prescriptions ; seule la visibilité est impactée.
- Le changement de nature en édition peut entraîner une perte de données sur les documents consultés si la nouvelle nature est incompatible.
- La reprise de prescriptions ne dédoublonne pas les prescriptions déjà présentes dans le dossier cible.
- Un dossier peut être lié à zéro établissement (aucune obligation).
