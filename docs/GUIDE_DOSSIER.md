# Guide d'utilisation - Page des informations générales des dossiers

## Accès aux pages

### Consultation/Édition d'un dossier existant
```
URL: /dossier/{id}
Méthodes: GET, POST
Exemple: /dossier/123
```

### Création d'un nouveau dossier
```
URL: /dossier/nouveau
Méthodes: GET, POST
Paramètre optionnel: ?id_etablissement=XXX
Exemple: /dossier/nouveau?id_etablissement=456
```

### Verrouiller un dossier
```
URL: /dossier/{id}/verrouiller
Méthode: GET
Exemple: /dossier/123/verrouiller
Redirection: Retour à la page précédente
```

### Déverrouiller un dossier
```
URL: /dossier/{id}/deverrouiller
Méthode: GET
Exemple: /dossier/123/deverrouiller
Redirection: Retour à la page précédente
```

### Supprimer un dossier
```
URL: /dossier/{id}/supprimer
Méthode: POST
Protection: Token CSRF + permission "delete_dossier"
Exemple: Formulaire avec token dans le modal de confirmation
```

## Fonctionnalités

### Affichage des informations

La page affiche automatiquement :
- Les informations de base du dossier (type, nature, dates, avis)
- L'établissement lié (si existant)
- Le statut de verrouillage
- Les préventionnistes associés
- L'historique (créateur, date de création)

### Édition

1. Ouvrir la page du dossier : `/dossier/{id}`
2. Modifier les champs souhaités dans le formulaire
3. Cliquer sur "Enregistrer"
4. Un message de succès confirme l'enregistrement

### Création

1. Accéder à `/dossier/nouveau` (optionnellement avec `?id_etablissement=XXX`)
2. Remplir les champs obligatoires :
   - Type de dossier
3. Compléter les autres champs selon les besoins
4. Cliquer sur "Enregistrer"
5. Redirection automatique vers la page du dossier créé

### Verrouillage

**Verrouiller :**
1. Sur la page du dossier, cliquer sur "Verrouiller ce dossier"
2. Le dossier est verrouillé et une alerte jaune s'affiche
3. Seul le verrouilleur peut éditer le dossier

**Déverrouiller :**
1. Si vous êtes le verrouilleur, cliquer sur "Déverrouiller" dans l'alerte
2. Le dossier redevient accessible en édition pour tous

### Suppression

1. Sur la page du dossier, cliquer sur "Supprimer" (bouton rouge en bas à droite)
2. Une modal de confirmation s'affiche
3. Confirmer la suppression
4. Le dossier est supprimé logiquement (soft delete)
5. Redirection vers la liste des établissements

**Note :** La suppression nécessite la permission `delete_dossier` sur la ressource `suppression`.

## Permissions requises

### Visualisation
- Accès standard (tous les utilisateurs connectés)

### Édition
- Accès standard
- Le dossier ne doit pas être verrouillé par un autre utilisateur

### Modification de l'avis de la commission
- Permission spécifique : `edit_avis_com` sur `avis_commission`

### Suppression
- Permission : `delete_dossier` sur `suppression`

## Messages et retours utilisateur

### Messages de succès
- "Le dossier a été mis à jour avec succès." (édition)
- "Le dossier a été créé avec succès." (création)
- "Le dossier a été supprimé avec succès." (suppression)

### Alertes
- Alerte de verrouillage (jaune) : Indique qui a verrouillé le dossier
- Messages flash (vert) : Confirmations d'actions réussies
- Messages flash (rouge) : Erreurs éventuelles

## Intégration avec d'autres pages

### Depuis la liste des établissements
- Créer un nouveau dossier pour un établissement : `/dossier/nouveau?id_etablissement={id}`
- L'établissement est automatiquement associé au dossier

### Depuis un dossier
- Accéder aux établissements liés via les liens dans "Établissements liés"
- Redirection après suppression vers la liste des établissements

## Limitations actuelles

1. **Champs limités** : Seuls les champs principaux sont disponibles
   - Pour une migration complète, environ 60 champs doivent être ajoutés

2. **Fonctionnalités JavaScript** : Certaines fonctionnalités avancées ne sont pas encore implémentées
   - Auto-complétion des préventionnistes
   - Sélection de commission avec calendrier
   - Gestion dynamique selon le type de dossier

3. **Associations** : Les associations suivantes doivent être gérées dans des pages dédiées
   - Documents d'urbanisme
   - Documents manquants
   - Prescriptions
   - Textes applicables
   - Dossiers liés

## Développement futur

### Phase 2 recommandée
- Ajout de tous les champs manquants
- Implémentation de l'auto-complétion
- Gestion des associations (documents, prescriptions, etc.)

### Phase 3 recommandée
- Migration des pages de prescription
- Migration des pages de documents
- Migration des pages d'avis et dérogations

## Support technique

Pour toute question ou problème :
1. Consulter la documentation technique dans `docs/MIGRATION_DOSSIER_INDEX.md`
2. Vérifier les logs Symfony : `var/log/dev.log`
3. Utiliser les outils de débogage Symfony : profiler web, `bin/console debug:router`, etc.

## Commandes utiles

```bash
# Vérifier les routes
php bin/console debug:router | grep dossier

# Vérifier le formulaire
php bin/console debug:form DossierType

# Vider le cache
php bin/console cache:clear

# Lancer les analyses
castor symfony:analyse
castor symfony:cs
castor symfony:test
```
