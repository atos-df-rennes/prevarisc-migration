# MOD-15 — Cartographie

## 1. Objet et périmètre

Le module Cartographie permet de configurer et d'afficher des fonds de carte dans l'application Prevarisc. Il couvre :

- La gestion des **couches cartographiques** (ajout, modification, suppression, réordonnancement).
- L'affichage d'un **visualiseur de carte** sur les écrans de consultation et d'édition des établissements.
- La prise en charge de **plusieurs fournisseurs cartographiques** (IGN Géoportail, GeoConcept, Google Maps).

Le module s'adresse à l'ensemble des utilisateurs ayant besoin de localiser visuellement un établissement sur une carte. La configuration des couches est réservée aux administrateurs.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|---------------------|
| **Administrateur** | Configure les couches cartographiques (ajout, modification, suppression, ordre d'affichage). Accède à l'écran d'administration dédié. |
| **Utilisateur métier** | Consulte les cartes affichées sur les fiches établissement (consultation et édition). Ne gère pas les couches. |

---

## 3. Les couches cartographiques

### 3.1 Types de couches

L'application prend en charge deux protocoles standards de diffusion de données géographiques :

| Type | Description |
|------|-------------|
| **WMS** | Web Map Service — protocole de diffusion d'images cartographiques à la demande. |
| **WMTS** | Web Map Tile Service — protocole de diffusion de tuiles pré-calculées, plus performant pour les fonds de plan. |

Pour les couches IGN, trois variantes sont proposées à l'ajout :

- **WMTS** — tuiles pré-calculées.
- **WMS Raster** — images raster à la demande.
- **WMS Vecteur** — données vectorielles servies sous forme d'images.

### 3.2 Fournisseurs supportés

L'application sélectionne automatiquement le fournisseur cartographique disponible selon la configuration de l'environnement. La priorité d'affichage est la suivante :

| Priorité | Fournisseur | Condition d'activation |
|----------|-------------|----------------------|
| 1 | **IGN Géoportail** | Une clé IGN est renseignée dans la configuration. |
| 2 | **GeoConcept** | Une URL GeoConcept est renseignée (et aucune clé IGN n'est présente). |
| 3 | **Google Maps** | Une clé Google Maps est renseignée (et ni IGN ni GeoConcept ne sont configurés). |

Si aucun fournisseur n'est configuré, un message invite l'utilisateur à contacter l'administrateur pour activer un fournisseur cartographique.

**Paramètres de configuration GeoConcept :**

- URL du serveur
- Jeton d'authentification (optionnel)
- Identifiant d'application (optionnel)
- Couche de référence
- Projection cartographique (optionnel)

### 3.3 Données d'une couche

Chaque couche cartographique est décrite par les informations suivantes :

| Donnée | Description | Obligatoire |
|--------|-------------|:-----------:|
| **Type** | Protocole de la couche : WMS ou WMTS. | Oui |
| **Nom** | Libellé affiché à l'utilisateur pour identifier la couche. | Non |
| **URL du flux** | Adresse du service cartographique fournissant les données. | Non |
| **Couche de base** | Indique si la couche sert de fond de plan principal (oui/non). | Oui (par défaut : non) |
| **Nom dans le flux** | Identifiant technique de la couche au sein du service distant. | Non |
| **Format d'image** | Format des tuiles ou images reçues : PNG ou JPEG. | Oui |
| **Couche masquée** | Indique si la couche est masquée (transparente) par défaut à l'affichage (oui/non). | Oui (par défaut : non) |
| **Ordre d'affichage** | Position de la couche dans la pile d'affichage (entier, minimum 1). | Non |

---

## 4. Fonctionnalités

### 4.1 Consulter les couches configurées

**Accès :** Écran d'administration des couches cartographiques.

**Comportement :**

1. L'écran affiche un **aperçu cartographique** en haut de page, centré sur les coordonnées par défaut de l'instance. Les couches configurées sont superposées sur le fond de carte du fournisseur actif.
2. Sous l'aperçu, un **tableau** liste toutes les couches existantes avec les colonnes suivantes :
   - Type (WMS / WMTS)
   - Nom de la couche (avec la mention « couche de base » le cas échéant)
   - Ordre d'affichage (ou « Ordre non renseigné » si absent)
   - Actions disponibles (modifier, supprimer)
3. Si aucune couche n'est configurée, un message propose d'en ajouter une (couche personnalisée ou couche IGN).
4. Les couches sont triées par ordre d'affichage croissant.

**Coordonnées par défaut :** Si aucune coordonnée par défaut n'est configurée pour l'instance, le centre de la carte est positionné sur la longitude 2.7149 et la latitude 50.4727.

### 4.2 Ajouter une couche

L'application propose deux parcours d'ajout distincts.

#### 4.2.1 Ajouter une couche personnalisée

**Accès :** Bouton « Ajouter une couche personnalisée » depuis la liste.

**Comportement :**

1. Un formulaire permet de saisir l'ensemble des données de la couche (cf. §3.3).
2. Le type est sélectionné parmi WMS et WMTS.
3. Le format d'image est sélectionné parmi PNG et JPEG.
4. La validation du formulaire enregistre la couche et redirige vers la liste.
5. Un message de succès confirme l'ajout.
6. En cas d'erreur, un message indique l'échec et l'utilisateur peut réessayer.

#### 4.2.2 Ajouter une couche IGN

**Accès :** Bouton « Ajouter une couche IGN » depuis la liste.

**Comportement :**

1. L'application interroge le service IGN Géoportail pour récupérer le **catalogue des couches disponibles** (capacités du service).
2. Les couches sont présentées regroupées par format (WMTS, WMS Raster, WMS Vecteur) dans des panneaux dépliants.
3. Pour chaque couche du catalogue, les informations suivantes sont affichées :
   - Nom de la couche
   - Identifiant de la couche dans le flux
   - Format d'image (modifiable pour les couches WMS, fixe pour WMTS)
   - Option « couche masquée »
4. L'utilisateur clique sur « Ajouter » en face de la couche souhaitée.
5. Les données sont pré-remplies automatiquement à partir du catalogue IGN.
6. Un avertissement prévient l'utilisateur que le chargement de cette page peut être long si plusieurs clés IGN sont configurées.

**Prise en charge de plusieurs clés IGN :** La configuration peut contenir plusieurs clés IGN séparées par des virgules. Le catalogue est interrogé pour chacune d'elles.

### 4.3 Modifier une couche

**Accès :** Lien « Modifier la couche » dans le tableau de la liste.

**Comportement :**

1. Le formulaire est identique à celui de l'ajout d'une couche personnalisée (§4.2.1), pré-rempli avec les données existantes.
2. L'utilisateur modifie les champs souhaités et valide.
3. La validation enregistre les modifications et redirige vers la liste.
4. Un message de succès confirme la modification.
5. En cas d'erreur, un message indique l'échec.

### 4.4 Supprimer une couche

**Accès :** Lien « Supprimer » dans le tableau de la liste.

**Comportement :**

1. La suppression est immédiate, sans demande de confirmation.
2. La couche est définitivement supprimée.
3. Un message de succès confirme la suppression.
4. L'utilisateur est redirigé vers la liste.
5. En cas d'erreur, un message indique l'échec.

### 4.5 Réordonner les couches

**Accès :** Bouton « Changer l'ordre des couches » depuis la liste (visible uniquement si au moins une couche existe).

**Comportement :**

1. Un tableau affiche toutes les couches avec un champ de saisie numérique pour l'ordre.
2. La valeur minimale acceptée est 1.
3. Le champ est obligatoire (requis par le formulaire).
4. L'utilisateur saisit les nouveaux numéros d'ordre pour chaque couche et valide.
5. Toutes les couches sont mises à jour simultanément.
6. L'utilisateur est redirigé vers la liste.

---

## 5. Utilisation des cartes dans l'application

Les couches cartographiques configurées dans le module d'administration sont utilisées dans les écrans suivants :

### 5.1 Fiche de consultation d'un établissement

- La carte est affichée dans un onglet dédié (« Carte IGN », « Carte GeoConcept » ou « Carte Google » selon le fournisseur actif).
- La carte est centrée sur les coordonnées géographiques (latitude / longitude) de l'adresse de l'établissement.
- Un marqueur est positionné à l'emplacement de l'établissement.
- Les couches utilisateur configurées par l'administrateur sont superposées au fond de carte.
- L'affichage des onglets cartographiques est conditionné par :
  - La présence d'au moins une adresse géolocalisée (latitude et longitude renseignées).
  - L'activation du fournisseur correspondant dans la configuration.
- Plusieurs fournisseurs peuvent coexister (onglets séparés) sur la fiche de consultation.

### 5.2 Édition d'un établissement

- Une carte interactive est affichée dans la section d'édition de l'adresse.
- L'utilisateur peut géolocaliser l'adresse de l'établissement directement depuis la carte.
- Les couches utilisateur sont superposées sur la carte d'édition.
- Un bouton de géolocalisation spécifique à GeoConcept est disponible lorsque ce fournisseur est actif.

### 5.3 Modification d'adresse (fenêtre modale)

- Lors de la modification d'une adresse d'établissement via la fenêtre modale dédiée, une carte interactive est également disponible pour ajuster la position géographique.

### 5.4 Aperçu dans l'administration

- L'écran de gestion des couches affiche un aperçu cartographique centré sur les coordonnées par défaut de l'instance, avec toutes les couches superposées.
- Cet aperçu permet à l'administrateur de vérifier visuellement le rendu de sa configuration.

---

## 6. Règles de gestion

| Identifiant | Règle |
|-------------|-------|
| **RG-CAR-001** | Le fournisseur cartographique est sélectionné par ordre de priorité : IGN > GeoConcept > Google Maps. Le premier fournisseur dont la clé ou l'URL est configurée est utilisé. |
| **RG-CAR-002** | Si aucun fournisseur cartographique n'est configuré, la carte n'est pas affichée. Un message invite à configurer un fournisseur. |
| **RG-CAR-003** | Les couches sont affichées dans l'ordre croissant défini par le champ « Ordre d'affichage ». |
| **RG-CAR-004** | Une couche marquée « couche de base » sert de fond de plan principal. Elle se distingue des couches de superposition. |
| **RG-CAR-005** | Une couche marquée « masquée » est chargée mais n'est pas visible par défaut à l'affichage. |
| **RG-CAR-006** | Le type de couche ne peut prendre que deux valeurs : WMS ou WMTS. |
| **RG-CAR-007** | Le format d'image ne peut prendre que deux valeurs : PNG ou JPEG. |
| **RG-CAR-008** | L'ordre d'affichage est un entier positif, avec une valeur minimale de 1. |
| **RG-CAR-009** | La suppression d'une couche est immédiate et définitive, sans demande de confirmation. |
| **RG-CAR-010** | L'ajout d'une couche IGN pré-remplit automatiquement les champs à partir du catalogue des capacités du service IGN (nom, identifiant dans le flux, URL, type, format). |
| **RG-CAR-011** | Les coordonnées par défaut de l'instance sont utilisées pour centrer les cartes en l'absence de géolocalisation spécifique. Si elles ne sont pas configurées, les coordonnées par défaut sont : longitude 2.7149, latitude 50.4727. |
| **RG-CAR-012** | Le cache est appliqué aux appels IGN GetCapabilities (opération la plus coûteuse), avec une durée de 1 semaine. La liste des couches vient du repository Doctrine et ne nécessite pas de cache applicatif (opération triviale). |
| **RG-CAR-013** | La configuration peut contenir plusieurs clés IGN (séparées par des virgules). Chaque clé donne accès à un sous-ensemble du catalogue IGN. |
| **RG-CAR-014** | Sur la fiche de consultation d'un établissement, les onglets cartographiques ne sont affichés que si l'établissement possède au moins une adresse avec des coordonnées géographiques renseignées (latitude et longitude). |
| **RG-CAR-015** | Le réordonnancement met à jour l'ordre de toutes les couches en une seule opération. |
| **RG-CAR-016** | Sur la fiche de consultation d'un établissement, plusieurs fournisseurs peuvent coexister sous forme d'onglets distincts (contrairement à l'écran d'administration qui n'affiche qu'un seul fournisseur par priorité). |
| **RG-CAR-017** | L'accès à l'écran d'administration des couches cartographiques est restreint au profil administrateur. |
| **RG-CAR-018** | Le formulaire d'ajout d'une couche IGN sélectionne automatiquement le format d'image optimal en interrogeant le catalogue IGN (GetCapabilities). Pour les couches WMS, le format est extrait du flux (LegendURL) ; pour les couches WMTS, il est extrait du Format element avec fallback sur PNG. L'utilisateur n'intervient pas dans le choix du format. |

---

## 7. Cas particuliers

| Cas | Comportement |
|-----|-------------|
| **Aucune couche configurée** | La liste affiche un message vide avec des boutons d'ajout (couche personnalisée et couche IGN). Le bouton « Changer l'ordre des couches » n'est pas affiché. |
| **Aucun fournisseur configuré** | Sur l'écran d'administration, un message explicite invite à configurer un fournisseur (IGN, Google Maps ou GeoConcept). Aucune carte n'est rendue. |
| **Ordre d'affichage non renseigné** | La mention « Ordre non renseigné » est affichée dans la liste. La couche est toujours chargée sur la carte mais sa position dans la pile est indéterminée. |
| **Chargement du catalogue IGN lent** | Un avertissement est affiché sur la page de liste et sur la page d'ajout IGN, prévenant que le chargement peut prendre du temps lorsque plusieurs clés sont configurées. |
| **Fichier de configuration IGN local** | L'application peut utiliser un fichier d'auto-configuration IGN local (capacités pré-chargées) pour accélérer l'initialisation du visualiseur. Si le fichier n'existe pas, le visualiseur se rabat sur l'interrogation en ligne du service IGN. |
| **Établissement sans adresse géolocalisée** | Les onglets cartographiques (IGN, GeoConcept, Google Maps) ne sont pas affichés sur la fiche de consultation de l'établissement. |
| **Erreur à l'enregistrement** | Un message d'erreur est affiché avec le détail du problème. L'utilisateur reste sur le formulaire et peut corriger puis réessayer. |
| **Suppression échouée** | Un message d'erreur est affiché. L'utilisateur est redirigé vers la liste. |
| **Écran plein** | Le visualiseur IGN propose un bouton de passage en mode plein écran. |
