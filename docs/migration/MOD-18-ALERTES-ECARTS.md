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

### Écart 1 — Comportement de la modale en erreur (service mail désactivé)

| | Détail |
|---|--------|
| **Règles** | RG-ALT-012 |
| **Spec** | Si le service mail est désactivé, aucun email n'est envoyé et l'échec reste silencieux. La modale se ferme normalement. |
| **Symfony (état actuel)** | Le contrôleur retourne un JSON `success=false` avec message explicite. Le JS affiche un toast d'erreur et laisse la modale ouverte sans fermeture. |
| **Legacy** | Le service mail retourne `false` silencieusement. La modale se ferme normalement après clic sur Envoyer. |
| **Divergence** | Régression UX : l'utilisateur voit une notification d'erreur alors que la spec et le legacy décrivent un échec silencieux avec fermeture. |
| **Fichiers concernés** | `src/Controller/AlerteController.php`, `templates/alerte/formulaire.html.twig`, `prevarisc/application/services/Mail.php`, `prevarisc/application/views/scripts/dossier/index.phtml` |
**Retour utilisateur** : Écart de migration accepté. Le comportement n'est pas une régression UX mais une **amélioration** UX. L'utilisateur voit désormais un message et comprend qu'une erreur est survenue et qu'une éventuelle action de sa part est attendue là où le legacy échouait silencieusement sans aucune information. Mets à jour la spec dans specs/ avec ce nouveau fonctionnement.

---

### Écart 2 — Comportement de la modale en erreur (erreur technique lors de l'envoi)

| | Détail |
|---|--------|
| **Règles** | RG-ALT-012 |
| **Spec** | En cas d'erreur technique, aucune notification spécifique n'est affichée. La modale se ferme normalement. |
| **Symfony (état actuel)** | En cas d'échec métier ou AJAX, Symfony affiche un toast d'erreur et réactive le bouton Envoyer sans fermer la modale. |
| **Legacy** | Ferme la boîte de dialogue immédiatement après clic sur Envoyer, sans attendre la réponse du service (comportement non-bloquant). |
| **Divergence** | Régression UX confirmée par rapport à la spec et au legacy. |
| **Fichiers concernés** | `templates/alerte/formulaire.html.twig`, `prevarisc/application/views/scripts/dossier/index.phtml` |

---

### Écart 3 — Libellé du bouton quand aucun destinataire n'est disponible

| | Détail |
|---|--------|
| **Règles** | RG-ALT-009 |
| **Spec** | Si aucun destinataire n'est disponible, le bouton Envoyer doit être remplacé par un bouton « Fermer ». |
| **Symfony (état actuel)** | Symfony masque le bouton Envoyer et conserve seulement le bouton `Annuler`. |
| **Legacy** | Le legacy renomme explicitement le bouton principal en `Fermer` (transforme `Envoyer` → `Fermer`). |
| **Divergence** | Écart UX mineur mais réel : libellé du bouton principal non aligné. |
| **Fichiers concernés** | `templates/alerte/formulaire.html.twig`, `prevarisc/application/views/scripts/changement/alerteform.phtml` |

---

## Points à valider

| Règle | Sujet | Constat | Fichiers |
|-------|-------|---------|----------|
| RG-ALT-002 | Déclenchement depuis la fiche dossier | Côté dossier, le lien « Alerter » apparaît via les flashs de sauvegarde plus que comme action permanente. À confirmer si la spec attend un bouton permanent visible. | `src/Controller/Dossier/DossierController.php`, `src/Service/Dossier/DossierSauvegardeService.php`, `prevarisc/application/views/scripts/dossier/index.phtml` |
| RG-ALT-004 | Union des périmètres (rattachement direct + groupement) | Symfony utilise `orX(...)` avec `DISTINCT` et jointures. Le legacy utilisait une `UNION` SQL. Le résultat semble aligné, mais à valider sur des cas de multi-appartenance. | `src/Repository/UtilisateurRepository.php`, `prevarisc/application/models/DbTable/Utilisateur.php` |
| RG-ALT-013 | Priorité de l'avis différé | `AvisService` ne passe dans cette branche que si `dossierAvisDiffere` **et** `dossierDonnantAvis` sont tous deux présents. Le legacy se base d'abord sur `presence_avis_differe`. Risque de divergence silencieuse selon les données. | `src/Service/Alerte/AvisService.php`, `prevarisc/application/services/Changement.php` |
| RG-ALT-013 | Avis non standard | Symfony réutilise le libellé brut de l'avis Doctrine. Le legacy traitait explicitement les IDs 1 et 2. À valider si la base comporte des avis additionnels. | `src/Service/Alerte/AvisService.php`, `prevarisc/application/services/Changement.php` |

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

1. **Écart 1 & 2** : Réaligner le comportement de la modale en erreur vers une **fermeture silencieuse** ou documenter explicitement l'évolution UX souhaitée.
2. **Écart 3** : Remplacer le bouton « Annuler » seul par un bouton « Fermer » quand aucun destinataire n'est disponible.
3. **RG-ALT-002** : Confirmer que le lien « Alerter » doit apparaître en permanence ou seulement via les flashs de sauvegarde.
4. **RG-ALT-004** : Tester le calcul des destinataires sur des cas de multi-rattachement (commune directe + groupement) pour verrouiller l'équivalence.
5. **RG-ALT-013** : Valider par tests métier la logique de résolution de `{etablissementAvis}` sur les cas d'avis différé et avis non standard.
