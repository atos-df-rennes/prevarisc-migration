# MOD-22 — À propos et documentation

## 1. Objet et périmètre

Cet espace rassemble les informations utiles aux utilisateurs : présentation de Prevarisc, conditions d'utilisation, aide et documentation. Les liens sont disponibles dans le pied de page de l'application.

## 2. Acteurs concernés

| Acteur | Accès |
|---|---|
| Utilisateur connecté | Consulte les pages d'information, d'aide et la documentation fonctionnelle. |
| Administrateur | Consulte également la documentation destinée à l'administration et au fonctionnement de l'application. |
| Développeur ou partenaire d'intégration | Consulte les informations destinées aux échanges entre Prevarisc et d'autres logiciels. |

## 3. Pages disponibles

### 3.1 À propos

Présente les objectifs de Prevarisc, ses principales fonctions et les coordonnées de contact.

### 3.2 Conditions d'utilisation

Présente les conditions applicables à l'utilisation de l'application.

### 3.3 Aide

Indique comment obtenir de l'aide, signaler un problème et contacter l'équipe de support.

### 3.4 Informations pour les développeurs

Présente les échanges de données disponibles avec d'autres logiciels, notamment pour les établissements, les dossiers, les contacts et les adresses.

### 3.5 Documentation

La documentation fonctionnelle décrit les fonctions et les règles métier de Prevarisc. La documentation destinée à l'administration et au fonctionnement de l'application est réservée aux administrateurs.

Les documents sont présentés dans une liste organisée par thèmes. L'utilisateur peut ouvrir un document et revenir aux autres documents depuis cette liste. L'affichage reste lisible sur les petits écrans.

## 4. Règles de gestion

| N° | Intitulé | Description |
|----|----------|-------------|
| **RG-APR-001** | Accès authentifié | Les pages « À propos », « Conditions d'utilisation », « Aide », les informations pour les développeurs et la documentation fonctionnelle sont accessibles aux utilisateurs connectés. La documentation destinée à l'administration et au fonctionnement de l'application est réservée aux administrateurs. |
| **RG-APR-002** | Lecture seule | Le contenu des pages est exclusivement en lecture. Aucune action de création, modification ou suppression n'est possible depuis l'interface utilisateur. |
| **RG-APR-003** | Contenu externalisé | Le contenu rédactionnel de chaque page est stocké dans un fichier texte enrichi (Markdown) distinct, indépendant de l'interface. La modification du contenu passe par la mise à jour de ces fichiers sources. |
| **RG-APR-004** | Conversion automatique | Le contenu Markdown est automatiquement converti en page lisible (HTML) au moment de l'affichage. Aucune mise en cache n'est appliquée. |
| **RG-APR-005** | Vue unique partagée | Les quatre pages utilisent un même gabarit d'affichage. La présentation est homogène quel que soit le contenu affiché. |
| **RG-APR-006** | Accès depuis le pied de page | Les liens vers les quatre pages sont présents dans le pied de page global de l'application, visible sur toutes les pages. |
| **RG-APR-007** | Affichage de la version | Le pied de page affiche la version de l'application ainsi qu'un lien vers le détail du code source correspondant dans `atos-df-rennes/prevarisc-migration` : commit courant en mode Git, release correspondante en mode version définie par l'environnement. Le nom de branche Git est affiché sans transformation, y compris le préfixe `release/`. |
| **RG-APR-008** | Copyright dynamique | L'année affichée dans la mention de copyright du pied de page est calculée automatiquement (année en cours). |
| **RG-APR-009** | Liens institutionnels | Le pied de page contient des liens vers les sites du SDIS 62 (avec logo) et du Ministère de l'Intérieur, identifiant les organismes à l'origine du projet. |
| **RG-APR-010** | Lien vers les notes de versions | Le pied de page contient un lien vers la page « Notes de versions » (changelog), distincte de ce module mais accessible depuis le même espace. |
| **RG-APR-011** | Sources des notes de versions | La page « Notes de versions » présente les entrées des fichiers de changelog disponibles, organisés par version majeure (`CHANGELOG-2.x.md`, `CHANGELOG-3.x.md`, etc.). Si aucun fichier exploitable n'est disponible, la page indique qu'aucun changelog n'est disponible. |
| **RG-APR-012** | Navigation des notes de versions | Les entrées sont regroupées par version majeure puis par version mineure. Les onglets des deux niveaux sont triés par ordre sémantique décroissant ; chaque entrée conserve sa version, sa date et son contenu. |
| **RG-APR-013** | Version courante | La version courante est la plus élevée selon l'ordre sémantique parmi les entrées de tous les changelogs. Elle est utilisée pour signaler les nouveautés et mémoriser la dernière version consultée. |

## 5. Limites

- Le contenu des pages ne peut pas être modifié depuis l'application.
- La documentation intégrée ne propose pas de recherche dans le texte.
