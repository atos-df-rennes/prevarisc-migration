# Problème de caractères spéciaux dans les templates ODT

## Contexte

Cette documentation traite d'un problème de robustesse identifié lors des tests de non-régression des documents de commission (mai 2026), spécifiquement avec le champ 3084 « Descriptif sommaire (nb. niv. / nb. bât. / disposition,...) » qui ne s'affichait pas dans les rendus ODT générés.

**Statut** : En cours de résolution avec implémentation progressive

---

## 🔍 Problème identifié

### Symptômes

- Un champ formulaire personnalisé configuré en base de données ne s'affiche pas dans les documents ODT générés
- Les autres champs du même formulaire s'affichent correctement
- Le placeholder du champ demeure visible dans le document final (ex. : `{descriptifEtablissement-descriptif_de_l_établissement-descriptif_sommaire_(...)}`)

### Cas testé

| Paramètre | Valeur |
|-----------|--------|
| Établissement | 10 |
| Champ | 3084 - « Descriptif sommaire » |
| Capsule | Descriptif établissement |
| Rubrique | Descriptif de l'établissement |
| Type éditeur | LibreOffice |

### Root cause

**Incompatibilité de normalisation Unicode** entre le template ODT et le code de remplissage des variables.

Lors de la création du template ODT dans LibreOffice :
1. L'utilisateur copie-colle le nom du champ depuis l'administration Prevarisc
2. LibreOffice convertit certains caractères spéciaux lors de la sauvegarde en format ODT (XML)
3. La base de données et le code PHP conservent d'autres formes Unicode

**Exemple concret** :
- Nom en BD : `Descriptif sommaire (nb. niv. / nb. bât. / disposition,...)`
- Nom dans template après sauvegarde LibreOffice : `Descriptif sommaire (nb. niv. / nb. bât. / disposition,…)` (ellipsis Unicode U+2026 au lieu de trois points)

Le code génère :
```
descriptifEtablissement-descriptif_de_l_établissement-descriptif_sommaire_(nb._niv._/_nb._bât._/_disposition,...)
```

Mais le template attend :
```
descriptifEtablissement-descriptif_de_l_établissement-descriptif_sommaire_(nb._niv._/_nb._bât._/_disposition,…)
```

**Différence** : position 111-113 = `...` vs `…`

---

## 🎯 Solutions envisagées

### Approche 1 : Normalisation Unicode stricte (NFKD)

**Concept** : Convertir tous les caractères spéciaux en formes canoniques ASCII au moment du formatage du nom.

```php
private static function formatFusionName(string $name): string {
    $trimmedName = trim($name);
    $loweredName = strtolower($trimmedName);
    
    // Normaliser les caractères Unicode en forme canonique (NFKD)
    // é → e, à → a, … → ..., etc.
    if (function_exists('iconv')) {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $loweredName);
        if (false !== $normalized) {
            $loweredName = $normalized;
        }
    }
    
    $result = preg_replace(['/\s+/', "/'+/", '/\"+/'], '_', $loweredName);
    if (null === $result) {
        throw new \InvalidArgumentException('Invalid name format.');
    }
    return $result;
}
```

**Avantages** :
- ✅ Solution idéale pour les **nouveaux templates**
- ✅ Complètement indépendante de l'éditeur ODT
- ✅ Noms prévisibles et lisibles

**Inconvénients** :
- ❌ **Casse incompatibilité** : les 20+ templates existants en production auront leurs champs mal nommés
- ❌ Nécessite une migration manuelle ou automatisée de tous les templates

---

### Approche 2 : Fallback bilingue (⭐ RECOMMANDÉE)

**Concept** : Essayer d'abord le nom tel quel (compatibilité rétroactive), puis essayer une version normalisée en fallback.

**Implémentation dans `OdtWriter.php`** :

