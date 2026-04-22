# MOD-19 — Statistiques et extractions

## 1. Objet et périmètre

⚠️ **Ce module est actuellement non opérationnel.** Le code legacy porte explicitement la mention « code non fonctionnel et non utilisé ». Une remise en état complète est nécessaire avant toute exploitation.

Le module Statistiques et extractions permet aux agents de prévention d'interroger la base des Établissements Recevant du Public (ERP) selon des critères métier prédéfinis. Il produit des listes filtrées, triables et exportables, destinées au pilotage de l'activité de contrôle périodique et au suivi des avis défavorables.

Le périmètre couvre exclusivement les ERP de genre « bâtiment » (hors sites et IGH). Les données présentées reflètent toujours l'état connu de l'établissement à une date de référence choisie par l'utilisateur.

---

## 2. Acteurs concernés

| Acteur | Rôle dans le module |
|--------|---------------------|
| Agent de prévention | Lance les extractions, consulte les résultats, exporte les données |
| Préventionniste référent | Apparaît dans les résultats de l'extraction 4 en tant que préventionniste affecté à un établissement |
| Responsable de commission | Utilise les extractions pour préparer les calendriers de visites et le suivi des avis |
| Chef de groupement / Chef de service | Exploite les résultats à des fins de pilotage et de reporting |

---

## 3. Extractions disponibles

Le module propose quatre extractions prédéfinies. L'utilisateur accède à un écran d'accueil présentant la liste de ces extractions dans un menu déroulant. Après sélection, des paramètres de filtrage complémentaires sont affichés dynamiquement.

### 3.1 Extraction 1 — ERP en exploitation soumis à contrôle périodique

**Objectif :** Obtenir la liste de tous les ERP actuellement en exploitation qui sont soumis à une obligation de contrôle périodique, à une date de référence donnée.

**Paramètre d'entrée :**

| Paramètre | Type | Valeur par défaut |
|-----------|------|-------------------|
| Date de référence | Date (jj/mm/aaaa) | Date du jour |

**Colonnes affichées dans les résultats :**

| Colonne | Description |
|---------|-------------|
| Libellé | Nom de l'établissement |
| Commune | Commune d'implantation |
| Arrondissement | Arrondissement de rattachement |
| Type | Type d'activité de l'ERP |
| Catégorie | Catégorie de classement de l'ERP |
| Date dernière visite de contrôle | Date de la visite de contrôle la plus récente |
| Commission | Commission de sécurité compétente |

**Résumé affiché :** « Liste ERP en exploitation connus soumis à contrôle à la date du [date formatée] »

---

### 3.2 Extraction 2 — ERP sous avis défavorable

**Objectif :** Obtenir la liste de tous les ERP en exploitation qui font l'objet d'un avis défavorable de la commission de sécurité à une date de référence donnée.

**Paramètres d'entrée :**

| Paramètre | Type | Valeur par défaut |
|-----------|------|-------------------|
| Date de référence | Date (jj/mm/aaaa) | Date du jour |
| Tri sur une colonne | Liste déroulante | Premier élément de la liste |

**Options de tri disponibles :**

- Arrondissement
- Commission
- Commune
- Type
- Catégorie
- Date de la dernière visite de contrôle
- Nombre de jours écoulés sous avis défavorable

**Colonnes affichées dans les résultats :**

| Colonne | Description |
|---------|-------------|
| Libellé | Nom de l'établissement |
| Commune | Commune d'implantation |
| Arrondissement | Arrondissement de rattachement |
| Type | Type d'activité de l'ERP |
| Catégorie | Catégorie de classement |
| Date dernière visite de contrôle | Date de la visite de contrôle la plus récente |
| Commission | Commission de sécurité compétente |
| Nombre de jours sous avis défavorable | Durée en jours entre la date de passage sous avis défavorable et la date de référence |

**Résumé affiché :** « Liste ERP en exploitation sous avis défavorable à la date du [date formatée] »

---

### 3.3 Extraction 3 — Prochaines visites de contrôle périodique par commune

**Objectif :** Obtenir la liste des ERP en exploitation, soumis à contrôle périodique, situés sur une commune donnée, afin de planifier les prochaines visites.

**Paramètres d'entrée :**

| Paramètre | Type | Valeur par défaut |
|-----------|------|-------------------|
| Date de référence | Date (jj/mm/aaaa) | Date du jour |
| Commune | Liste déroulante (toutes les communes) | Première commune de la liste |
| Tri sur une colonne | Liste déroulante | Premier élément de la liste |

**Options de tri disponibles :**

- Type
- Catégorie
- Date de la dernière visite de contrôle
- Avis de la dernière visite

**Colonnes affichées dans les résultats :**

