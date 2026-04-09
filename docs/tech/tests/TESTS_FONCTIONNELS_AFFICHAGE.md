# Tests Fonctionnels - Affichage des Dossiers

## 📋 Vue d'ensemble

Les tests fonctionnels permettent de valider automatiquement la conformité de l'affichage Symfony avec le legacy Zend.

## ✅ État actuel

### Tests implémentés

1. **testAffichageReunionLocauxSdis** (Nature 31)
   - Date de création du dossier ✅
   - Objet ✅
   - Date réunion ✅
   - Date de réponse ✅
   - Demandeur ✅
   - Observations ✅

2. **testAffichageReunionExterieurSdis** (Nature 32)
   - Date de création du dossier ✅
   - Objet ✅
   - Lieu réunion ✅
   - Date réunion ✅
   - Date de réponse ✅
   - Demandeur ✅
   - Observations ✅

3. **testAffichageCourrierLettre** (Nature 52)
   - Date de création du dossier ✅
   - Objet ✅
   - Date de réception mairie ✅
   - Date d'envoi/transit ✅
   - Date de réponse ✅
   - Date de réception SDIS ✅
   - Demandeur ✅
   - Date de transfert commission ✅
   - Date de réception commission ✅
   - Observations ✅

**Résultat : 3/3 tests PASS ✅**

## 🔧 Configuration

### Authentification

Les tests utilisent le compte de développement `root/root` qui est toujours présent sur les environnements de développement. L'authentification simule la session Zend en définissant :

```php
$_SESSION['Zend_Auth'] = [
    'storage' => [
        'USERNAME_UTILISATEUR' => 'root'
    ]
];
```

### Dépendances

- **Git** : Installé dans le conteneur Docker pour éviter les erreurs du profiler Symfony
- **PHPUnit 7.5.20** : Framework de tests

## 🐛 Bugs détectés et corrigés

### Template `index.html.twig`

**Ligne 410** : Mauvaise condition pour l'affichage de `dateReunion`

```twig
<!-- AVANT (Bug) -->
{% if dossier|should_display_field('dateVisite') and dossier.dateReunion %}

<!-- APRÈS (Corrigé) -->
{% if dossier|should_display_field('dateReunion') and dossier.dateReunion %}
```

Ce bug empêchait l'affichage de la date de réunion pour les dossiers de nature 31 et 32.

## 📊 Structure des tests

### Fonctionnement

1. **Récupération d'un dossier** de la nature testée depuis la base de données
2. **Authentification** avec le compte root/root
3. **Accès à la page** `/dossier/{id}`
4. **Vérification** :
   - Présence des champs attendus
   - Labels corrects
   - Valeurs affichées conformes
5. **Rapport** détaillé des résultats

### Structure HTML analysée

Les tests analysent la structure Bootstrap 3 suivante :

```html
<ul class='list-unstyled'>
    <li class='row'>
        <div class='left libelle col-md-3'>Label du champ</div>
        <div class='right value col-md-9'>Valeur affichée</div>
    </li>
</ul>
```

## 🚀 Exécution des tests

```bash
# Depuis la machine hôte
cd /home/dev/prevarisc-infra
docker exec prevarisc-infra-app-1 bash -c "cd /var/www/html/prevarisc-migration && vendor/bin/phpunit tests/Functional/DossierAffichageComparisonTest.php --testdox"
```

## 📝 Prochaines étapes

### Tests à ajouter

Pour couvrir toutes les natures de dossiers, il faudrait ajouter des tests pour :
- Nature 1 : Étude
- Nature 2 : Visite
- Nature 21 : Groupe de visite
- Nature 46 : Dossiers d'autorisation
- Nature 47 : Dossiers d'étude
- Etc.

### Améliorations possibles

1. **Tests de régression** : Ajouter des snapshots HTML pour détecter les changements non voulus
2. **Tests de performance** : Mesurer le temps de chargement des pages
3. **Tests d'accessibilité** : Vérifier la conformité WCAG
4. **Tests cross-browser** : Valider le rendu sur différents navigateurs

## 📚 Références

- Fichier de test : `tests/Functional/DossierAffichageComparisonTest.php`
- Template testé : `templates/dossier/index.html.twig`
- Documentation du système : `docs/tech/tests/SYSTEME_TESTS_COMPARAISON.md`
