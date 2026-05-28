# Rapport d'écarts — Génération documents de commission

**Feature :** Génération des documents de commission (convocation, ODJ, PV, compte-rendu)  
**Date :** 2026-05-30  
**Legacy :** `CalendrierDesCommissionsController` (generationconvocAction, generationodjAction, generationpvAction, generationcompterenduAction) + 4 templates `.phtml`  
**Symfony :** `CalendrierDesCommissionsGenerationController` + `CommissionDocumentGenerationService` + `CommissionMembreEligibilityChecker` + `AvisCommission`

---

## Niveau de confiance global : ⚠️

La logique de génération ODT est un port direct fidèle. Les adaptations concernent l'architecture (service vs template phtml), la réponse (JSON vs HTML injecté), et l'ajout de protections absentes du legacy.

---

## ✅ PORT DIRECT — aucune vérification nécessaire

- **Chargement des dossiers** : même requête DBAL (getDossiersInfosByHour / getDossiersInfosByOrder)
- **Enrichissement dossiers** : mêmes appels (preventionnistes, docUrba, infosEtab, parents, adresses, communes, last_visite)
- **Filtrage compétences membres** : logique identique (manifestation, catégorie+type, classe, dossiertype)
- **Remplissage segments ODT** : tous les champs (`addChamp` = `setVars` silent) identiques au legacy
- **Gestion prescriptions** : même structure segments (listeReg, listeExploitation, listeAmelioration + variantes Reprises/Actuelles)
- **Gestion dérogations** : segments avisDerogations + avisDerogationsEtablissement identiques
- **Gestion formulaires personnalisés** : même logique (Parent/tableau/fils/champ simple)
- **Sauvegarde PieceJointe** : même entité, même association datecommissionpj
- **Écriture fichier** : DataStore::getFilePath identique
- **Template convoc salle/visite** : même logique de sélection (typeCommission 1 vs 2/3)
- **Groupement convoc/ODJ** : même logique (membres avec ID_GROUPEMENT non null)

---

## ⚠️ ADAPTATIONS — vérifier ces points précis

### 1. Réponse HTTP : HTML injecté → JSON structuré

| | Legacy | Symfony |
|---|---|---|
| **Format** | HTML brut (template .phtml) injecté dans `#infoGeneration` via `$.ajax success` | JSON `{success, summary, results[]}` avec toast notification |
| **Granularité** | Pas de statut par membre, message global unique | Réponse par membre (memberId, memberLabel, status, message, pieceJointeId) |
| **Impact** | UX améliorée — l'utilisateur voit le détail par membre |
| **Test** | Cliquer "Générer les convocations" → vérifier toast succès/erreur, vérifier détail si erreurs partielles |

### 2. Protection CSRF ajoutée

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | Aucune protection CSRF sur les actions POST | Token CSRF par action + dateCommId (`isCsrfTokenValid`) |
| **Impact** | Sécurité renforcée, pas de régression fonctionnelle |
| **Test** | Appeler le endpoint sans token → vérifier 403. Appeler avec token valide → succès |

### 3. Vérification existence dateCommission

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | `$dbDateComm->find($dateCommId)->current()` lance une exception non gérée si inexistant | Vérification explicite → JsonResponse 404 propre |
| **Impact** | Meilleure gestion d'erreur |
| **Test** | Appeler avec un ID inexistant → vérifier réponse 404 JSON |

### 4. Guard "tous les dossiers placés" déplacé côté client uniquement

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | Vérification JS avant envoi (`#external-events` vide) | Même vérification JS (conservée). Pas de guard serveur (le legacy n'en avait pas non plus) |
| **Impact** | Identique au legacy — si le JS est désactivé, la génération se lance quand même |
| **Test** | Placer un dossier non affecté → cliquer génération → toast warning avant l'appel AJAX |

### 5. Gestion d'erreur template introuvable

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | Exception PHP non catchée → erreur 500 | try/catch → résultat avec status="error" et message "Fichier template introuvable" |
| **Impact** | UX améliorée — l'utilisateur voit quel membre a un template manquant |
| **Test** | Supprimer le fichier template d'un membre → générer → vérifier message d'erreur spécifique dans les résultats |

### 6. Frontend : `#infoGeneration` div supprimé → toast notifications

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | `<div id="infoGeneration">` recevait le HTML du résultat | Notifications toast (jQuery Toast Plugin) avec heading/text/icon |
| **Impact** | Le résultat n'est plus visible en permanence dans la page (toast disparaît après 5-8s) |
| **Test** | Générer un document → vérifier apparition toast succès (heading "Succès", icône verte) |

---

## 🔴 LOGIQUE MODIFIÉE — vérification approfondie recommandée

### 7. Architecture : template phtml → service PHP

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | Le code de génération ODT est dans les fichiers `.phtml` (templates Zend), exécutés par le moteur de vue | Code dans `CommissionDocumentGenerationService` (service injecté) |
| **Impact** | La logique est strictement identique (port direct), mais l'ordre d'exécution a été légèrement réorganisé : le legacy parcourt les membres EN PREMIER puis les dossiers DANS le template, tandis que le service charge d'abord tous les dossiers enrichis, puis itère par membre |
| **Risque** | Si un dossier est ajouté/modifié entre le chargement et la génération par le dernier membre (race condition théorique), le résultat diffère. Le legacy avait le même risque puisque le chargement était fait en amont dans l'action du controller |
| **Test** | Générer des convocations pour une commission avec 3+ membres et 5+ dossiers → vérifier que chaque membre reçoit bien les dossiers de sa compétence (comparer avec le legacy) |

### 8. PV : membres sans groupement uniquement

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | `$model_membres->fetchAll('ID_COMMISSION = X AND ID_GROUPEMENT IS NULL')` | Même filtre DBAL : `WHERE cm.ID_GROUPEMENT IS NULL` |
| **Impact** | Identique — les membres de groupement sont exclus du PV |
| **Vérification** | Comparer la liste des membres PV entre legacy et Symfony pour une commission avec groupements |

### 9. Compte-rendu : document unique (pas par membre)

| | Legacy | Symfony |
|---|---|---|
| **Mécanisme** | Utilise le template `DOCUMENT_CR` de la commission (pas des membres) | Même comportement — champ `DOCUMENT_CR` de la table `commission` |
| **Impact** | Identique |
| **Test** | Vérifier que le compte-rendu produit un seul fichier dans les PJ (pas un par membre) |

---

## Scénarios de test recommandés

| # | Scénario | Action | Résultat attendu |
|---|----------|--------|------------------|
| 1 | Convoc salle | Générer convocations pour une commission type salle (type=1) avec 2 membres + templates valides | 2 PJ créées, toast succès |
| 2 | Convoc visite | Générer convocations pour type visite (type=2) | PJ créées avec template visite |
| 3 | Convoc groupement | Commission avec membres dans un groupement | 1 PJ supplémentaire par membre groupement |
| 4 | ODJ | Générer ODJ | PJ par membre compétent |
| 5 | PV | Générer PV | 1 PJ unique (hors groupement) |
| 6 | Compte-rendu | Générer CR | 1 PJ unique |
| 7 | Template manquant | Supprimer template d'un membre | Toast avertissement avec détail du membre en erreur |
| 8 | Aucun dossier | Commission sans dossiers affectés | Toast "Aucun document à générer" |
| 9 | CSRF invalide | Appeler endpoint sans token | 403 JSON |
| 10 | Dossiers non placés | Laisser dossiers dans le panneau gauche | Toast warning (côté client uniquement, pas d'appel serveur) |
