# Rapport d'écarts — MOD-08 CONTACTS

> **Date** : 2026-05-22  
> **Spec source** : `docs/specs/MOD-08-CONTACTS.md`  
> **Méthode** : Comparaison spec ↔ code Symfony ↔ code legacy Zend

---

## Synthèse

| Indicateur | Valeur |
|------------|--------|
| Règles de gestion analysées | 16 (RG-CTT-001 à RG-CTT-016) |
| Écarts spec ↔ Symfony | 5 (1 traité ✅) |
| Écarts spec ↔ Legacy | 1 |
| Écarts Symfony ↔ Legacy | 5 (1 corrigé ✅) |
| Impact critique | 0 (1 risque critique éliminé ✅) |

---

## Écarts détaillés

### Écart 1 — La fonction « Maire » reste proposée lors de la création

| | Détail |
|---|--------|
| **Règles** | RG-CTT-005, § 3.2, § 8.2 |
| **Spec** | La fonction « Maire » ne doit pas être proposée en création standard. Elle n'est visible qu'en édition d'un contact existant portant déjà cette fonction, ou en contexte « commune ». |
| **Symfony** | `ContactType` expose toujours le champ `fonction` sans filtrer les choix (`src/Form/ContactType.php:18-22`). Aucun contexte de formulaire ne masque « Maire ». |
| **Legacy** | Le formulaire Zend exclut explicitement `ID_FONCTION = 2` tant qu'on n'édite pas un contact existant (`application/views/scripts/contact/form.phtml:21-35`). |
| **Impact** | **Moyen** — Symfony permet de créer des contacts « Maire » hors du contexte prévu par la règle métier et par le legacy. |
| **Fichiers concernés** | `prevarisc-migration/src/Form/ContactType.php`, `prevarisc/application/views/scripts/contact/form.phtml` |

**Retour utilisateur** : Écart de migration confirmé. Par ailleurs je ne constate pas de présélection de la valeur "Maire". J'ai uniquement constaté le masquage de cette fonction à l'ajout et l'affichage lors de la modification. Une analyse du contexte "commune" est à effectuer pour déterminer sur quels écrans et suite à quelles actions ce contexte apparaît et déclenche la règle de présélection.

---

### Écart 2 — L'héritage des contacts d'établissement ne remonte qu'au parent direct

