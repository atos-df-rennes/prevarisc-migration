# Nommage des variables de fusion ODT

`App\Service\Formulaires\Fusion\ChampNamer` construit les noms des variables de fusion utilisés lors du remplissage des documents ODT.

## Comportement implémenté

Pour chaque partie du nom (rubrique, champ, etc.), `ChampNamer::getFullFusionName()` :

1. retire les espaces au début et à la fin ;
2. met le texte en minuscules ;
3. remplace les séquences d'espaces, les apostrophes et les guillemets doubles par `_` ;
4. assemble les parties avec `-`.

Les accents et autres caractères Unicode ne sont pas translittérés en ASCII. Par exemple, le test `ChampNamerTest` confirme que `Personnalisé` conserve son accent. Le nom dans le template ODT doit donc correspondre au nom produit par le code.

`OdtWriter::setVariable()` transmet le nom tel quel à `setVars()`. Il n'existe pas de fallback qui essaie un nom normalisé ou translittéré. Les exceptions levées par `setVars()` sont actuellement ignorées par cette méthode.

## Diagnostic

Si une variable attendue ne se remplit pas :

- comparer le nom de fusion produit par `ChampNamer` au nom présent dans le template ;
- vérifier particulièrement les accents, les apostrophes, les guillemets et les caractères de ponctuation ;
- ajouter ou adapter un test de `ChampNamer` si une règle de nommage change.

Cette page décrit l'implémentation actuelle ; les normalisations supplémentaires et la conversion de templates sont des évolutions, pas des fonctionnalités disponibles.
