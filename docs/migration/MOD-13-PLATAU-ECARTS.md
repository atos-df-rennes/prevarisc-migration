# Rapport d'écarts — MOD-13 PLAT'AU

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-13-PLATAU.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend ↔ passerelle `prevarisc-passerelle-platau`

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 38 (RG-PLT-001 à RG-PLT-038) |
| Écarts spec ↔ Symfony / passerelle | 3 |
| Écarts partagés avec legacy | 1 |
| Niveau de confiance | ⚠️ Bon sur l'UI, la recherche et les PJ ; moyen sur les aspects d'infrastructure déportés dans la passerelle |

### Constats clés

- Le périmètre fonctionnel visible côté dossier est largement migré : identifiant Plat'AU, temps restant, statuts PEC/Avis, relances, sélection et suivi des pièces jointes, badge « Nouveau ! ».
- La recherche Symfony couvre bien la provenance Plat'AU et la recherche libre par identifiant `XXX-XXX-XXX`.
- Les flux asynchrones (lecture des notifications, import des consultations/pièces, export PEC, export avis) sont bien couverts, mais **dans la passerelle dédiée**, pas dans le cœur Symfony.
- Deux écarts concrets ressortent : **absence de blocage d'ouverture d'un dossier Plat'AU si le module est désactivé** et **absence de support proxy visible dans la passerelle**, alors que le legacy et le healthcheck Symfony le prévoient.

---

## Écarts détaillés

### Écart 1 — L'ouverture d'un dossier Plat'AU n'est plus bloquée quand le module est désactivé

**✅ VALIDÉ**

| | Détail |
|---|--------|
| **Règles** | RG-PLT-002, §7.4 |
| **Spec** | Si un dossier possède un identifiant de consultation Plat'AU et que le module est désactivé, l'ouverture du dossier doit être bloquée avec une erreur explicite. |
| **Symfony** | ✅ **Conforme** — `DossierController::index()` (ligne 252-254) et `DossierController::edit()` (ligne 424-426) incluent le garde préalable sur `$this->deactivatePlatau`. Configuration injected via `services.php` et déclarée dans `config/platau.php`. Exception levée avec message explicite : `"Plat'AU est désactivé, vous ne pouvez pas voir ce dossier"`. |
| **Legacy** | ✅ Comportement identique : `DossierController` bloque explicitement l'accès si `ID_PLATAU` est présent et que `PREVARISC_DEACTIVATE_PLATAU` est vrai. |
| **Impact** | **Résolu** — Conformité rétablie à la règle d'activation/désactivation du module. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Dossier/DossierController.php:252-254,424-426`, `prevarisc-migration/config/platau.php:3-6`, `prevarisc-migration/config/services.php:155-168` |

**Retour utilisateur** : Comportement implémenté après détection de cet écart. Validation confirmée par examen du code et exécution des tests (tests et PHPStan passent sans erreur).

---

### Écart 2 — Le support proxy n'est visible que pour le healthcheck Symfony, pas pour les flux asynchrones de la passerelle

**✅ VALIDÉ (hors scope)**

| | Détail |
|---|--------|
| **Règles** | RG-PLT-032, §7.8 |
| **Spec** | Les communications avec PISTE et Plat'AU doivent pouvoir passer par un proxy HTTP configurable (protocole, hôte, port, authentification). |
| **Symfony** | Le healthcheck Symfony est aligné : `PlatauHealthcheckService` reconstruit les options cURL via `ProxyConfigurationService`, en continuité du legacy. |
| **Passerelle** | La config documentée dans `README.md` ne prévoit aucun bloc proxy, et `PlatauAbstract` ne définit ni option proxy dans `OptionsResolver`, ni paramètre `proxy` dans le client Guzzle utilisé par les commandes `lecture-notifications`, `export-pec`, `export-avis`. |
| **Legacy** | `Service_PlatauClient` charge bien `PREVARISC_PROXY_*` et configure `CURLOPT_PROXYTYPE`, `CURLOPT_PROXYPORT`, `CURLOPT_PROXY`, `CURLOPT_PROXYUSERPWD`. |
| **Impact** | **Aucun** — la passerelle Plat'AU est un composant externe hors du scope de cette migration Symfony. |
| **Fichiers concernés** | `docs/specs/MOD-13-PLATAU.md:214-215,265-267`, `prevarisc-migration/src/Service/PlatauHealthcheckService.php:296-307`, `prevarisc-passerelle-platau/README.md:48-77`, `prevarisc-passerelle-platau/src/Service/PlatauAbstract.php:38-102`, `prevarisc/application/services/PlatauClient.php:7-19` |