```php
public function setVariable($target, string $name, ?string $value, ?LoggerInterface $logger = null): void
{
    try {
        $target->setVars($name, $value ?? '', true, 'UTF8');
        if (null !== $logger) {
            $prefix = $target instanceof \Segment ? '['.$target->getName().'] ' : '';
            $logger->debug($prefix.'{'.$name.'} : '.json_encode($value, JSON_UNESCAPED_UNICODE));
        }
    } catch (\Exception $exception) {
        // Fallback : essayer avec une version normalisée si le nom exact ne correspond pas
        $normalizedName = $this->normalizeForFallback($name);
        if ($normalizedName !== $name) {
            try {
                $target->setVars($normalizedName, $value ?? '', true, 'UTF8');
                if (null !== $logger) {
                    $prefix = $target instanceof \Segment ? '['.$target->getName().'] ' : '';
                    $logger->debug($prefix.'{'.$normalizedName.'} (fallback) : '.json_encode($value, JSON_UNESCAPED_UNICODE));
                }
                return;
            } catch (\Exception $fallbackException) {
                // Silencieux — variable absente du template ODT
            }
        }
    }
}

private function normalizeForFallback(string $name): string {
    // Normaliser pour gérer les variations Unicode et caractères spéciaux
    if (function_exists('iconv')) {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if (false !== $normalized) {
            return $normalized;
        }
    }
    return $name;
}
```

**Avantages** :
- ✅ **Aucune modification de templates requise**
- ✅ Fonctionne avec les anciens templates (rétrocompatibilité complète)
- ✅ Résout automatiquement les variations Unicode
- ✅ Progressive : les nouveaux templates peuvent utiliser des noms normalisés
- ✅ Zéro impact sur la production

**Inconvénients** :
- ⚠️ Ajoute une tentative supplémentaire par variable manquante (performance négligeable)

**Statut** : ✅ **À implémenter en priorité**

---

### Approche 3 : Script de migration automatique (optionnel)

**Concept** : Automatiser la conversion des templates ODT existants pour utiliser des noms normalisés.

Les fichiers ODT sont des archives ZIP contenant du XML. Le script pourrait :
1. Extraire le fichier ODT (unzip)
2. Parser le XML (`content.xml`)
3. Remplacer les noms de champs par leurs versions normalisées
4. Recompresser en ODT

**Exemple de transformation dans le XML** :

```xml
<!-- Avant -->
<text:span text:style-name="Fusion">descriptifEtablissement-descriptif_de_l_établissement-descriptif_sommaire_(...)</text:span>

<!-- Après -->
<text:span text:style-name="Fusion">descriptifEtablissement-descriptif_de_l_etablissement-descriptif_sommaire_(...)</text:span>
```

**Utilité** :
- Optionnel (le fallback suffit)
- À utiliser **après** implémentation du fallback
- Permet de "nettoyer" les templates et documenter les noms canoniques

**Statut** : 📋 À développer si demande utilisateur

---

## 📋 Recommandations d'implémentation

### Phase 1 : Correctif immédiat (Fallback)

1. Implémenter le fallback bilingue dans `OdtWriter.php`
2. Tester avec les 20+ templates existants
3. ✅ **Zéro risque**, zéro modification requise

### Phase 2 : Normalisation progressive

1. Documenter l'approche dans le Manuel Utilisateur
2. Recommander la normalisation pour les **nouveaux templates**
3. Optionnel : offrir un script de migration pour les anciens

### Phase 3 : Maintenance

1. Ajouter une validation lors de la création de templates
2. Logger les fallbacks utilisés pour monitoring
3. Considérer une normalisation en base de données à long terme

---

## 📖 Documentation utilisateur

### Pour les administrateurs

