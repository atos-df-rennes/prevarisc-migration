# Reprendre ici — État de la migration

> Ce fichier est mis à jour après chaque phase de migration. Il décrit l'état courant et le prochain point de départ pour reprendre les travaux.

---

## ✅ Phases terminées

### Phase 1 — Affichage read-only des formulaires personnalisés
**Branche :** `migration/affichage-formulaires-persos` (mergée)  
**Périmètre :** Affichage des 4 onglets de formulaires personnalisés en lecture seule.

### Phase 2 — Édition des formulaires personnalisés (POST brut, DescriptifSaver)
**Commit :** `feat(formulaires): édition formulaires personnalisés - Phase 2`  
**Note :** Ce service a été remplacé en Phase 3 — voir ci-dessous.

### Phase 3 — Migration complète vers Symfony Forms + DTOs + drag & drop
**Branche :** `migration/edition-formulaire-perso` (prête à merger)  
**Périmètre :** Remplacement de `DescriptifSaver` par un pipeline Symfony Forms complet avec drag & drop des lignes de tableau.

**Documentation complète :** `docs/tech/dossier/FORMULAIRES_PERSONNALISES.md`

#### Fichiers supprimés (nettoyage)
- `src/Service/DescriptifSaver.php` (remplacé par le pipeline)
- `tests/Service/DescriptifSaverTest.php`
- `public/js/formulaire/tableau/gestionTableau.js`

### Phase 4 — Migration écrans de recherche (établissements + dossiers)
**Branche :** `migration/recherches` (en cours)  
**Périmètre :** Migration complète des deux écrans de recherche, incluant filtres, pagination, export calc et routes API associées.

**Commits clés :**
- Correction SQL, Bootstrap 3, pagination, modales géographiques
- Export Calc ETS + DOSS : `feat(search): export calc établissements et dossiers`
- Mise à jour liens legacy : `migration/recherches` dans `prevarisc/`

**Fonctionnalités migrées :**
- Recherche établissements avec tous les filtres
- Recherche dossiers avec tous les filtres (Plat'AU inclus)
- Affichage établissements enfants (AJAX)
- Sélection zone géographique (modal Bootstrap 3)
- Export Calc → fichier .ods (PHPExcel 1.8, 24 colonnes ETS / 22 colonnes DOSS)
- API adresses (communes, voies, numéros) déjà migrée dans `AdresseController`

### Phase 5 — Migration page d'accueil (dashboard)
**Branche :** `migration/page-accueil`  
**Commit :** `feat(dashboard): migration page d'accueil`  
**Périmètre :** Page d'accueil avec 21 blocs conditionnels (ACL), lazy-loading AJAX, grille Packery drag-and-drop, healthcheck Plat'AU, flux de messages.

**Fichiers créés :**
- `src/Controller/IndexController.php` — 5 routes (index, bloc, add-message, delete-message, platau-healthcheck)
- `src/Controller/Api/UtilisateurPreferencesController.php` — POST /api/utilisateur/preferences
- `src/Service/Dashboard/DashboardService.php` — orchestration 21 blocs
- `src/Service/Dashboard/DashboardBlocConfig.php` — configuration blocs + ACL
- `src/Service/PlatauHealthcheckService.php` — healthcheck cURL Plat'AU/PISTE
- `src/Service/NotificationService.php` — dates dernière visite
- `src/Service/FeedService.php` — gestion flux messages
- `templates/layouts/index.html.twig` — layout plein écran (sans sidebar)
- `templates/index/` — 11 templates (index, bloc, add_message, 5 shells, 5 contents)

**Repositories enrichis :** DossierRepository, EtablissementRepository, DateCommissionRepository, NewsRepository

**Écarts documentés :**
- URL préférences : `/api/1.0/user/preferences` → `/api/utilisateur/preferences`
- Bootstrap 2 → Bootstrap 3 (glyphicons, btn-xs)
- Token PISTE : `var/piste-{env}.json` (legacy : DataStore)

---

## 🎯 Prochaine phase : à définir

**Branche courante :** `migration/page-accueil`

Suggestions :
- Continuer la migration des autres fonctionnalités (voir backlog)

---

## 📌 Commandes de validation

```bash
castor symfony:analyse   # PHPStan niveau 10 — 0 erreur
castor symfony:cs        # CS/Rector/Twig — 0 erreur
castor symfony:test      # PHPUnit — 434 tests passent
```