**Retour utilisateur** : La passerelle Plat'AU est un composant à part et hors scope de cette revue. Aucune action requise.

---

### Écart 3 — L'export recherche ajoute toujours l'en-tête « Identifiant PLATAU », même sans filtre provenance Plat'AU

| | Détail |
|---|--------|
| **Règles** | RG-PLT-035 |
| **Spec** | La colonne « Identifiant Plat'AU » doit être incluse **lorsque** le filtre de provenance Plat'AU est sélectionné. |
| **Symfony** | `SearchExport::exportDossiers()` écrit toujours l'en-tête de colonne, puis ne renseigne les cellules que si `provenance == '1'`. |
| **Legacy** | Même comportement : l'en-tête est toujours créé, la valeur n'est écrite que si `provenance == '1'`. |
| **Impact** | **Faible** — la donnée est correctement remplie quand le filtre est actif, mais le comportement reste plus large que la formulation de la spec. |
| **Fichiers concernés** | `docs/specs/MOD-13-PLATAU.md:223`, `prevarisc-migration/src/Service/SearchExport.php:155-197`, `prevarisc/application/controllers/SearchController.php:400-473` |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-PLT-001 / 024 / 036 / 037 / 038 | Activation du module, blocs dashboard et droits | ✅ Aligné : les blocs dashboard Plat'AU sont conditionnés par l'activation du module et par les droits utilisateur. |
| RG-PLT-003 / 005 / 006 / 007 | Identification consultation, affichage dossier, date de réponse et urgence | ✅ Aligné : l'identifiant Plat'AU est visible dans l'en-tête et le bloc d'information, avec calcul du temps restant et code couleur. |
| RG-PLT-008 à 015 | Statuts PEC / Avis et relances | ✅ Aligné : statuts affichés, relances conditionnelles, remise en attente et prise en compte des cas métier côté Symfony + passerelle. |
| RG-PLT-016 à 023 | Pièces jointes Plat'AU | ✅ Aligné : stockage spécifique, sélection des pièces non envoyées, passage à l'état d'attente, interdiction de suppression selon statut, badge « Nouveau ! », identifiant Plat'AU sous le nom et bouton « Renvoyer » en cas d'erreur. |
| RG-PLT-029 / 030 / 031 | Vérification de connexion, OAuth2 client credentials, cache du jeton | ✅ Aligné côté Symfony : healthcheck automatique, OAuth2 PISTE et réutilisation du token avec marge de 5 minutes. |
| RG-PLT-033 / 034 | Recherche et filtre provenance | ✅ Aligné : filtre de provenance conditionnel et détection regex de l'identifiant `XXX-XXX-XXX` en recherche libre. |
| Flux asynchrones métier | Import consultations / notifications / PJ, export PEC / avis | ✅ Couvert par la passerelle : `LectureNotifications`, `ExportPEC` et `ExportAvis` implémentent bien les échanges Plat'AU externalisés. |
| §7.9 | Métadonnées des pièces Plat'AU | ✅ Aligné : type, sous-type, nature, date de dépôt peuvent être affichés dans la description lorsque la description libre est absente. |

---

## Recommandations

1. **Priorité basse** : documenter explicitement, dans la doc technique de migration, que les échanges entrants/sortants Plat'AU (lecture-notifications, export-pec, export-avis) sont externalisés dans `prevarisc-passerelle-platau` et non exécutés par l'application Symfony elle-même. Cela clarifierait pourquoi le proxy n'est visible que dans le healthcheck Symfony (RG-PLT-032 est une responsabilité de la passerelle, hors scope).

### Synthèse des validations (2026-07-03)

- ✅ **Écart 1 — Blocage dossier Plat'AU désactivé** : VALIDÉ. La garde sur `PREVARISC_DEACTIVATE_PLATAU` est correctement implémentée dans les méthodes `index()` et `edit()` de `DossierController`, configuration properly injected. Tests et PHPStan passent.
- ✅ **Écart 2 — Support proxy passerelle** : VALIDÉ (hors scope). La passerelle Plat'AU est un composant externe indépendant ; aucune action requise dans le scope Symfony.