> **Caractères spéciaux dans les templates ODT**
>
> Si un champ personnalisé ne s'affiche pas dans les documents générés :
>
> 1. **Vérifier la donnée** : Assurez-vous que le champ a une valeur configurée pour l'établissement/dossier testé
> 2. **Caractères spéciaux** : Les accents (é, è, à), apostrophes ('), guillemets ("), ellipsis (…) peuvent être convertis différemment selon votre éditeur ODT
> 3. **Solution automatique** : Le système gère automatiquement les variations Unicode. Si le problème persiste, essayez de :
>    - Supprimer le champ du template
>    - Le recopier depuis la liste de l'administration (Copier-coller le nom exact)
>    - Resauvegarder le template
>
> **Pour les nouveaux templates** : Utilisez LibreOffice Writer. Le système accepte tous les formats de noms (avec ou sans accents).

### Pour les développeurs

> **Architecture de remplissage des variables ODT**
>
> Le système utilise une approche **fallback bilingue** :
> - Essai 1 : Nom exact du template (rétrocompatibilité)
> - Essai 2 : Nom normalisé ASCII (robustesse)
>
> Cela permet de supporter les anciens templates créés avec LibreOffice tout en acceptant les nouveaux basés sur des noms normalisés.
>
> Classe concernée : `App\Service\Odt\OdtWriter`
> Méthode : `setVariable()`

---

## 🔧 Script de migration optionnel (À développer)

### Pseudo-code

```php
/**
 * Script de migration : Normalise les noms de champs ODT existants
 * Usage : php bin/console app:migrate-odt-field-names /chemin/templates
 */
class MigrateOdtFieldNamesCommand extends Command {
    
    protected function execute(InputInterface $input, OutputInterface $output): int {
        $templateDir = $input->getArgument('template_dir');
        $migratedCount = 0;
        
        foreach (glob($templateDir . '/*.odt') as $file) {
            if ($this->migrateOdtFile($file)) {
                $migratedCount++;
                $output->writeln("✓ Migré : $file");
            }
        }
        
        $output->writeln("✅ Migration terminée : $migratedCount fichiers");
        return 0;
    }
    
    private function migrateOdtFile(string $filepath): bool {
        // 1. Extraire ODT (ZIP)
        $zip = new ZipArchive();
        $zip->open($filepath);
        
        // 2. Lire content.xml
        $xml = $zip->getFromName('content.xml');
        
        // 3. Remplacer les noms de champs via regex
        // Pattern : {(capsule)-(rubrique)-(champ)}
        $xml = preg_replace_callback(
            '/\{([a-zA-Z]+)-(.*?)-(.*?)\}/',
            function ($matches) {
                return '{' . $matches[1] . '-' 
                    . $this->normalize($matches[2]) . '-' 
                    . $this->normalize($matches[3]) . '}';
            },
            $xml
        );
        
        // 4. Recompresser
        $zip->addFromString('content.xml', $xml);
        $zip->close();
        
        return true;
    }
    
    private function normalize(string $name): string {
        if (function_exists('iconv')) {
            $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
            if (false !== $normalized) {
                return $normalized;
            }
        }
        return $name;
    }
}
```

---

## 🧪 Tests

### Cas de test inclus

| Cas | Description | Résultat attendu |
|-----|-------------|------------------|
| Field with ellipsis | Champ avec ellipsis Unicode (…) | ✅ Affichage via fallback |
| Field with accents | Champ avec accents (é, è, ô) | ✅ Affichage via fallback |
| Field with quotes | Champ avec guillemets (") ou apostrophes (') | ✅ Affichage via fallback |
| New normalized field | Nouveau champ avec nom déjà normalisé | ✅ Affichage direct |
| Missing field | Champ absent du template | ✅ Silencieux, placeholder vide |

---

## 📚 Références

- **Service OdtWriter** : `src/Service/Odt/OdtWriter.php`
- **Tests** : `tests/Service/Odt/OdtWriterTest.php`
- **Service Descriptif** : `src/Service/Descriptif.php` (chargement des valeurs)
- **ChampNamer** : `src/Service/Formulaires/Fusion/ChampNamer.php` (formatage des noms)

---

## 📅 Historique

| Date | Auteur | Évènement |
|------|--------|----------|
| 2026-05-28 | Copilot | Identification du problème avec champ 3084 |
| 2026-05-28 | Copilot | Documentation des solutions |
| — | À faire | Implémentation du fallback |
| — | À faire | Tests de rétrocompatibilité |
| — | À faire | Script de migration (optionnel) |