| | Détail |
|---|--------|
| **Règles** | RG-CTT-009, RG-CTT-015, § 6 |
| **Spec** | Une cellule doit afficher les contacts du parent direct **et** du grand-parent (maximum deux niveaux), notamment le DUS. |
| **Symfony** | L'onglet Contacts ne parcourt que `getParents()` sur l'établissement courant, puis charge les contacts de ces parents directs (`src/Controller/Etablissement/EtablissementContactController.php:60-69`). L'entité `Etablissement` n'expose ici qu'une collection de parents directs (`src/Entity/Etablissement.php:929-932`). |
| **Legacy** | Le legacy remonte explicitement deux niveaux via `getAllParents()` qui renvoie `[parent, grand-parent]` (`application/models/DbTable/Etablissement.php:255-263`), puis fusionne les contacts des deux niveaux (`application/controllers/EtablissementController.php:453-461`). |
| **Impact** | **Élevé** — un DUS porté uniquement par le grand-parent ne sera pas visible dans l'onglet Contacts Symfony d'une cellule, contrairement au legacy et à la spec. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/Etablissement/EtablissementContactController.php`, `prevarisc-migration/src/Entity/Etablissement.php`, `prevarisc/application/models/DbTable/Etablissement.php`, `prevarisc/application/controllers/EtablissementController.php` |

**Retour utilisateur** : Écart de migration confirmé. Aligner avec le legacy pour remonter de deux niveaux.

---

### Écart 3 — La suppression peut effacer un contact encore lié à une autre entité du même type ✅ Corrigé

| | Détail |
|---|--------|
| **Règles** | RG-CTT-006, RG-CTT-007, § 5.4, § 8.3 |
| **Spec** | Si au moins une autre association subsiste, seule la dissociation courante doit être supprimée ; la fiche contact doit être conservée. |
| **Symfony (avant)** | Les contrôleurs retiraient d'abord l'association (`removeContact()`), puis appelaient `Contact::isUsed()` avec un seuil `1` pour le type courant (`src/Controller/Etablissement/EtablissementContactController.php:179-183`, `src/Controller/Dossier/DossierContactController.php:194-198`, `src/Controller/GroupementController.php:299-303`, `src/Controller/Commission/CommissionContactsController.php:139-143`, `src/Service/Contact.php:67-75`). Cette séquence créait une dépendance au timing Doctrine : si un `flush()` était inséré entre `removeContact()` et `isUsed()`, le contact aurait pu être supprimé à tort. |
| **Legacy** | Le legacy compte les associations **avant** suppression et conserve la fiche dès qu'il en reste au moins une sur n'importe quel type (`application/controllers/ContactController.php:241-255`). |
| **Solution** | Vérification de `isUsed()` **avant** `removeContact()`, stockage du résultat dans une variable temporaire (`$isStillUsed`), puis suppression de l'association et du contact en toute sécurité. Élimine la fragilité de timing et aligne le pattern avec le legacy. |
| **Fichiers modifiés** | `prevarisc-migration/src/Controller/Etablissement/EtablissementContactController.php` (ligne 179), `prevarisc-migration/src/Controller/Dossier/DossierContactController.php` (ligne 176), `prevarisc-migration/src/Controller/GroupementController.php` (ligne 299), `prevarisc-migration/src/Controller/Commission/CommissionContactsController.php` (ligne 139) |

---

### Écart 4 — L'autocomplétion Symfony est plafonnée à 10 résultats au lieu de 100

| | Détail |
|---|--------|
| **Règles** | RG-CTT-012, RG-CTT-013, § 5.5 |
| **Spec** | La recherche doit démarrer à partir de 3 caractères et proposer jusqu'à **100** résultats. |
| **Symfony** | Le front respecte bien le seuil des 3 caractères, mais le repository applique `setMaxResults(10)` (`src/Repository/UtilisateurInformationsRepository.php:48-67`). |
| **Legacy** | Le widget jQuery autocomplete est configuré avec `max: 100` (`application/views/scripts/contact/index.phtml:206-221`). Le backend Zend ne réduit pas la requête à 10 résultats (`application/models/DbTable/UtilisateurInformations.php:75-84`). |
| **Impact** | **Moyen** — en base volumineuse, Symfony masque une partie importante des contacts existants que le legacy pouvait encore proposer. |
| **Fichiers concernés** | `prevarisc-migration/src/Repository/UtilisateurInformationsRepository.php`, `prevarisc/application/views/scripts/contact/index.phtml`, `prevarisc/application/models/DbTable/UtilisateurInformations.php` |

---

### Écart 5 — L'invalidation de cache prévue par la spec n'est pas portée en Symfony

| | Détail |
|---|--------|
| **Règles** | RG-CTT-014 |
| **Spec** | Toute opération d'ajout, modification ou suppression d'un contact doit invalider le cache de l'entité concernée. |
| **Symfony** | Aucun des contrôleurs migrés de contacts n'appelle de mécanisme d'invalidation après ajout / édition / suppression. |
| **Legacy** | Le legacy tente d'invalider le cache à l'ajout (`application/controllers/ContactController.php:135-137`) et à l'édition (`application/controllers/ContactController.php:170-175`), mais l'implémentation reste partielle et la suppression vise une clé incorrecte (`application/controllers/ContactController.php:257-258`, usage de l'id contact au lieu de l'id entité). |
| **Impact** | **Faible à moyen** — la spec n'est pas tenue dans Symfony ; l'écart existait déjà partiellement en legacy mais n'a pas été repris ni corrigé. |
| **Fichiers concernés** | `prevarisc-migration/src/Controller/ContactController.php`, `prevarisc-migration/src/Controller/Etablissement/EtablissementContactController.php`, `prevarisc-migration/src/Controller/Dossier/DossierContactController.php`, `prevarisc-migration/src/Controller/GroupementController.php`, `prevarisc-migration/src/Controller/Commission/CommissionContactsController.php`, `prevarisc/application/controllers/ContactController.php` |

---

## Comportements alignés (confirmés)

| Règles | Sujet | Constat |
|--------|-------|---------|
| RG-CTT-001 | Unicité de l'association | ✅ Aligné : les entités Doctrine évitent les doublons via `addContact()` et les tables d'association conservent une clé composite côté base. |
| RG-CTT-002 / RG-CTT-003 | Partage d'une fiche contact entre plusieurs entités + modification globale | ✅ Aligné : Symfony édite la même entité `UtilisateurInformations` pour tous les rattachements (`src/Controller/ContactController.php:44-125`). |
| RG-CTT-004 | Civilité facultative | ✅ Aligné : `civilite` est optionnelle dans `ContactType` (`src/Form/ContactType.php:26-30`). |
| RG-CTT-010 | Contacts hérités en lecture seule | ✅ Aligné : les contacts parents sont rendus avec `can_edit: false` dans l'onglet établissement (`templates/etablissement/contacts.html.twig:18-27`). |
| RG-CTT-011 | Tri alphabétique | ✅ Aligné : tri par nom puis prénom dans les repositories / critères Doctrine (`src/Repository/EtablissementRepository.php:96-111`, `src/Repository/DossierRepository.php:633-645`, `src/Doctrine/ContactCriteria.php:9-13`). |
| RG-CTT-012 / RG-CTT-013 | Recherche à partir de 3 caractères et exclusion des utilisateurs | ✅ Aligné sur le principe : seuil géré côté UI (`templates/etablissement/contacts.html.twig:110-140`, `templates/dossier/contacts/contacts.html.twig:94-123`, `public/js/contacts-panel.js:38-43`) et exclusion via `NOT IN utilisateur` (`src/Repository/UtilisateurInformationsRepository.php:31-68`). |
| RG-CTT-016 | Reprise de contacts établissement → dossier à la création | ✅ Aligné : Symfony copie les contacts de fonctions métier ciblées (Exploitant, DUS, Responsable sécurité, Propriétaire) comme le legacy (`src/Service/Dossier/DossierCreationService.php:48-75`, `application/controllers/DossierController.php:984-1002`). |

---

## Recommandations

1. **Priorité critique** ✅ Traitée : correction de la logique de suppression dans les contrôleurs pour vérifier les associations restantes **avant** suppression, éliminant la dépendance au timing Doctrine.
2. **Priorité haute** : compléter l'héritage des contacts d'établissement sur **deux niveaux** (parent + grand-parent) dans `EtablissementContactController` pour restaurer la visibilité du DUS hérité.
3. **Priorité moyenne** : ajouter un filtrage contextuel des fonctions dans `ContactType` afin de masquer « Maire » en création standard et ne l'autoriser qu'en édition / contexte commune.
4. **Priorité moyenne** : relever l'autocomplétion à 100 résultats maximum pour retrouver le comportement attendu par la spec et par l'UI legacy.
5. **Priorité basse** : définir une stratégie d'invalidation de cache cohérente côté Symfony, ou documenter explicitement dans la spec que cette règle n'est plus applicable.
