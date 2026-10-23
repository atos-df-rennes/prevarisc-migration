# MOD-22 — À propos et documentation

> **Version :** 1.1  
> **Date** : 2026-07-08 (mise à jour : gestion d'erreur écart #3)
> **Statut :** Spécification initiale issue de l'analyse du legacy

---

## 1. Objet et périmètre

Le module « À propos et documentation » regroupe l'ensemble des pages informatives de l'application Prevarisc. Il permet aux utilisateurs de consulter :

- La présentation générale du logiciel et de ses objectifs.
- Les conditions d'utilisation encadrant l'accès aux services.
- Les canaux de support et la procédure de signalement de problèmes.
- La documentation technique de l'interface de programmation (API REST) destinée aux développeurs et intégrateurs.

Ces pages sont accessibles depuis le **pied de page** (footer), présent sur toutes les pages de l'application.

---

## 2. Acteurs concernés

| Acteur | Rôle vis-à-vis du module |
|--------|--------------------------|
| Tout utilisateur authentifié | Consulte les pages « À propos », « Conditions d'utilisation » et « Aide » |
| Développeur / Intégrateur | Consulte la documentation de l'API REST via la page « Développeurs » |
| Administrateur fonctionnel | Peut être amené à mettre à jour le contenu rédactionnel des pages |

> **Note :** Ces pages sont accessibles à **tout utilisateur authentifié**, sans restriction de rôle ou de profil. Elles ne sont pas accessibles avant authentification : le mécanisme ACL de l'application redirige systématiquement les visiteurs non authentifiés vers la page de connexion (seules les pages `login` et `error` sont publiques).

---

## 3. Pages disponibles

Le module expose quatre pages, toutes en **lecture seule**. Le contenu de chaque page est rédigé au format texte enrichi (Markdown) et converti automatiquement en page lisible lors de l'affichage.

### 3.1 Page « À propos »

**Accès :** Lien « À propos de » dans le pied de page.

**Contenu affiché :**

- **Objectifs de Prevarisc** : présentation du logiciel comme outil de prévention permettant aux agents de gérer les événements des établissements, d'accéder à la géolocalisation, à l'intégration avec un annuaire centralisé (LDAP), et à la génération automatique de documents.
- **Suivi des commissions de sécurité** liées aux activités de prévention.
- **Invitation à contribuer** avec renvoi vers le guide de contribution du projet.
- **Coordonnées de contact** : lien vers le système de suivi d'anomalies du projet et adresse e-mail de l'équipe.

### 3.2 Page « Conditions d'utilisation »

**Accès :** Lien « Conditions d'utilisation » dans le pied de page.

**Contenu affiché :**

- Texte juridique définissant les conditions générales d'accès et d'utilisation des services Prevarisc.
- Périmètre couvert : sites internet, API, applications, notifications, widgets et tout contenu associé (textes, graphiques, photos).
- Obligation pour l'utilisateur d'accepter ces conditions pour utiliser l'application.
- Conditions de base régissant l'utilisation.

### 3.3 Page « Aide » (Support)

**Accès :** Lien « Aide » dans le pied de page.

**Contenu affiché :**

- **Obtenir de l'aide** : renvoi vers la documentation officielle et le wiki du projet.
- **Forums et communauté** : lien vers le système de suivi d'anomalies pour poser des questions ou signaler un problème ; mention d'un groupe de discussion.
- **Contribuer au projet** : procédure en trois étapes — copier le dépôt, créer une branche, soumettre une demande de fusion.
- **Signaler un problème** : consignes pour rédiger un signalement (description, étapes de reproduction, messages d'erreur).
- **Contact** : lien vers le suivi d'anomalies et adresse e-mail de l'équipe.

### 3.4 Page « Développeurs » (Documentation technique)

**Accès :** Lien « Développeurs » dans le pied de page.

**Contenu affiché :**

Documentation de l'API REST (version 1.0) organisée en cinq domaines fonctionnels :

| Domaine | Opérations proposées |
|---------|----------------------|
| **Établissements** | Consulter un établissement, son historique, ses descriptifs, ses textes applicables, ses pièces jointes, ses contacts, ses dossiers, et récupérer les valeurs par défaut selon les caractéristiques d'un établissement |
| **Recherche** | Rechercher des établissements (par libellé, identifiant, genre, catégorie, type d'activité, statut, localisation géographique, etc.), des dossiers (par type, objet, numéro de document d'urbanisme, etc.) et des utilisateurs (par nom, fonction, groupe, statut actif) |
| **Contacts** | Récupérer des contacts par nom |
| **Adresses** | Rechercher des communes par nom ou code postal, récupérer les types de voie d'une commune, rechercher des voies dans une commune |
| **Tests** | Point de test permettant de vérifier la disponibilité de l'API |

Chaque opération est documentée avec son chemin d'accès, ses paramètres (requis ou optionnels) et une description de la réponse attendue.

---

## 4. Règles de gestion

| N° | Intitulé | Description |
|----|----------|-------------|
| **RG-APR-001** | Accès libre | Toutes les pages du module sont accessibles sans restriction de rôle ni de profil. Aucun contrôle d'habilitation n'est appliqué. |
| **RG-APR-002** | Lecture seule | Le contenu des pages est exclusivement en lecture. Aucune action de création, modification ou suppression n'est possible depuis l'interface utilisateur. |
| **RG-APR-003** | Contenu externalisé | Le contenu rédactionnel de chaque page est stocké dans un fichier texte enrichi (Markdown) distinct, indépendant de l'interface. La modification du contenu passe par la mise à jour de ces fichiers sources. |
| **RG-APR-004** | Conversion automatique | Le contenu Markdown est automatiquement converti en page lisible (HTML) au moment de l'affichage. Aucune mise en cache n'est appliquée. |
| **RG-APR-005** | Vue unique partagée | Les quatre pages utilisent un même gabarit d'affichage. La présentation est homogène quel que soit le contenu affiché. |
| **RG-APR-006** | Accès depuis le pied de page | Les liens vers les quatre pages sont présents dans le pied de page global de l'application, visible sur toutes les pages. |
| **RG-APR-007** | Affichage de la version | Le pied de page affiche la version de l'application ainsi qu'un lien vers le détail du code source correspondant dans `atos-df-rennes/prevarisc-migration` : commit courant en mode Git, release correspondante en mode version définie par l'environnement. Le nom de branche Git est affiché sans transformation, y compris le préfixe `release/`. |
| **RG-APR-008** | Copyright dynamique | L'année affichée dans la mention de copyright du pied de page est calculée automatiquement (année en cours). |
| **RG-APR-009** | Liens institutionnels | Le pied de page contient des liens vers les sites du SDIS 62 (avec logo) et du Ministère de l'Intérieur, identifiant les organismes à l'origine du projet. |
| **RG-APR-010** | Lien vers les notes de versions | Le pied de page contient un lien vers la page « Notes de versions » (changelog), distincte de ce module mais accessible depuis le même espace. |

---

## 5. Notes

1. **Pas de gestion de versions du contenu** : le module ne propose pas d'historique ni de versionnement des textes affichés. Seul le contenu actuel du fichier source est présenté.

2. **Contenu mixte sur la page Développeurs** : le fichier de documentation de l'API contient du balisage HTML (tableaux) mélangé au Markdown. La conversion automatique doit gérer cette cohabitation.

3. **Absence de pagination et de recherche** : les pages sont affichées intégralement, sans mécanisme de recherche dans le contenu ni de navigation par sections.

4. **Gestion d'erreur pour documents invalides** : si un utilisateur accède à une URL `/about/{doc}` avec une valeur `{doc}` non reconnue (hors de la liste : `about`, `dev`, `support`, `tos`), l'application retourne une page d'erreur HTTP 404. Les quatre documents attendus sont énumérés dans la constante `AboutDocument::ALL`.

5. **Lien de contribution — URL GitHub absolue** : les pages « À propos » et « Aide » contiennent un lien vers le guide de contribution du projet. La cible est une **URL GitHub absolue** (`https://github.com/atos-df-rennes/prevarisc-migration/blob/main/CONTRIBUTING.md`) qui fonctionne complètement dans le contexte de l'application web et sert toujours la version courante du fichier dans le dépôt.

6. **Adresse e-mail de contact** : `prevarisc.ext@atos.net` — présente sur les pages « À propos » et « Aide ».

7. **Pied de page — périmètre élargi** : le pied de page contient des éléments (version, copyright, liens institutionnels) qui ne relèvent pas strictement du module « À propos » mais qui participent à l'espace informatif global. Ils sont documentés ici car étroitement liés.