| Colonne | Description |
|---------|-------------|
| Libellé | Nom de l'établissement |
| Commune | Commune d'implantation |
| Type | Type d'activité de l'ERP |
| Catégorie | Catégorie de classement |
| Date dernière visite de contrôle | Date de la visite de contrôle la plus récente |
| Avis de la dernière visite | Avis rendu lors de la dernière visite (favorable / défavorable) |
| Commission | Commission de sécurité compétente |

**Résumé affiché :** « Prochaines visites de contrôle périodique à faire sur [nom de la commune] à la date du [date formatée] »

---

### 3.4 Extraction 4 — Visites périodiques sur douze mois

**Objectif :** Obtenir la liste des ERP ayant des visites périodiques programmées sur une période définie (par défaut, l'année civile en cours), avec indication du préventionniste référent et de la date limite de prochaine visite.

**Paramètres d'entrée :**

| Paramètre | Type | Valeur par défaut |
|-----------|------|-------------------|
| Date de début | Date (jj/mm/aaaa) | 1er janvier de l'année en cours |
| Date de fin | Date (jj/mm/aaaa) | 31 décembre de l'année en cours |
| Tri sur une colonne | Liste déroulante | Premier élément de la liste |

**Options de tri disponibles :**

- Commune
- Commission
- Établissement
- Type
- Catégorie
- Date de prochaine visite

**Colonnes affichées dans les résultats :**

| Colonne | Description |
|---------|-------------|
| Commune | Commune d'implantation |
| Établissement | Nom de l'établissement |
| Préventionniste | Nom du préventionniste référent affecté |
| Type | Type d'activité de l'ERP |
| Catégorie | Catégorie de classement |
| Date de prochaine visite | Date de la prochaine visite de contrôle programmée |
| Date limite de prochaine visite | Date calculée à partir de la dernière visite + périodicité |
| Commission | Commission de sécurité compétente |

**Comportements spécifiques :**

- Si aucune visite n'est programmée, la colonne « Date de prochaine visite » affiche un lien invitant à programmer une visite.
- Si aucun préventionniste n'est affecté, la colonne « Préventionniste » affiche un lien invitant à en désigner un.
- Si la périodicité de l'établissement est à zéro ou non renseignée, la colonne « Date limite » affiche un lien invitant à corriger la périodicité.
- La date limite est calculée en ajoutant la périodicité (en mois) à la date de la dernière visite.

**Résumé affiché :** « Liste ERP avec des visites périodiques à partir du [date de début formatée] »

---

## 4. Format des résultats

### 4.1 Affichage à l'écran

Les résultats sont présentés sous forme de tableau HTML. Chaque extraction affiche :

- Un **résumé textuel** rappelant les paramètres de la recherche.
- Le **nombre total d'établissements** concernés.
- La **date d'exécution** de la requête.
- Un **tableau** avec en-têtes de colonnes et une ligne par établissement.

Les dates sont formatées en clair (ex. : « Lundi 15 janv. 2024 »).

### 4.2 Export XML

Chaque extraction peut être exportée au format XML. Le fichier généré contient :

- Un nœud racine englobant la liste des établissements.
- Pour chaque établissement, un nœud enfant avec une balise par colonne. Chaque balise porte un attribut indiquant le libellé lisible de la colonne.
- Un nœud d'informations contenant : la date d'exécution, le nombre total de résultats, le nom de l'extraction et le résumé.

Le nom du fichier téléchargé suit le format : `[nom-de-l-extraction]-[date-du-jour].xml`.

### 4.3 Format JSON (usage interne)

Lorsque l'interface charge les paramètres d'une extraction, une requête au format JSON retourne la définition des champs de saisie (libellé, type de champ, valeurs par défaut, options de liste déroulante). Ce mécanisme est transparent pour l'utilisateur final.

---

## 5. Règles de gestion

### Périmètre des établissements

| Règle | Énoncé |
|-------|--------|
| **RG-STA-001** | Seuls les ERP de genre « bâtiment » sont inclus dans les extractions. Les sites et IGH sont exclus. |
| **RG-STA-002** | Les informations affichées pour un établissement correspondent à la fiche d'information la plus récente dont la date est antérieure ou égale à la date de référence. |
| **RG-STA-003** | Un établissement est considéré « en exploitation » lorsque son statut est « Ouvert » (statut n° 2). |

### Contrôle périodique

| Règle | Énoncé |
|-------|--------|
| **RG-STA-004** | Un établissement est « soumis à contrôle périodique » si sa périodicité est renseignée et strictement supérieure à zéro. |
| **RG-STA-005** | La date de dernière visite de contrôle correspond à la date de visite la plus récente parmi les dossiers de nature « visite périodique » liés à l'établissement. |

### Avis défavorable

| Règle | Énoncé |
|-------|--------|
| **RG-STA-006** | Un ERP est « sous avis défavorable » lorsque l'avis de la commission sur le dossier donnant avis est « Défavorable » (avis n° 2). |
| **RG-STA-007** | Le nombre de jours sous avis défavorable est calculé comme la différence, en jours, entre la date de référence et la date de la fiche d'information la plus récente antérieure ou égale à la date de référence. |

### Visites périodiques sur une période

| Règle | Énoncé |
|-------|--------|
| **RG-STA-008** | L'extraction 4 ne retient que les visites futures (date de visite postérieure à la date du jour). |
| **RG-STA-009** | Les natures de dossier prises en compte pour l'extraction 4 sont la « visite périodique » et la « visite périodique inopinée ». |
| **RG-STA-010** | La date limite de prochaine visite est calculée en ajoutant la périodicité de l'établissement (exprimée en mois) à la date de la dernière visite. |
| **RG-STA-011** | Si la périodicité n'est pas renseignée ou est égale à zéro, aucune date limite ne peut être calculée. L'utilisateur est invité à corriger la fiche de l'établissement. |
| **RG-STA-012** | Si aucune visite future n'est programmée pour un établissement, l'utilisateur est invité à créer un dossier de visite. |
| **RG-STA-013** | Si aucun préventionniste n'est affecté à un établissement, l'utilisateur est invité à en désigner un via la fiche de l'établissement. |

### Filtrage et tri

| Règle | Énoncé |
|-------|--------|
| **RG-STA-014** | L'extraction 3 filtre les résultats sur une commune unique sélectionnée par l'utilisateur (code INSEE). |
| **RG-STA-015** | Les extractions 2, 3 et 4 permettent un tri sur une colonne choisie par l'utilisateur parmi une liste prédéfinie propre à chaque extraction. |

### Format de date

| Règle | Énoncé |
|-------|--------|
| **RG-STA-016** | Les dates saisies par l'utilisateur sont au format jj/mm/aaaa. |
| **RG-STA-017** | Les dates affichées dans les résultats sont formatées en clair : jour de la semaine, jour, mois abrégé, année (ex. : « Lundi 15 janv. 2024 »). |

### Export

| Règle | Énoncé |
|-------|--------|
| **RG-STA-018** | L'export XML reprend exactement les mêmes colonnes et données que l'affichage à l'écran. |
| **RG-STA-019** | Le fichier XML exporté contient en complément la date d'exécution, le nombre total de résultats, le nom et le résumé de l'extraction. |

---

## 6. Travaux de remise en état nécessaires

Le module est explicitement marqué comme **non fonctionnel et non utilisé** dans le code legacy. Les travaux suivants sont nécessaires pour le rendre opérationnel :

### 6.1 Problèmes identifiés

1. **Module désactivé** — Le module porte un avertissement explicite indiquant qu'il ne doit pas être utilisé en l'état. Aucune garantie de bon fonctionnement n'est donnée.

2. **Absence de contrôle d'accès** — Aucune vérification de droits ou de rôle n'est effectuée. Tout utilisateur authentifié peut accéder aux extractions.

3. **Vulnérabilité d'injection** — Les dates saisies par l'utilisateur sont insérées dans les requêtes sans échappement suffisant, ce qui constitue un risque d'injection.

4. **Absence de pagination** — Les résultats sont retournés intégralement en une seule requête et affichés en une seule page, sans limite. Sur un parc important d'ERP, cela peut provoquer des temps de réponse excessifs et une surcharge mémoire.

5. **Interface obsolète** — L'interface utilise des méthodes JavaScript dépréciées (`.live()` jQuery). Le formulaire ne comporte pas de bouton de soumission natif et repose sur des appels AJAX avec peu de gestion d'erreurs.

6. **Faute de frappe dans le code** — Le libellé « Prevenstionniste » contient une coquille (devrait être « Préventionniste »).

7. **Calcul de la date limite fragile** — Le calcul de la date limite de prochaine visite (extraction 4) ne gère pas les cas de données incohérentes (visite sans date, périodicité nulle avec visite renseignée).

### 6.2 Recommandations pour la migration

1. Mettre en place un **contrôle d'accès** basé sur les rôles pour restreindre l'accès aux extractions.
2. Utiliser des **requêtes paramétrées** pour éliminer tout risque d'injection.
3. Introduire une **pagination** systématique sur toutes les extractions.
4. Moderniser l'interface de saisie des paramètres et l'affichage des résultats.
5. Ajouter une **gestion d'erreurs** (date invalide, commune introuvable, aucun résultat).
6. Envisager un **format d'export complémentaire** (CSV ou XLSX) plus adapté aux usages bureautiques que le XML.
7. Corriger les libellés erronés et normaliser l'ensemble des intitulés de colonnes.
8. Valider les **règles de gestion** (RG-STA-001 à RG-STA-019) avec les utilisateurs métier avant de les implémenter.
