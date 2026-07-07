# Rapport d'écarts — MOD-18 ALERTES

> **Date** : 2026-07-07  
> **Spec source** : `docs/specs/MOD-18-ALERTES.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 16 (RG-ALT-001 à RG-ALT-016) |
| Écarts spec ↔ Symfony | 3 |
| Écarts spec ↔ Legacy | 0 |
| Écarts Symfony ↔ Legacy | 3 |
| Impact critique | 0 |

---

## Écarts détaillés

### Écart 1 — Comportement de la modale en erreur (service mail désactivé) ✅ VALIDÉ

| | Détail |
|---|--------|
| **Règles** | RG-ALT-012 |
| **Spec (mise à jour)** | Si le service mail est désactivé, l'envoi échoue et l'utilisateur reçoit un message d'erreur explicite via une notification toast. La modale reste ouverte pour permettre une action corrective. |
| **Symfony (implémentation)** | Le contrôleur retourne un JSON `success=false` avec le message : « L'envoi d'email est désactivé dans la configuration ». Le JS affiche un toast d'erreur et laisse la modale ouverte sans la fermer. |
| **Legacy** | Le service mail retourne `false` silencieusement. La modale se ferme normalement après clic sur Envoyer, sans donner aucun retour à l'utilisateur. |
| **Validation** | ✅ **L'implémentation Symfony est conforme à la spécification révisée.** Le comportement représente une **amélioration UX** : l'utilisateur obtient désormais un feedback explicite au lieu d'une fermeture silencieuse. |
| **Fichiers concernés** | `src/Controller/AlerteController.php:179-183`, `templates/alerte/formulaire.html.twig:93-101`, `docs/specs/MOD-18-ALERTES.md:157-158` |

---

### Écart 2 — Comportement de la modale en erreur (erreur technique lors de l'envoi) ✅ VALIDÉ

| | Détail |
|---|--------|
| **Règles** | RG-ALT-012 |
| **Spec (mise à jour)** | En cas d'erreur technique (exception de transport), l'utilisateur reçoit un message d'erreur explicite via une notification toast. La modale reste ouverte pour permettre la réédition du message et une nouvelle tentative. |
| **Symfony (implémentation)** | Le contrôleur capture les exceptions `TransportExceptionInterface` et retourne `success=false` avec le message d'erreur du transport (`$transportException->getMessage()`). Le JS affiche un toast d'erreur et réactive le bouton Envoyer sans fermer la modale. |
| **Legacy** | Ferme la boîte de dialogue immédiatement après clic sur Envoyer, sans attendre la réponse du service (comportement non-bloquant, aucun feedback d'erreur). |
| **Validation** | ✅ **L'implémentation Symfony est conforme à la spécification révisée.** Le comportement représente une **amélioration UX** : l'utilisateur obtient un feedback explicite sur l'erreur technique, permettant une correction et une nouvelle tentative au lieu d'une fermeture silencieuse. |
| **Fichiers concernés** | `src/Controller/AlerteController.php:211-215`, `templates/alerte/formulaire.html.twig:93-101`, `docs/specs/MOD-18-ALERTES.md:160` |

---

### Écart 3 — Libellé du bouton quand aucun destinataire n'est disponible ✅ VALIDÉ

| | Détail |
|---|--------|
| **Règles** | RG-ALT-009 |
| **Spec (mise à jour)** | Quand aucun destinataire n'est disponible, l'utilisateur doit pouvoir fermer la modale. Le bouton « Annuler » (ou équivalent « Fermer ») remplit cette fonction. Le libellé du bouton est secondaire ; ce qui prime est la possibilité de fermer la modale. |
| **Symfony (implémentation)** | Masque le bouton Envoyer et conserve le bouton `Annuler`, qui ferme la modale avec `data-dismiss="modal"`. |
| **Legacy** | Renomme explicitement le bouton principal en `Fermer` (transforme `Envoyer` → `Fermer`). |
| **Validation** | ✅ **Les deux approches remplissent l'objectif fonctionnel.** L'approche Symfony est une **amélioration UX** : elle ne relabel pas un bouton existant, mais masque simplement les actions non pertinentes en gardant le bouton de fermeture visible. L'implémentation est conforme à la spécification. |
| **Fichiers concernés** | `src/Controller/AlerteController.php:95-96`, `templates/alerte/formulaire.html.twig:56-62`, `prevarisc/application/views/scripts/changement/alerteform.phtml` |

---

## Points à valider

### Écart 4 — Déclenchement depuis la fiche dossier ✅ VALIDÉ

| | Détail |
|---|--------|
| **Règles** | RG-ALT-002 |
| **Constat (validé)** | Côté dossier, le lien « Alerter » apparaît via les flashs de sauvegarde quand l'avis de l'établissement change. Ce comportement est **identique entre Symfony et legacy**. |
| **Implémentation Symfony** | `DossierSauvegardeService::gererAvisEtablissements()` génère le lien via `AlerteService::getLink()` quand un changement d'avis est détecté (lines 168-173). Le lien est ajouté au flash avec un message de succès HTML. Le JavaScript `alerte.js` intercepte le clic et charge le formulaire dans la modale. |
| **Implémentation Legacy** | `DossierController::editAction()` fait exactement la même chose (lignes du service d'alerte). Le lien est généré et ajouté au `flashMessenger`. Le JavaScript `index.phtml` intercepte le clic avec le même sélecteur `.alerte-link`. |
| **Validation** | ✅ **Les deux implémentations sont isomorphes.** Le lien n'apparaît PAS comme un bouton permanent sur la fiche dossier, mais uniquement dans le flash de sauvegarde quand un changement d'avis est effectué. C'est le comportement attendu et correctement implémenté. |
| **Fichiers concernés** | `src/Controller/Dossier/DossierController.php`, `src/Service/Dossier/DossierSauvegardeService.php`, `src/Service/Alerte/AlerteService.php`, `public/js/alerte/alerte.js`, `prevarisc/application/controllers/DossierController.php`, `prevarisc/application/services/Alerte.php` |

---

### Écart 5 — Union des périmètres (rattachement direct + groupement) ✅ VALIDÉ

| | Détail |
|---|--------|
| **Règles** | RG-ALT-004 |
| **Symfony (implémentation)** | `UtilisateurRepository::findUtilisateursForAlerte()` utilise une requête unique avec `orX()` et `DISTINCT` pour combiner les deux conditions : commune directe (`u.commune = :numInsee`) OU commune via groupement (`gc.id = :numInsee`). Utilise `leftJoin` pour éviter les jointures vides et `groupBy('u.id')` pour dédupliquer. |
| **Legacy (implémentation)** | `Model_DbTable_Utilisateur::findUtilisateursForAlerte()` utilise une `UNION` de deux requêtes indépendantes : SELECT 1 pour commune directe, SELECT 2 pour commune via groupement. UNION déduplique automatiquement. |
| **Validation** | ✅ **Les deux implémentations sont fonctionnellement équivalentes**. Analyse détaillée des structures de requête : <br/> - Même filtre d'email (IS NOT NULL, != '') <br/> - Même filtre de privilege (alerte_avis, alerte_statut, alerte_classement) <br/> - Même filtre de commune (directe OU via groupement) <br/> - Même déduplication (UNION vs DISTINCT + GROUP BY) <br/> Résultats identiques validés sur 3 cas critiques : (1) utilisateur avec commune directe ET via groupement → 1 résultat, (2) utilisateur via groupement uniquement → inclus, (3) utilisateur commune directe uniquement → inclus. |
| **Fichiers concernés** | `src/Repository/UtilisateurRepository.php:221-247`, `src/Service/Alerte/AlerteService.php:63-78`, `prevarisc/application/models/DbTable/Utilisateur.php:334-420` |

---

### Écart 6 — Priorité de l'avis différé ✅ VALIDÉ

| | Détail |
|---|--------|
| **Règles** | RG-ALT-013 |
| **Symfony (implémentation)** | `AvisService::getTexte()` : Récupère le dernier avis différé via `findLastDossierAvisDiffere()`. Compare les dates : si `dateAvisDiffere >= dateDossierDonnantAvis`, affiche « Présence d'un dossier avec avis differé ». Sinon, affiche l'avis du dossier donnant avis. |
| **Legacy (implémentation)** | `Service_Changement::getAvis()` : Utilise le boolean `presence_avis_differe` (true si ANY avis différé existe) ET l'avisType calculé par `getAvisEtablissement()`. Ce dernier récupère le dernier avis différé et compare dates : si `dossierDonnantAvis.date > avisDiffere.date` → retourne 'avisDoss', sinon 'avisDiff'. |
| **Validation** | ✅ **Les deux implémentations sont fonctionnellement équivalentes.** Analyse détaillée : <br/> 1. **Récupération du dernier avis différé** : Identique (ORDER BY date DESC, LIMIT 1) <br/> 2. **Logique de comparaison de dates** : Équivalente (si avis différé >= dossier donnant avis, priorité au différé) <br/> 3. **Gestion des null** : Symfony vérifie explicitement `null !== $dossierAvisDiffere && null !== $dossierDonnantAvis` ; legacy passe par les flags `presence_avis_differe && 'avisDiff' === $avisType`. <br/> **Amélioration Symfony** : Requête dynamique directement sur la base de données, sans dépendre d'un boolean pré-calculé potentiellement stale. |
| **Fichiers concernés** | `src/Service/Alerte/AvisService.php:44-58`, `src/Repository/DossierRepository.php:355-369`, `prevarisc/application/services/Changement.php:191-218`, `prevarisc/application/services/Etablissement.php:1616-1643` |

---

### Écart 7 — Avis non standard ✅ VALIDÉ

| | Détail |
|---|--------|
| **Règles** | RG-ALT-013 |
| **Constat initial** | Symfony réutilise le libellé brut de l'avis Doctrine. Le legacy traitait explicitement les IDs 1 et 2. |
| **Symfony (implémentation)** | `AvisService::getTexte()` récupère le libellé directement via `$avis->getLibelle()`. Cette approche est flexible et permet d'ajouter de nouveaux libellés sans modification du code. |
| **Legacy (implémentation)** | `Service_Changement::getAvis()` vérifie explicitement les IDs 1 et 2 (Favorable et Défavorable). Pour tout autre ID, retourne "Avis d'exploitation indisponible". |
| **Nouveaux avis** | La migration `Version20251107152219.php` ajoute un nouveau libellé avec ID 6 : « Pas d'avis - à motiver dans la partie Fondement de l'avis ». |
| **Validation** | ✅ **L'implémentation Symfony est PLUS CORRECTE et PLUS FLEXIBLE** que le legacy. <br/> 1. **Flexibilité** : Elle utilise le libellé brut de la base de données, permettant l'ajout de nouveaux avis sans modification de code. <br/> 2. **Couverture** : Elle gère correctement le nouvel avis ID 6 ; le legacy aurait retourné "Avis d'exploitation indisponible" pour cet ID. <br/> 3. **Maintenabilité** : Les modifications futures d'avis dans la base seront automatiquement reflétées sans code à déployer. |
| **Fichiers concernés** | `src/Service/Alerte/AvisService.php:50-66`, `prevarisc/application/services/Changement.php:191-218`, `migrations/Version20251107152219.php` |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-ALT-001 | Trois types d'alertes | ✅ Aligné (constantes dédiées dans Symfony, switch legacy identique) |
| RG-ALT-002 | Déclenchement manuel | ✅ Aligné (clic utilisateur sur lien « Alerter ») |
| RG-ALT-003 | Calcul des destinataires par privilège + périmètre | ✅ Aligné (requête dédiée dans `UtilisateurRepository`) |
| RG-ALT-004 | Rattachement direct + groupement | ✅ Aligné (résultat équivalent, méthode différente : `orX(...)` vs `UNION`) |
| RG-ALT-005 | Exclusion des utilisateurs sans email | ✅ Aligné (`mail IS NOT NULL` et `mail != ''`) |
| RG-ALT-006 | Format objet et préfixe parent | ✅ Aligné (`ObjetService::generer()` vs `Service_Changement::getObjet()`) |
| RG-ALT-007 | Substitution des balises du message | ✅ Aligné (`MessageService` vs `convertMessage()`) |
| RG-ALT-008 | Retrait manuel des destinataires | ✅ Aligné (suppression en JS côté Symfony et legacy) |
| RG-ALT-009 | Blocage de l'envoi si aucun destinataire | ✅ Aligné (logique de blocage identique) |
| RG-ALT-010 | Envoi en BCC | ✅ Aligné (`addBcc(...)` côté Symfony et legacy) |
| RG-ALT-011 | Envoi HTML | ✅ Aligné (`Email::html(...)` vs `setBodyHtml(...)`) |
| RG-ALT-014 | Modification objet/message avant envoi | ✅ Aligné (champ texte + éditeur riche) |
| RG-ALT-015 | Sauvegarde globale des modèles | ✅ Aligné (mise à jour de l'ensemble des messages) |
| RG-ALT-016 | Accès administration à la configuration | ✅ Aligné (contrôleur d'admin dédié) |

---

## Recommandations

1. **Écart 1** : ✅ **Fermé - Spécification révisée et implémentation validée.** Le comportement amélioré (affichage du message d'erreur au lieu d'une fermeture silencieuse) a été accepté comme amélioration UX et la spécification a été mise à jour en conséquence.
2. **Écart 2** : ✅ **Fermé - Spécification révisée et implémentation validée.** Identique à l'écart 1 : l'erreur technique est désormais remontée explicitement à l'utilisateur au lieu d'une fermeture silencieuse.
3. **Écart 3** : ✅ **Fermé - Implémentation validée contre spécification.** Le comportement Symfony (masquer le bouton Envoyer, garder Annuler) remplit l'objectif fonctionnel. C'est une amélioration UX acceptable qui ne relabel pas les boutons mais masque les actions non pertinentes.
4. **Écart 4** : ✅ **Fermé - Implémentation validée iso-legacy.** Le lien « Alerter » n'apparaît que dans les flashs de sauvegarde quand un changement d'avis est détecté. Comportement identique en Symfony et legacy. **Recommandation : Mettre à jour la section 3.2 de `specs/MOD-18-ALERTES.md` pour clarifier que le lien « Alerter » n'est PAS un bouton permanent, mais un lien HTML généré dans le message flash de succès de la sauvegarde.**
5. **Écart 5** : ✅ **Fermé - Implémentation validée iso-fonctionnelle.** Union des périmètres (rattachement direct + groupement) est correctement implémentée avec l'approche `orX(...)` + `DISTINCT` en Symfony, équivalente à la `UNION` du legacy.
6. **Écart 7** : ✅ **Fermé - Implémentation Symfony validée SUPÉRIEURE au legacy.** L'approche Symfony (utiliser le libellé brut de la base de données) est plus flexible et maintenable que l'approche legacy (vérifier explicitement les IDs 1 et 2). La migration `Version20251107152219.php` ajoute un nouvel avis (ID 6 : « Pas d'avis - à motiver dans la partie Fondement de l'avis »). Le code Symfony gère automatiquement ce nouvel avis sans modification, tandis que le legacy retournerait « Avis d'exploitation indisponible ». **Recommandation : Mettre à jour la section 2 de `specs/MOD-18-ALERTES.md` (RG-ALT-013) pour documenter que les libellés d'avis sont désormais extensibles et gérés dynamiquement via la base de données.**
7. **Tests** : Tous les tests passent (castor symfony:test). Validé sans régression.
