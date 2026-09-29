---
name: analyse-legacy
description: Compare current Prevarisc application behavior with the frozen Zend 1.12 reference to identify historical behavior or regressions
---

# Analyser la référence historique Zend

L'application actuelle est ce dépôt Symfony. Le dépôt Zend `atos-df-rennes/prevarisc` est une référence figée, en lecture seule, non livrée aux clients. Le consulter pour déterminer si un comportement actuel diffère du comportement historique ou si une fonctionnalité existait déjà.

## Accéder au code historique

- En local, le dépôt infra peut fournir le checkout `prevarisc/` à côté de ce dépôt. Depuis la racine de l'application, définir `LEGACY=../prevarisc` avant d'utiliser les commandes ci-dessous.
- Si le checkout local n'est pas disponible, consulter le dépôt GitHub `https://github.com/atos-df-rennes/prevarisc`. Utiliser les outils GitHub disponibles pour lire les fichiers ou rechercher le code. Vérifier que l'accès fonctionne avant de conclure; ne pas prétendre avoir comparé le code si la référence n'a pas pu être lue.
- Ne jamais modifier le dépôt ou les fichiers Zend.

## Comparaison ciblée

Commencer par le controller Zend et l'action associée, puis suivre uniquement les dépendances nécessaires pour comprendre le comportement. Lorsque le checkout local existe :

```bash
LEGACY=../prevarisc
find "$LEGACY/application/controllers/" -iname '*Dossier*'
find "$LEGACY/application/modules/" -iname '*Dossier*'
find "$LEGACY/application/views/scripts/" -type d -name 'dossier'
find "$LEGACY/application/models/" -iname '*Dossier*'
find "$LEGACY/application/models/DbTable/" -iname '*Dossier*'
find "$LEGACY/application/forms/" -iname '*Dossier*'
```

Adapter les noms et dossiers au sujet analysé. Pour chaque action pertinente, relever :

- paramètres, validations et valeurs par défaut;
- contrôles d'accès, rôles et cas d'absence de droits;
- appels aux modèles et requêtes SQL, filtres, tri et pagination;
- données transmises à la vue, messages, redirections et réponses JSON;
- règles métier, effets de bord et gestion des erreurs;
- vues/partials et interactions JavaScript qui déterminent le résultat visible;
- formulaires, contraintes et comportement en cas d'erreur.

Les équivalences de syntaxe Zend/Symfony peuvent aider à naviguer dans les deux implémentations :

| Comportement Zend | Équivalent courant dans Symfony |
|---|---|
| `$this->_getParam('id')` | Paramètre de requête ou d'attribut `Request` |
| `$this->view->x = $value` | Variables passées au rendu Twig |
| `$this->_redirect('/path')` | Redirection vers une route |
| Flash messenger Zend | Flash message Symfony |
| ACL/helper Zend | Autorisation Symfony |
| `Zend_Session_Namespace` | Session Symfony |
| `echo json_encode(...)` | Réponse JSON Symfony |
| `.phtml` partials | Templates Twig et `include` |
| `Zend_Form` | Form Type Symfony |

Ne pas supposer qu'une différence de syntaxe implique une différence fonctionnelle. Comparer les entrées, sorties, contrôles d'accès, requêtes et effets observables.

## Conclusion attendue

Présenter les éléments legacy pertinents avec leurs chemins et lignes lorsque disponibles, puis comparer au comportement actuel. Conclure explicitement sur l'un des cas suivants :

- comportement historiquement existant et conservé;
- comportement historiquement existant mais régressé/modifié dans l'application actuelle;
- comportement absent du legacy (nouvelle fonctionnalité);
- indéterminé, si l'une des implémentations ou dépendances nécessaires n'a pas pu être inspectée.

Ne pas proposer de restauration de l'ancien code Zend. Les corrections et nouvelles fonctionnalités sont réalisées exclusivement dans ce dépôt applicatif.
