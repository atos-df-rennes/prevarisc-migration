# MOD-18 — Alertes et notifications — Rapport d'écarts

## Synthèse

| Indicateur | Valeur |
|---|---:|
| Règles de gestion analysées | 16 |
| Écarts confirmés | 3 |
| Points à vérifier | 4 |
| Niveau de risque | Moyen |
| Niveau de confiance | Bon |

### Constats clés

- La migration couvre correctement l'architecture du module : configuration des messages, modale d'alerte, calcul des destinataires, génération de l'objet, substitution des balises, envoi HTML en BCC.
- Les règles structurantes restent alignées avec le legacy : trois types d'alertes, filtrage par privilège + périmètre communal/groupement, objet pré-rempli et modifiable, message pré-rempli et modifiable.
- Les écarts confirmés portent surtout sur le **comportement de la modale en erreur** : Symfony affiche des toasts et garde la modale ouverte, là où le legacy se fermait silencieusement.
- Le cas « aucun destinataire » n'est pas reproduit à l'identique : le bouton Envoyer est masqué, mais il n'est pas remplacé par un bouton « Fermer » comme dans le legacy et la spec.
- Une divergence métier possible subsiste sur la résolution de `{etablissementAvis}` quand un avis différé existe sans dossier donnant avis exploitable.

## Écarts détaillés

### Écarts confirmés

| Règle | Spec | Symfony | Legacy | Impact | Fichiers |
|---|---|---|---|---|---|
| RG-ALT-012 / cas « service de messagerie désactivé » | Si le service mail est désactivé, aucun email n'est envoyé et l'échec reste silencieux. | Le contrôleur retourne un JSON `success=false` avec message explicite, puis le JS affiche un toast d'erreur et laisse la modale ouverte. | Le service mail legacy retourne `false` silencieusement et la modale se ferme normalement. | Régression UX : l'utilisateur voit une erreur alors que la spec décrit un échec silencieux. | `src/Controller/AlerteController.php`, `templates/alerte/formulaire.html.twig`, `prevarisc/application/services/Mail.php`, `prevarisc/application/views/scripts/dossier/index.phtml` |
| Cas « erreur technique lors de l'envoi » | En cas d'erreur technique, aucune notification spécifique n'est affichée ; la modale se ferme normalement. | En cas d'échec métier ou AJAX, Symfony affiche un toast d'erreur et réactive le bouton Envoyer sans fermer la modale. | Le legacy ferme la boîte de dialogue immédiatement après clic sur Envoyer, sans attendre la réponse. | Régression UX confirmée par rapport à la spec et au legacy. | `templates/alerte/formulaire.html.twig`, `prevarisc/application/views/scripts/dossier/index.phtml` |
| RG-ALT-009 — bouton « Fermer » sans destinataire | Si aucun destinataire n'est disponible, le bouton Envoyer doit être remplacé par « Fermer ». | Symfony masque le bouton Envoyer et conserve seulement le bouton `Annuler`. | Le legacy renomme explicitement le bouton principal en `Fermer`. | Écart UX mineur mais réel vis-à-vis du comportement attendu. | `templates/alerte/formulaire.html.twig`, `prevarisc/application/views/scripts/changement/alerteform.phtml` |

### Points à vérifier

