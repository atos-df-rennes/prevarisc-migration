# MOD-15 — Cartographie

## 1. Objet et périmètre

Le module Cartographie permet de configurer les cartes et de localiser les établissements. Il couvre :

- La gestion des **couches cartographiques**, c'est-à-dire des informations affichées sur la carte (fond de carte, limites ou repères).
- L'affichage d'une carte interactive sur les fiches établissement.
- La prise en charge de **plusieurs fournisseurs cartographiques** (IGN Géoportail, GeoConcept, Google Maps).

Le module permet de localiser les établissements sur une carte. La configuration des cartes est réservée aux administrateurs.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|---------------------|
| **Administrateur** | Configure les couches cartographiques (ajout, modification, suppression, ordre d'affichage). Accède à l'écran d'administration dédié. |
| **Utilisateur métier** | Consulte les cartes affichées sur les fiches établissement (consultation et édition). Ne gère pas les couches. |

---

## 3. Les couches cartographiques

### 3.1 Couches cartographiques

Les couches permettent de superposer différentes informations sur une carte. Les services cartographiques peuvent proposer plusieurs modes d'affichage des cartes (WMS ou WMTS). Pour les couches IGN, le catalogue peut proposer des cartes en mode WMTS, WMS Raster ou WMS Vecteur.

### 3.2 Fournisseurs supportés

L'application sélectionne automatiquement le fournisseur cartographique disponible selon le paramétrage du service. La priorité d'affichage est la suivante :

| Ordre de choix | Fournisseur | Condition d'utilisation |
|----------|-------------|----------------------|
| 1 | **IGN Géoportail** | Une clé IGN est renseignée dans la configuration. |
| 2 | **GeoConcept** | Une URL GeoConcept est renseignée (et aucune clé IGN n'est présente). |
| 3 | **Google Maps** | Une clé Google Maps est renseignée (et ni IGN ni GeoConcept ne sont configurés). |

Si aucun fournisseur n'est configuré, un message invite l'utilisateur à contacter l'administrateur pour activer un fournisseur cartographique.

**Informations nécessaires pour utiliser GeoConcept :**

- Adresse du service
- Clé d'accès (optionnelle)
- Identifiant d'application (optionnel)
- Couche de référence
- Projection cartographique (optionnel)

### 3.3 Données d'une couche

Chaque couche cartographique est décrite par les informations suivantes :

| Donnée | Description | Obligatoire |
|--------|-------------|:-----------:|
| **Type** | Format de diffusion proposé par le service (WMS ou WMTS). | Oui |
| **Nom** | Libellé affiché à l'utilisateur pour identifier la couche. | Non |
| **Adresse du service** | Adresse fournie par le fournisseur de la carte. | Non |
| **Fond principal** | Indique si la couche sert de fond de carte principal. | Oui (par défaut : non) |
| **Nom de la couche** | Nom de la couche tel qu'indiqué par le service cartographique. | Non |
| **Format d'image** | Format des images de la carte : PNG ou JPEG. | Oui |
| **Masquée par défaut** | Indique si la couche est masquée au premier affichage. | Oui (par défaut : non) |
| **Ordre d'affichage** | Position de la couche par rapport aux autres couches. | Non |

---

## 4. Fonctionnalités

### 4.1 Consulter les couches configurées

**Accès :** Écran d'administration des couches cartographiques.

**Comportement :**

1. L'écran affiche un **aperçu cartographique** en haut de page, centré sur les coordonnées par défaut de l'instance. Les couches configurées sont superposées sur le fond de carte du fournisseur actif.
2. Sous l'aperçu, un **tableau** liste toutes les couches existantes avec les colonnes suivantes :
   - Format de diffusion (WMS / WMTS)
   - Nom de la couche (avec la mention « couche de base » le cas échéant)
   - Ordre d'affichage (ou « Ordre non renseigné » s'il n'est pas défini)
   - Actions disponibles (modifier, supprimer)
3. Si aucune couche n'est configurée, un message propose d'en ajouter une (couche personnalisée ou couche IGN).
4. Les couches sont triées par ordre d'affichage croissant.

**Position par défaut :** Si aucune position n'est définie, la carte est centrée sur un point de référence.

### 4.2 Ajouter une couche

L'application propose deux parcours d'ajout distincts.

#### 4.2.1 Ajouter une couche personnalisée

**Accès :** Bouton « Ajouter une couche personnalisée » depuis la liste.

**Comportement :**

1. Un formulaire permet de saisir l'ensemble des données de la couche (cf. §3.3).
2. Le format de diffusion est sélectionné parmi WMS et WMTS.
3. Le format d'image est sélectionné parmi PNG et JPEG.
4. La validation du formulaire enregistre la couche et redirige vers la liste.
5. Un message de succès confirme l'ajout.
6. En cas d'erreur, un message indique l'échec et l'utilisateur peut réessayer.

#### 4.2.2 Ajouter une couche IGN

**Accès :** Bouton « Ajouter une couche IGN » depuis la liste.

**Comportement :**

1. L'application récupère le **catalogue des couches disponibles** auprès du service IGN Géoportail.
2. Les couches sont présentées par format (WMTS, WMS Raster, WMS Vecteur), dans des rubriques que l'utilisateur peut ouvrir ou fermer.
3. Pour chaque couche du catalogue, les informations suivantes sont affichées :
   - Nom de la couche
   - Identifiant de la couche dans le flux
   - Format d'image (modifiable pour les couches WMS, fixe pour WMTS)
   - Option « couche masquée »
4. L'utilisateur clique sur « Ajouter » en face de la couche souhaitée.
5. Les informations disponibles sont reprises automatiquement depuis le catalogue IGN.
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

- La carte est affichée dans un onglet dédié (« Carte IGN », « Carte GeoConcept » ou « Carte Google »).
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
| **RG-CAR-006** | Le format de diffusion d'une couche peut être WMS ou WMTS. |
| **RG-CAR-007** | Le format d'image ne peut prendre que deux valeurs : PNG ou JPEG. |
| **RG-CAR-008** | L'ordre d'affichage est un entier positif, avec une valeur minimale de 1. |
| **RG-CAR-009** | La suppression d'une couche est immédiate et définitive, sans demande de confirmation. |
| **RG-CAR-010** | L'ajout d'une couche IGN pré-remplit automatiquement les informations disponibles dans le catalogue IGN. |
| **RG-CAR-011** | Les coordonnées par défaut de l'instance sont utilisées pour centrer les cartes en l'absence de géolocalisation spécifique. Si elles ne sont pas configurées, les coordonnées par défaut sont : longitude 2.7149, latitude 50.4727. |
| **RG-CAR-013** | La configuration peut contenir plusieurs clés IGN (séparées par des virgules). Chaque clé donne accès à un sous-ensemble du catalogue IGN. |
| **RG-CAR-014** | Sur la fiche de consultation d'un établissement, les onglets cartographiques ne sont affichés que si l'établissement possède au moins une adresse avec des coordonnées géographiques renseignées (latitude et longitude). |
| **RG-CAR-015** | Le réordonnancement met à jour l'ordre de toutes les couches en une seule opération. |
| **RG-CAR-016** | Sur la fiche de consultation d'un établissement, plusieurs fournisseurs peuvent coexister sous forme d'onglets distincts (contrairement à l'écran d'administration qui n'affiche qu'un seul fournisseur par priorité). |
| **RG-CAR-017** | L'accès à l'écran d'administration des couches cartographiques est restreint au profil administrateur. |
| **RG-CAR-018** | Lorsqu'une couche IGN est ajoutée, le format d'image est choisi automatiquement à partir des informations fournies par IGN. |
| **RG-CAR-019** | Une configuration IGN locale peut être utilisée pour initialiser les cartes. Si elle n'est pas définie ou si son fichier est introuvable, les pages restent accessibles et les cartes utilisent le service IGN en ligne. Cette règle s'applique à la consultation, à l'ajout/édition et aux fenêtres de modification d'adresse des établissements, ainsi qu'à l'aperçu d'administration des couches. |

---

## 7. Cas particuliers

| Cas | Comportement |
|-----|-------------|
| **Aucune couche configurée** | La liste affiche un message vide avec des boutons d'ajout (couche personnalisée et couche IGN). Le bouton « Changer l'ordre des couches » n'est pas affiché. |
| **Aucun fournisseur configuré** | Sur l'écran d'administration, un message explicite invite à configurer un fournisseur (IGN, Google Maps ou GeoConcept). Aucune carte n'est rendue. |
| **Ordre d'affichage non renseigné** | La mention « Ordre non renseigné » est affichée dans la liste. La couche reste visible sur la carte, mais son emplacement par rapport aux autres couches n'est pas défini. |
| **Chargement du catalogue IGN lent** | Un avertissement est affiché sur la page de liste et sur la page d'ajout IGN, prévenant que le chargement peut prendre du temps lorsque plusieurs clés sont configurées. |
| **Configuration IGN locale indisponible** | L'absence de configuration locale ou un fichier introuvable ne doivent pas empêcher l'affichage de la page. La carte utilise le service IGN en ligne (RG-CAR-019). |
| **Établissement sans adresse géolocalisée** | Les onglets cartographiques (IGN, GeoConcept, Google Maps) ne sont pas affichés sur la fiche de consultation de l'établissement. |
| **Erreur à l'enregistrement** | Un message d'erreur est affiché avec le détail du problème. L'utilisateur reste sur le formulaire et peut corriger puis réessayer. |
| **Suppression échouée** | Un message d'erreur est affiché. L'utilisateur est redirigé vers la liste. |
| **Écran plein** | La carte IGN propose un bouton de passage en mode plein écran. |
