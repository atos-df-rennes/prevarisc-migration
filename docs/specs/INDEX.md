# Spécifications fonctionnelles — Prevarisc

> **Prevarisc** est une application web de gestion de la **prévention incendie** utilisée par les Services Départementaux d'Incendie et de Secours (SDIS). Elle couvre l'ensemble du cycle de vie de la prévention : enregistrement des **Établissements Recevant du Public (ERP)**, instruction des **dossiers d'études et de visites**, pilotage des **commissions de sécurité**, suivi des **prescriptions réglementaires**, et gestion des **utilisateurs et droits d'accès**. L'application s'appuie sur un référentiel réglementaire structuré et intègre des fonctions de cartographie, de génération de documents officiels (PV, convocations, ordres du jour) et d'interopérabilité avec la plateforme nationale **Plat'AU**.

---

## Table des matières

- [MOD-01 — Établissements](#mod-01-etablissements)
- [MOD-02 — Dossiers](#mod-02-dossiers)
- [MOD-03 — Commissions de sécurité](#mod-03-commissions-de-securite)
- [MOD-04 — Prescriptions réglementaires](#mod-04-prescriptions-reglementaires)
- [MOD-05 — Utilisateurs et droits d'accès](#mod-05-utilisateurs-et-droits-d-acces)
- [MOD-06 — Recherche et export](#mod-06-recherche-et-export)
- [MOD-07 — Groupements de communes](#mod-07-groupements-de-communes)
- [MOD-08 — Contacts](#mod-08-contacts)
- [MOD-09 — Pièces jointes](#mod-09-pieces-jointes)
- [MOD-10 — Textes applicables](#mod-10-textes-applicables)
- [MOD-11 — Périodicités](#mod-11-periodicites)
- [MOD-12 — Tableau de bord](#mod-12-tableau-de-bord)
- [MOD-13 — Interopérabilité Plat'AU](#mod-13-interoperabilite-plat-au)
- [MOD-14 — Formulaires personnalisés](#mod-14-formulaires-personnalises)
- [MOD-15 — Cartographie](#mod-15-cartographie)
- [MOD-16 — Gestion des communes](#mod-16-gestion-des-communes)
- [MOD-17 — Documents modèles](#mod-17-documents-modeles)
- [MOD-18 — Alertes et notifications](#mod-18-alertes-et-notifications)
- [MOD-19 — Statistiques et extractions](#mod-19-statistiques-et-extractions)
- [MOD-20 — Rétablissement d'éléments supprimés](#mod-20-retablissement-d-elements-supprimes)
- [MOD-21 — Administration système](#mod-21-administration-systeme)
- [MOD-22 — À propos et documentation](#mod-22-a-propos-et-documentation)
- [Vue d'ensemble des modules](#vue-d-ensemble-des-modules)
- [Dépendances entre modules](#dependances-entre-modules)

---

## Modules fonctionnels

### MOD-01 — Établissements

**Description** : Module central de gestion des établissements recevant du public (ERP), cellules, habitations, immeubles de grande hauteur (IGH) et autres types réglementaires. Permet de créer, consulter, modifier et supprimer un établissement avec toutes ses caractéristiques : classement réglementaire (catégorie, type d'activité, famille, classe), adresse géolocalisée, descriptif technique, plans, effectifs et dégagements, avis et dérogations, textes réglementaires applicables, contacts et historique complet des dossiers associés. Gère la hiérarchie entre établissements (site parent → cellules enfants) avec recalcul automatique de la périodicité de visite obligatoire.

**Priorité** : 🔴 Critique
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-01-ETABLISSEMENTS.md](MOD-01-ETABLISSEMENTS.md)

---

### MOD-02 — Dossiers

**Description** : Gestion complète des dossiers de prévention, qu'il s'agisse d'études de sécurité ou de visites de contrôle. Chaque dossier est caractérisé par son type (étude ou visite) et sa nature (permis de construire, réception de travaux, visite périodique, levée de prescriptions, etc.). Le module couvre l'intégralité du cycle de vie : création du dossier, affectation à des préventionnistes, rattachement à un ou plusieurs établissements, instruction (prescriptions, documents, textes applicables, descriptif technique), rédaction du rapport de visite, inscription à l'ordre du jour d'une commission, et clôture du dossier. Un mécanisme de verrouillage protège les dossiers finalisés contre toute modification accidentelle.

**Priorité** : 🔴 Critique
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-02-DOSSIERS.md](MOD-02-DOSSIERS.md)

---

### MOD-03 — Commissions de sécurité

**Description** : Gestion des commissions de sécurité et de leur calendrier. Ce module couvre trois périmètres : (1) la **configuration des commissions** — types, règles de compétence territoriale et technique (par commune, catégorie d'ERP, type d'activité, présence de locaux à sommeil), liste des membres et leurs spécialisations ; (2) le **calendrier des séances** — création de dates de commission, gestion de l'ordre du jour par glisser-déposer, affectation des dossiers aux créneaux horaires, export vers les agendas (format iCalendar) ; (3) la **génération de documents officiels** — convocations, ordres du jour, procès-verbaux, comptes-rendus. Les règles de compétence permettent de déterminer automatiquement quelle commission est habilitée à examiner un établissement donné.

**Priorité** : 🔴 Critique
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-03-COMMISSIONS.md](MOD-03-COMMISSIONS.md)

---

### MOD-04 — Prescriptions réglementaires

**Description** : Gestion du référentiel de prescriptions réglementaires et de leur application aux dossiers de prévention. Le référentiel est organisé de manière hiérarchique : **catégories** → **textes réglementaires** → **articles**, chaque article pouvant être associé à des types de prescriptions prédéfinis. Le module permet d'administrer ce référentiel (création, modification, réordonnancement) et d'appliquer des prescriptions à un dossier avec numérotation automatique, possibilité de reprise depuis un dossier antérieur, et recherche par mots-clés. Des rappels réglementaires distincts peuvent être définis pour les études et les visites.

**Priorité** : 🔴 Critique
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-04-PRESCRIPTIONS.md](MOD-04-PRESCRIPTIONS.md)

---

### MOD-05 — Utilisateurs et droits d'accès

**Description** : Gestion des utilisateurs, des groupes fonctionnels, de l'authentification et du contrôle d'accès. Le module permet de créer et gérer des comptes utilisateurs (informations personnelles, avatar, coordonnées), de les organiser en groupes, et de configurer une matrice de droits d'accès granulaire par groupe couvrant toutes les fonctionnalités de l'application. L'authentification supporte quatre méthodes : base de données locale, SSO CAS, NTLM et LDAP. Un mécanisme de protection limite les tentatives de connexion répétées. Des **ressources spécialisées** permettent de restreindre l'accès de certains groupes à des sous-ensembles de données (par catégorie d'ERP, type d'activité ou nature de dossier).

**Priorité** : 🔴 Critique
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-05-UTILISATEURS.md](MOD-05-UTILISATEURS.md)

---

### MOD-06 — Recherche et export

**Description** : Recherche avancée multi-critères portant sur les établissements et les dossiers. Plus de cinquante filtres combinables sont disponibles (genre, catégorie, type d'activité, commune, commission compétente, statut, avis, famille, classe, etc.) avec pagination des résultats. Les résultats peuvent être exportés au format tableur (ODS). Une fonction de recherche en temps réel alimente les champs d'autocomplétion présents dans les autres modules.

**Priorité** : 🟡 Important
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-06-RECHERCHE.md](MOD-06-RECHERCHE.md)

---

### MOD-07 — Groupements de communes

**Description** : Gestion des groupements administratifs de communes (intercommunalités, secteurs de prévention, arrondissements, etc.). Un groupement rassemble un ensemble de communes et peut être associé à des préventionnistes référents ainsi qu'à des contacts. Le module permet de visualiser, créer, modifier et supprimer des groupements, de gérer l'affectation des communes membres et la réassignation des préventionnistes. Les groupements sont essentiels pour définir les compétences territoriales des commissions de sécurité.

**Priorité** : 🟡 Important
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-07-GROUPEMENTS.md](MOD-07-GROUPEMENTS.md)

---

### MOD-08 — Contacts

**Description** : Gestion transverse des contacts associés aux différentes entités de l'application (établissements, dossiers, groupements, commissions). Un contact peut être un utilisateur du système ou une personne externe (exploitant, responsable, élu, etc.). Le module permet de créer, modifier, supprimer et rechercher des contacts, et de les rattacher aux entités concernées. Les contacts d'un établissement parent sont automatiquement hérités par ses cellules.

**Priorité** : 🟡 Important
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-08-CONTACTS.md](MOD-08-CONTACTS.md)

---

### MOD-09 — Pièces jointes

**Description** : Gestion transverse des fichiers attachés aux établissements, dossiers et dates de commission. Tout type de fichier peut être déposé ; des miniatures sont générées automatiquement pour les images. Les pièces jointes d'un établissement peuvent être classées en diaporamas ou en plans. Pour les dossiers transmis via Plat'AU, le statut d'export de chaque pièce jointe est suivi et une relance manuelle est possible en cas d'échec.

**Priorité** : 🟡 Important
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-09-PIECES-JOINTES.md](MOD-09-PIECES-JOINTES.md)

---

### MOD-10 — Textes applicables

**Description** : Gestion du référentiel des textes réglementaires applicables aux établissements et aux dossiers (arrêtés, décrets, codes, etc.). Chaque texte peut être associé à une entrée du référentiel de prescriptions pour faciliter la cohérence entre les deux référentiels. Le module permet de créer, modifier et ordonner les textes par type. Ce référentiel est ensuite utilisé dans les fiches établissement et dossier pour sélectionner les textes pertinents à l'instruction.

**Priorité** : 🟡 Important
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-10-TEXTES-APPLICABLES.md](MOD-10-TEXTES-APPLICABLES.md)

---

### MOD-11 — Périodicités

**Description** : Configuration et application du tableau des périodicités réglementaires de visites de contrôle. Ce tableau croise la **catégorie** d'ERP (1re à 5e catégorie), le **type d'activité** (J, L, M, N, O, P, R, S, T, U, V, W, X, Y) et la **présence de locaux à sommeil** pour déterminer la fréquence de visite obligatoire (exprimée en **mois**, avec affichage optionnel en années et mois au-delà de 12 mois). Le module permet de modifier cette matrice et d'appliquer les nouvelles valeurs à l'ensemble des établissements, mettant automatiquement à jour leur périodicité calculée.

**Priorité** : 🟡 Important
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-11-PERIODICITES.md](MOD-11-PERIODICITES.md)

---

### MOD-12 — Tableau de bord

**Description** : Page d'accueil personnalisable présentant des indicateurs et informations adaptés au profil de l'utilisateur et à ses droits d'accès. Les blocs d'information disponibles incluent : actualités du service, compteurs métier (nombre d'établissements, de dossiers en cours, etc.), notifications, et fil de messages internes. L'état de la connexion avec la plateforme Plat'AU y est également visible.

**Priorité** : 🟡 Important
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-12-TABLEAU-DE-BORD.md](MOD-12-TABLEAU-DE-BORD.md)

---

### MOD-13 — Interopérabilité Plat'AU

**Description** : Intégration avec la plateforme nationale **Plat'AU** (Plateforme des Autorisations d'Urbanisme) pour l'échange dématérialisé de consultations et d'avis. Le module assure la transmission des prises en charge et des avis vers Plat'AU, ainsi que l'envoi des pièces jointes associées. En cas d'échec de transmission, une relance manuelle est possible pour chaque élément concerné. L'état de santé de la connexion est consultable depuis le tableau de bord.

**Priorité** : 🟡 Important
**Statut** : ✅ Migré (relance Plat'AU)
**Spécification détaillée** : [MOD-13-PLATAU.md](MOD-13-PLATAU.md)

---

### MOD-14 — Formulaires personnalisés

**Description** : Configuration des formulaires personnalisés utilisés dans les descriptifs d'établissements et de dossiers. Chaque SDIS peut adapter les champs de saisie à ses besoins spécifiques sans modification du logiciel : les formulaires sont organisés en capsules contenant des rubriques, elles-mêmes composées de champs paramétrables (texte libre, liste de valeurs, case à cocher, etc.). L'ordre des rubriques et des champs est ajustable par glisser-déposer.

**Priorité** : 🟢 Standard
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-14-FORMULAIRES.md](MOD-14-FORMULAIRES.md)

---

### MOD-15 — Cartographie

**Description** : Administration des couches cartographiques affichées sur les cartes de l'application. Permet de configurer les fonds de carte et les couches superposées provenant de différents fournisseurs (IGN, Google Maps, GeoConcept). Chaque couche est paramétrable (visibilité, opacité, ordre d'affichage) et peut être réordonnée selon les besoins du SDIS.

**Priorité** : 🟢 Standard
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-15-CARTOGRAPHIE.md](MOD-15-CARTOGRAPHIE.md)

---

### MOD-16 — Gestion des communes

**Description** : Administration du référentiel des communes et gestion des fusions de communes. Le module permet de consulter et modifier les informations d'une commune, notamment les coordonnées du contact associé. Il permet également de traiter les fusions de communes (suite à des réformes territoriales) en important un fichier de description des fusions : toutes les références à la commune fusionnée sont alors automatiquement mises à jour dans l'ensemble du système (adresses, groupements, établissements, etc.).

**Priorité** : 🟢 Standard
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-16-COMMUNES.md](MOD-16-COMMUNES.md)

---

### MOD-17 — Documents modèles

**Description** : Administration des modèles de documents (au format ODT) utilisés pour la génération des documents officiels des commissions : convocations, ordres du jour, procès-verbaux, comptes-rendus et courriers aux membres. Les modèles sont organisés par commission et peuvent être remplacés à tout moment par de nouvelles versions.

**Priorité** : 🟢 Standard
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-17-DOCUMENTS-MODELES.md](MOD-17-DOCUMENTS-MODELES.md)

---

### MOD-18 — Alertes et notifications

**Description** : Système de notification par e-mail permettant d'informer des utilisateurs lors de modifications survenues sur des établissements ou des dossiers. Les messages d'alerte sont configurables avec des balises dynamiques (remplacées automatiquement par les données réelles au moment de l'envoi). Le système identifie automatiquement les destinataires concernés selon la nature du changement.

**Priorité** : 🟢 Standard
**Statut** : ❓ À investiguer (non documenté dans le manifest)
**Spécification détaillée** : [MOD-18-ALERTES.md](MOD-18-ALERTES.md)

---

### MOD-19 — Statistiques et extractions

**Description** : Module d'extraction de données statistiques sur le parc ERP. Propose quatre extractions prédéfinies : liste des ERP en exploitation soumis à contrôle périodique, liste des ERP sous avis défavorable, prochaines visites périodiques par commune, visites périodiques sur douze mois glissants. Les résultats sont exportables. **Note : ce module est actuellement non opérationnel et nécessite une remise en état.**

**Priorité** : 🟢 Standard
**Statut** : ⏭️ Abandonné (non migré volontairement)
**Spécification détaillée** : [MOD-19-STATISTIQUES.md](MOD-19-STATISTIQUES.md)

---

### MOD-20 — Rétablissement d'éléments supprimés

**Description** : Interface permettant aux administrateurs autorisés de visualiser les dossiers et établissements supprimés (suppression logique) et de les restaurer. Constitue un filet de sécurité contre les suppressions accidentelles.

**Priorité** : 🟢 Standard
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-20-RETABLISSEMENT.md](MOD-20-RETABLISSEMENT.md)

---

### MOD-21 — Administration système

**Description** : Panneau de surveillance technique réservé aux administrateurs système. Affiche l'état général de l'application : version déployée, configuration des clés d'accès aux services cartographiques, état de la connexion à la base de données, méthode d'authentification active, état du cache, liste des sessions utilisateurs actives et inventaire des composants tiers.

**Priorité** : 🟢 Standard
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-21-ADMINISTRATION.md](MOD-21-ADMINISTRATION.md)

---

### MOD-22 — À propos et documentation

**Description** : Pages statiques d'information sur l'application (présentation, conditions d'utilisation, support et documentation de l'API), ainsi qu'un navigateur intégré des documentations fonctionnelle et technique du projet. La documentation fonctionnelle est accessible aux utilisateurs authentifiés ; la documentation technique est réservée aux administrateurs.

**Priorité** : 🟢 Standard
**Statut** : ✅ Migré
**Spécification détaillée** : [MOD-22-A-PROPOS.md](MOD-22-A-PROPOS.md)

---

## Vue d'ensemble des modules

| ID | Module | Priorité | Statut |
|----|--------|----------|--------|
| MOD-01 | Établissements | 🔴 Critique | ✅ Migré |
| MOD-02 | Dossiers | 🔴 Critique | ✅ Migré |
| MOD-03 | Commissions de sécurité | 🔴 Critique | ✅ Migré |
| MOD-04 | Prescriptions réglementaires | 🔴 Critique | ✅ Migré |
| MOD-05 | Utilisateurs et droits d'accès | 🔴 Critique | ✅ Migré |
| MOD-06 | Recherche et export | 🟡 Important | ✅ Migré |
| MOD-07 | Groupements de communes | 🟡 Important | ✅ Migré |
| MOD-08 | Contacts | 🟡 Important | ✅ Migré |
| MOD-09 | Pièces jointes | 🟡 Important | ✅ Migré |
| MOD-10 | Textes applicables | 🟡 Important | ✅ Migré |
| MOD-11 | Périodicités | 🟡 Important | ✅ Migré |
| MOD-12 | Tableau de bord | 🟡 Important | ✅ Migré |
| MOD-13 | Interopérabilité Plat'AU | 🟡 Important | ✅ Migré |
| MOD-14 | Formulaires personnalisés | 🟢 Standard | ✅ Migré |
| MOD-15 | Cartographie | 🟢 Standard | ✅ Migré |
| MOD-16 | Gestion des communes | 🟢 Standard | ✅ Migré |
| MOD-17 | Documents modèles | 🟢 Standard | ✅ Migré |
| MOD-18 | Alertes et notifications | 🟢 Standard | ❓ À investiguer |
| MOD-19 | Statistiques et extractions | 🟢 Standard | ⏭️ Abandonné |
| MOD-20 | Rétablissement d'éléments supprimés | 🟢 Standard | ✅ Migré |
| MOD-21 | Administration système | 🟢 Standard | ✅ Migré |
| MOD-22 | À propos et documentation | 🟢 Standard | ✅ Migré |

**Total : 22 modules · 20 migrés complètement · 1 à investiguer · 1 abandonné**

**Progression réelle : 100% (35/36 tâches de migration terminées)**

---

## Dépendances entre modules

```
┌──────────────────────────────────────────────────────────────┐
│                        CŒUR MÉTIER                           │
│                                                              │
│   ┌─────────────────┐  1:N   ┌─────────────────┐            │
│   │  MOD-01         │───────▶│  MOD-02         │            │
│   │  Établissements │◀───────│  Dossiers       │            │
│   └────────┬────────┘  N:M   └────────┬────────┘            │
│            │                          │                      │
│     calcul │ périodicité      affecté │ à                    │
│            ▼                          ▼                      │
│   ┌─────────────────┐        ┌─────────────────┐            │
│   │  MOD-11         │        │  MOD-03         │            │
│   │  Périodicités   │        │  Commissions    │            │
│   └─────────────────┘        └─────────────────┘            │
│                                                              │
│                      ┌─────────────────┐                    │
│                      │  MOD-04         │◀── rattachées aux  │
│                      │  Prescriptions  │    dossiers        │
│                      └─────────────────┘                    │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│                    MODULES TRANSVERSES                       │
│                                                              │
│   MOD-08 Contacts        ──┐                                 │
│   MOD-09 Pièces jointes  ──┼──▶ s'appliquent à :            │
│   MOD-10 Textes applicab.──┘    Établissements, Dossiers,   │
│                                 Commissions, Groupements     │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│                      MODULES SUPPORT                         │
│                                                              │
│   MOD-05 Utilisateurs   ──▶ Contrôle l'accès à tout         │
│   MOD-06 Recherche      ──▶ Interroge Établissements+Dossiers│
│   MOD-07 Groupements    ──▶ Compétence territoriale          │
│                              des Commissions                 │
│   MOD-12 Tableau de bord──▶ Synthèse de tous les modules    │
│   MOD-13 Plat'AU        ──▶ Export Dossiers + PJ            │
│   MOD-14 Formulaires    ──▶ Descriptifs Établissements+Doss.│
│   MOD-18 Alertes        ──▶ Déclenché par Établissements    │
│                              et Dossiers                     │
└──────────────────────────────────────────────────────────────┘
```

### Relations fonctionnelles

| Module | Dépend de | Nature de la relation |
|--------|-----------|-----------------------|
| MOD-02 Dossiers | MOD-01 Établissements | Un dossier est lié à un ou plusieurs établissements |
| MOD-02 Dossiers | MOD-03 Commissions | Un dossier est inscrit à l'ordre du jour d'une commission |
| MOD-02 Dossiers | MOD-04 Prescriptions | Un dossier contient des prescriptions issues du référentiel |
| MOD-03 Commissions | MOD-07 Groupements | La compétence d'une commission est définie par territoire |
| MOD-03 Commissions | MOD-17 Documents modèles | Les documents officiels utilisent les modèles de la commission |
| MOD-01 Établissements | MOD-11 Périodicités | La périodicité de visite est calculée depuis la matrice réglementaire |
| MOD-08 Contacts | MOD-01, 02, 03, 07 | Les contacts sont rattachés aux entités métier |
| MOD-09 Pièces jointes | MOD-01, 02, 03 | Les fichiers sont attachés aux établissements, dossiers et commissions |
| MOD-10 Textes applicables | MOD-01, 02 | Les textes s'appliquent aux établissements et dossiers |
| MOD-10 Textes applicables | MOD-04 Prescriptions | Un texte applicable peut être lié à une prescription du référentiel |
| MOD-13 Plat'AU | MOD-02 Dossiers | Les consultations exportées correspondent à des dossiers |
| MOD-13 Plat'AU | MOD-09 Pièces jointes | Les pièces jointes des dossiers sont transmises avec les consultations |
| MOD-14 Formulaires | MOD-01, 02 | Les formulaires personnalisés enrichissent les descriptifs |
| MOD-05 Utilisateurs | Tous | Les droits d'accès conditionnent chaque fonctionnalité |
| MOD-18 Alertes | MOD-01, 02 | Les modifications d'établissements et dossiers déclenchent des alertes |
| MOD-06 Recherche | MOD-01, 02 | La recherche porte sur les établissements et les dossiers |

---

## Métadonnées

| Clé | Valeur |
|-----|--------|
| **Date de génération** | Avril 2026 |
| **Nombre de modules** | 22 |
| **Modules critiques** 🔴 | 5 |
| **Modules importants** 🟡 | 8 |
| **Modules standard** 🟢 | 9 |