| Règle | Spec | Symfony | Legacy | Impact | Fichiers |
|---|---|---|---|---|---|
| RG-ALT-013 — priorité de l'avis différé | Si un dossier avec avis différé existe, le texte doit devenir « Présence d'un dossier avec avis différé ». | `AvisService` ne passe dans cette branche que si `dossierAvisDiffere` **et** `dossierDonnantAvis` sont tous deux présents. | Le legacy se base d'abord sur `presence_avis_differe` et `avisType === 'avisDiff'`. | Risque de divergence silencieuse sur certains établissements selon les données en base. | `src/Service/Alerte/AvisService.php`, `prevarisc/application/services/Changement.php` |
| RG-ALT-013 — avis non standard | La spec ne décrit que favorable / défavorable / indisponible / avis différé. | Symfony réutilise le libellé brut de l'avis Doctrine puis ajoute éventuellement « à l'exploitation ». | Le legacy traitait explicitement les IDs 1 et 2, sinon restait vide. | À confirmer si la base comporte des avis additionnels : la sortie Symfony peut différer du legacy. | `src/Service/Alerte/AvisService.php`, `prevarisc/application/services/Changement.php` |
| RG-ALT-004 — union rattachement direct + groupement | Les destinataires doivent être calculés par union des deux périmètres. | Symfony fait une requête unique avec `orX(...)`, `DISTINCT` et jointures. | Le legacy utilisait une union SQL de deux requêtes distinctes. | Le résultat semble aligné, mais mérite un test sur des cas de multi-appartenance pour exclure les doublons ou oublis. | `src/Repository/UtilisateurRepository.php`, `prevarisc/application/models/DbTable/Utilisateur.php` |
| RG-ALT-002 — présence du déclenchement côté dossier | L'envoi doit pouvoir être déclenché depuis la fiche établissement ou la fiche dossier. | Côté dossier, le lien « Alerter » semble apparaître via les flashs de sauvegarde plus que comme action permanente. | Le legacy procédait déjà ainsi par lien injecté dans le flux de la page. | Probablement aligné avec l'historique, mais à confirmer si la spec attend un bouton permanent visible. | `src/Controller/Dossier/DossierController.php`, `src/Service/Dossier/DossierSauvegardeService.php`, `prevarisc/application/views/scripts/dossier/index.phtml` |

## Comportements alignés

| Règle | Constat | Symfony | Legacy |
|---|---|---|---|
| RG-ALT-001 | Trois types d'alertes existent | Constantes et mapping des privilèges dédiés | Switch legacy sur les 3 types |
| RG-ALT-002 | Déclenchement manuel | Clic utilisateur sur le lien « Alerter » | Même logique |
| RG-ALT-003 | Calcul des destinataires par privilège + périmètre | Requête dédiée dans `UtilisateurRepository` | Requête legacy dédiée |
| RG-ALT-004 | Prise en compte du rattachement direct et par groupement | `orX(...)` avec jointures | `UNION` SQL legacy |
| RG-ALT-005 | Exclusion des utilisateurs sans email | Filtre `mail IS NOT NULL` et `mail != ''` | Même filtre |
| RG-ALT-006 | Objet au format `<établissement> (<commune>) - <changement>` | `ObjetService::generer()` | `Service_Changement::getObjet()` |
| RG-ALT-006 | Préfixe du parent dans l'objet | Parent ajouté avant le libellé enfant | Même comportement |
| RG-ALT-007 | Balises du message remplacées avec les données réelles | `MessageService` | `convertMessage()` |
| RG-ALT-008 | Retrait manuel des destinataires | Suppression de l'email et du badge visuel en JS | Même logique |
| RG-ALT-009 | Blocage de l'envoi si aucun destinataire | Formulaire masqué et message affiché | Même logique de blocage |
| RG-ALT-010 | Envoi en BCC | `addBcc(...)` pour chaque destinataire | `addBcc(...)` côté legacy |
| RG-ALT-011 | Envoi HTML | `Email::html(...)` | `setBodyHtml(...)` |
| RG-ALT-014 | Objet et message modifiables avant envoi | Champ texte + éditeur riche | Même logique |
| RG-ALT-015 | Sauvegarde globale des modèles d'alerte | Mise à jour de l'ensemble des messages affichés | Même logique |
| RG-ALT-016 | Accès d'administration à la configuration | Contrôleur d'admin dédié | Entrée dans le périmètre admin legacy |

## Recommandations

1. **Réaligner le comportement de la modale en erreur** : fermeture silencieuse ou, à défaut, documenter explicitement l'évolution UX si l'équipe souhaite conserver les toasts.
2. **Traiter le cas “service mail désactivé” comme un non-envoi silencieux** si l'iso-fonctionnalité stricte avec la spec est recherchée.
3. **Remplacer l'état “Annuler seul” par “Fermer”** quand aucun destinataire n'est disponible.
4. **Valider par tests métier la logique de `{etablissementAvis}`** sur les cas d'avis différé et sur les avis non standard.
5. **Tester le calcul des destinataires sur des cas de multi-rattachement** (commune directe + groupement) pour verrouiller l'équivalence avec le legacy.
