# Migration - Édition des informations générales des dossiers

**Date :** 10 février 2026  
**Dernière mise à jour :** 10 février 2026  
**Statut :** ✅ Phase B Backend 100% complète

---

## Table des matières

1. [Vue d'ensemble](#vue-d'ensemble)
2. [Architecture](#architecture)
3. [Blocs fonctionnels implémentés](#blocs-fonctionnels-implémentés)
4. [Services métier](#services-métier)
5. [Architecture JavaScript](#architecture-javascript)
6. [Validations et règles métier](#validations-et-règles-métier)
7. [Système d'alertes email](#système-d'alertes-email)
8. [Constantes métier](#constantes-métier)
9. [Choix techniques](#choix-techniques)
10. [Tests](#tests)

---

## Vue d'ensemble

### Contexte

L'édition des dossiers est la fonctionnalité la plus complexe de Prevarisc. Elle permet de modifier toutes les informations d'un dossier (nature, dates, avis, affectations, documents manquants, prescriptions, etc.) avec des validations métier strictes et des workflows conditionnels.

**Périmètre migré :**
- ✅ Formulaire d'édition complet (18 blocs fonctionnels)
- ✅ Sauvegarde backend (Phase B) avec toutes les validations
- ✅ JavaScript modulaire (8 modules)
- ✅ Système d'alertes email
- ✅ Validation custom (UniqueUtilisateur)
- ✅ Configuration Mailer dynamique

**Legacy :**
- `prevarisc/application/controllers/DossierController.php` (2000+ lignes)
- `prevarisc/application/views/scripts/dossier/index.phtml` (2567 lignes, 40 listeners, 16 AJAX)

**Migré :**
- `src/Controller/DossierController.php` (257 lignes)
- `src/Form/DossierType.php` (300+ lignes)
- `templates/dossier/edit.html.twig` (600+ lignes)
- `public/js/dossier/*` (8 fichiers modulaires)

---

## Architecture

### Stack technique

| Composant | Technologie | Version |
|-----------|-------------|---------|
| **Framework** | Symfony | 4.4 LTS |
| **PHP** | PHP | 7.1.33 |
| **ORM** | Doctrine | 2.14 |
| **Forms** | Symfony Form | 4.4 |
| **Templates** | Twig | 2.x |
| **JavaScript** | Vanilla JS | ES6 (transpilé) |
| **CSS** | Bootstrap | 3.x |
| **Validation** | Symfony Validator | 4.4 |
| **Email** | Symfony Mailer | 4.4 |

### Structure des fichiers

```
prevarisc-migration/
├── src/
│   ├── Controller/
│   │   ├── DossierController.php          # Controller principal (257 lignes)
│   │   └── AlerteController.php           # Envoi alertes email (175 lignes)
│   ├── Form/
│   │   ├── DossierType.php                # FormType principal (300+ lignes)
│   │   ├── DossierDocumentManquantType.php
│   │   ├── DossierPrescriptionType.php
│   │   ├── DossierMixteType.php
│   │   └── DossierDonnantAvisType.php
│   ├── Entity/
│   │   ├── Dossier.php                    # Entité principale
│   │   ├── DossierDocumentManquant.php    # Collection documents
│   │   ├── DossierPrescription.php        # Collection prescriptions
│   │   ├── DossierMixte.php               # Collection mixte
│   │   └── DossierDonnantAvis.php         # Collection avis donnant
│   ├── Service/
│   │   ├── Dossier/
│   │   │   ├── DossierService.php         # Service principal sauvegarde
│   │   │   ├── AffectationService.php     # Gestion affectations préventionnistes
│   │   │   ├── Informations.php           # Récupération infos commission/calendrier
│   │   │   └── Prescriptions.php          # Gestion prescriptions
│   │   └── Mail/
│   │       ├── AlerteService.php          # Service alertes email
│   │       ├── AvisService.php            # Récupération texte avis
│   │       ├── Message.php                # Construction messages email
│   │       └── MailerConfiguration.php    # Configuration SMTP/Sendmail
│   ├── Constant/
│   │   ├── DossierNature.php              # Constantes natures dossier
│   │   ├── GenreEtablissement.php         # Constantes genres
│   │   └── DossierStatut.php              # Constantes statuts
│   ├── Validator/
│   │   ├── UniqueUtilisateur.php          # Contrainte validation
│   │   └── UniqueUtilisateurValidator.php # Validateur custom
│   └── DependencyInjection/
│       └── PrevariscMailerDsnProcessor.php # EnvVarProcessor pour Mailer
├── templates/
│   ├── dossier/
│   │   ├── edit.html.twig                 # Template principal (600+ lignes)
│   │   ├── _document_manquant_item.html.twig
│   │   ├── _prescription_item.html.twig
│   │   └── _avis_donnant_item.html.twig
│   └── alerte/
│       └── formulaire.html.twig           # Modale alertes email
├── public/js/dossier/
│   ├── commission.js                      # Gestion commission/calendrier
│   ├── calendar-init.js                   # Initialisation calendriers
│   ├── gestion-incomplet.js               # Workflow documents manquants
│   ├── gestion-avis.js                    # Affichage conditionnel avis
│   ├── gestion-affectations.js            # Affectations préventionnistes
│   ├── gestion-prescriptions.js           # Prescriptions réglementaires
│   ├── gestion-avis-donnant.js            # Dossiers donnant avis
│   └── mixte-handler.js                   # Dossiers mixtes
└── config/
    ├── prevarisc.php                      # Paramètres PREVARISC_MAIL_*
    └── packages/mailer.php                # Configuration Mailer dynamique
```

---

## Blocs fonctionnels implémentés

### BLOC 3 : Commission et dates

**Fonctionnalité :** Sélection commission + dates réunion/visite/réponse avec calendriers

**JavaScript :** `commission.js`, `calendar-init.js`

**Règles métier :**
- Commission obligatoire pour certaines natures (ERP, IOP, etc.)
- Calendriers Flatpickr pour sélection dates
- Vérification cohérence dates (réunion ≤ réponse)
- Affichage des informations de commission (président, secrétaire)

**Services :**
- `Informations::getCommissionInfos()` - Récupère infos commission
- `Informations::getDateProchaineDansCalendrier()` - Calcule prochaine date disponible

**Événements custom :**
- `commission:changed` - Déclenché au changement de commission
- `commission:cleared` - Déclenché au vidage de commission

---

### BLOC 4 : Avis de commission

**Fonctionnalité :** Affichage conditionnel de l'avis de commission

**JavaScript :** `gestion-avis.js`

**Règles métier :**
- Avis affiché uniquement si commission ET nature donnant avis (via `DossierNature::NATURES_DONNANT_AVIS`)
- Masqué si dossier incomplet (workflow documents manquants)
- Masqué si nature ne donne pas d'avis (étude, AT, etc.)

**Constantes :**
- `DossierNature::NATURES_DONNANT_AVIS` - Natures 1-6, 12-13, 17-21, 26, 28-29, 46-48

**Événements custom :**
- `dossier:updateAvisVisibility` - Recalcule visibilité avis

---

### BLOC 6 : Calendrier de la commission

**Fonctionnalité :** Affichage/masquage du calendrier selon la commission

**JavaScript :** `commission.js`

**Règles métier :**
- Calendrier affiché uniquement si commission sélectionnée
- Bouton "Prochaine date disponible" calcule date via service `Informations`
- Bouton "Masquer calendrier" ferme le widget

**Services :**
- `Informations::getDateProchaineDansCalendrier()` - Calcul date disponible

---

### BLOC 7 : Dates des pièces

**Fonctionnalité :** Dates dépôt, mise à l'ordre du jour, réception service consulté

**JavaScript :** `commission.js` (gestion `dateDepot`)

**Règles métier :**
- `dateDepot` obligatoire pour créer un dossier
- `dateInsert` (mise à l'ordre du jour) dépend de la commission
- `dateReception` pour service consulté (workflow spécifique)

---

### BLOC 8 : Informations Plat'AU

**Fonctionnalité :** Envoi vers Plat'AU si commission CCDSA

**JavaScript :** `commission.js`

**Règles métier :**
- Champ `preventionniste` affiché uniquement si commission = CCDSA (ID=3)
- Requis pour envoi Plat'AU
- Masqué pour autres commissions

**API :**
- `prevarisc-passerelle-platau` - API de synchronisation

---

### BLOC 12 : Documents consultables (Mixte)

**Fonctionnalité :** Liaison avec dossiers liés (documents consultables)

**JavaScript :** `mixte-handler.js`

**Entité :** `DossierMixte` (table `dossiermixte`)

**Règles métier :**
- Collection de dossiers liés
- Recherche autocomplete (TomSelect)
- Suppression sans cascade (conservation historique)

**FormType :** `DossierMixteType` (champ `dossierLie`)

---

### BLOC 13 : Documents manquants

**Fonctionnalité :** Workflow documents manquants avec validation réception

**JavaScript :** `gestion-incomplet.js` (682 lignes)

**Entités :** 
- `Dossier::$incomplet` (boolean)
- `DossierDocumentManquant` (collection)

**Workflow :**
1. Dossier incomplet → Affichage zone documents manquants
2. Ajout documents (libellé, date, delai)
3. Validation réception → Radios "Complet/Incomplet" masqués + champ readonly
4. Tous validés → Retour automatique "Complet"
5. "Modifier" → Retour état incomplet

**Règles métier :**
- Radios "Complet/Incomplet" désactivés (`disabled=true`) quand masqués
- Option "Vide" masquée via thème Twig (`_dossier_incomplet_widget`)
- Documents validés = readonly (sauf date réception)
- Historique conservé après passage à "Complet"
- Boutons "Aujourd'hui" pour dates incomplet/réception

**Thème Twig :** Masquage option vide radios via bloc `_dossier_incomplet_widget`

**Services :**
- `DossierService::saveDossier()` - Persiste collection documents manquants

---

### BLOC 14 : Observations

**Fonctionnalité :** Champ texte libre pour observations

**Champ :** `Dossier::$objet` (textarea)

---

### BLOC 15 : Descriptifs

**Fonctionnalité :** Descriptif établissement, activité, moyens de secours

**Champs :**
- `descEtablissement` - Descriptif général établissement
- `descActivite` - Descriptif activité
- `descMoyens` - Descriptif moyens de secours

---

### BLOC 16 : Affectations préventionnistes

**Fonctionnalité :** Affectation préventionnistes au dossier

**JavaScript :** `gestion-affectations.js`

**Service :** `AffectationService`

**Règles métier :**
- Recherche préventionnistes avec autocomplete (TomSelect)
- Affichage nom + fonction
- Suppression avec confirmation
- Envoi email optionnel à l'ajout (`AlerteService`)

**Base de données :**
- Table `dossieraffectation` (liaison many-to-many)
- Champs : `ID_DOSSIER`, `ID_UTILISATEUR`

**Alerte email :**
- Envoi automatique au préventionniste affecté
- Message personnalisé avec lien vers dossier
- Configuration SMTP/Sendmail dynamique

---

### BLOC 17 : Dossiers donnant avis (Parties 1, 2, 3)

**Fonctionnalité :** Gestion avis rendus pour autres dossiers

**JavaScript :** `gestion-avis-donnant.js`

**Service :** `AvisService`, `Message`, `AlerteService`

**Entité :** `DossierDonnantAvis` (table `dossierdonnantavis`)

#### Partie 1 : Affectation dossier

**Workflow :**
1. Recherche dossier lié (autocomplete)
2. Ajout à la collection `dossiersLiesAvis`
3. Affichage carte avec infos dossier
4. Bouton "Désaffecter" retire de la collection

**Règles métier :**
- Dossiers liés conservés en historique (pas de suppression cascade)
- Cache préventif avec `findDossiersAvecInfos()` pour éviter N+1

#### Partie 2 : Désaffectation

**Workflow :**
- Bouton "Désaffecter" retire le dossier lié
- Confirmation visuelle (carte supprimée)
- Pas de cascade DELETE (conservation historique)

#### Partie 3 : Alertes email

**Sous-partie 3A : Détection dossier prioritaire**

**Règles métier :**
- Dossier prioritaire = statut "En cours" (ID=2) + présence dossiers liés avis non traités
- Flash info orange affiché après sauvegarde
- Message : "Ce dossier est prioritaire (avis d'autres dossiers en attente)"

**Sous-partie 3B.1 : Alerte destinataires existants**

**Modale formulaire :**
- Champ objet (pré-rempli)
- Champ message (pré-rempli via service `Message`)
- Destinataires auto-détectés (créateur + affectés dossier lié)

**Services :**
- `AlerteService::envoyerAlerte()` - Envoi email
- `Message::genererObjet()` - Construction objet email
- `Message::genererCorps()` - Construction corps email
- `AvisService::getTexteFromEtablissement()` - Récupération texte avis

**Règles métier objet :**
- Format : `Avis [favorable/défavorable] - [Commune] - [Type] [Catégorie] - [Nom établissement]`
- Exemple : `Avis favorable - TOURS - ERP 1ère catégorie - Centre commercial Auchan`

**Règles métier corps :**
- Partie 1 : Type dossier (ERP/IGH/IOP)
- Partie 2 : Visite/réunion avec date
- Partie 3 : Avis de commission avec texte
- Balises HTML personnalisées : `[GRAS]`, `[FIN-GRAS]`, `[SOUSLIGNE]`, `[FIN-SOUSLIGNE]`

**Sous-partie 3B.2 : Alerte destinataires personnalisés**

**Modale formulaire :**
- Recherche destinataires (TomSelect sur utilisateurs actifs)
- Champ objet modifiable
- Champ message modifiable
- Toast notifications pour succès/erreur

**Validation :**
- Objet et message requis (strict, pas `empty()` mais `=== ''`)
- Destinataires requis (strict, pas `empty()` mais `=== []`)
- Erreurs 400 avec JSON `{success: false, error: "message"}`

---

### BLOC 18 : Prescriptions réglementaires

**Fonctionnalité :** Gestion prescriptions applicables au dossier

**JavaScript :** `gestion-prescriptions.js`

**Entité :** `DossierPrescription` (table `dossierprescription`)

**Service :** `Prescriptions`

**Règles métier :**
- Collection de prescriptions
- Champs : type, texte, catégorie, article
- Recherche/ajout/suppression dynamique
- Historique conservé

**FormType :** `DossierPrescriptionType`

---

### BLOC 19 : Visite Avant Ouverture (VAO)

**Fonctionnalité :** Alerte changement statut établissement

**Règles métier :**
- Nature 47 ou 48 (Visite Avant Ouverture)
- Avis favorable (ID=1)
- Établissement statut "Projet" (ID=1)
- → Flash warning suggère passage à "Ouvert"

**Constantes :**
- `DossierNature::VISITE_AVANT_OUVERTURE = [47, 48]`

**Code :**
```php
// DossierController.php lignes 228-257
if (in_array($dossier->getNature()?->getId(), DossierNature::VISITE_AVANT_OUVERTURE)
    && $dossier->getAvisCommission()?->getId() === 1
    && $dossier->getEtablissement()?->getStatut()?->getId() === 1
) {
    $this->addFlash('warning', sprintf(
        'Ce dossier concerne une visite avant ouverture avec un avis favorable. 
        Pensez à modifier le statut de l\'établissement en "Ouvert" si nécessaire. 
        <a href="%s">Modifier l\'établissement</a>',
        $this->generateUrl('etablissement_edit', ['id' => $dossier->getEtablissement()->getId()])
    ));
}
```

---

## Services métier

### DossierService

**Fichier :** `src/Service/Dossier/DossierService.php`

**Responsabilité :** Sauvegarde complète du dossier avec flush Doctrine

**Méthodes :**
- `saveDossier(Dossier $dossier): void` - Persiste le dossier et ses collections

**Utilisation :**
```php
$dossierService->saveDossier($dossier);
$this->addFlash('success', 'Dossier modifié avec succès');
```

---

### AffectationService

**Fichier :** `src/Service/Dossier/AffectationService.php`

**Responsabilité :** Gestion des affectations préventionnistes

**Méthodes :**
- `findDossiersAffectes(int $userId): array` - Liste dossiers affectés à un utilisateur
- `ajouterAffectation(Dossier $dossier, Utilisateur $utilisateur): void`
- `retirerAffectation(Dossier $dossier, Utilisateur $utilisateur): void`

---

### Informations

**Fichier :** `src/Service/Dossier/Informations.php`

**Responsabilité :** Récupération informations commission et calendrier

**Méthodes :**
- `getCommissionInfos(int $commissionId): array` - Infos commission (président, secrétaire)
- `getDateProchaineDansCalendrier(int $calendrierId): ?\DateTimeInterface` - Prochaine date dispo

---

### Prescriptions

**Fichier :** `src/Service/Dossier/Prescriptions.php`

**Responsabilité :** Gestion prescriptions réglementaires

**Méthodes :**
- `findPrescriptionsApplicables(string $type): array` - Liste prescriptions par type
- `validerPrescription(string $texte, string $type): bool` - Validation prescription

---

### AlerteService

**Fichier :** `src/Service/Mail/AlerteService.php`

**Responsabilité :** Envoi alertes email (affectations, avis donnant)

**Dépendances :**
- `MailerInterface` - Envoi email Symfony Mailer
- `Message` - Construction messages
- `AvisService` - Récupération texte avis
- `MailerConfiguration` - Configuration SMTP

**Méthodes :**
- `envoyerAlerte(string $objet, string $corps, array $destinataires): bool`
- `envoyerAlerteAffectation(Dossier $dossier, Utilisateur $preventionniste): bool`
- `envoyerAlerteAvisDonnant(Dossier $dossier, array $destinataires): bool`

**Utilisation :**
```php
$alerteService->envoyerAlerte(
    "Affectation dossier #123",
    "Vous avez été affecté au dossier...",
    ['jean.dupont@example.com']
);
```

---

### Message

**Fichier :** `src/Service/Mail/Message.php`

**Responsabilité :** Construction messages email personnalisés

**Dépendances :**
- `AvisService` - Récupération avis
- `Changement` (Service legacy) - Gestion balises HTML

**Méthodes :**
- `genererObjet(Dossier $dossier): string` - Génère objet email
- `genererCorps(Dossier $dossier): string` - Génère corps email avec balises HTML

**Balises HTML personnalisées :**
```php
public const BALISES_HTML = [
    '[GRAS]' => '<strong>',
    '[FIN-GRAS]' => '</strong>',
    '[SOUSLIGNE]' => '<u>',
    '[FIN-SOUSLIGNE]' => '</u>',
];
```

**Format objet :**
```
Avis [favorable/défavorable] - [Commune] - [Type] [Catégorie] - [Nom établissement]
```

**Format corps :**
```
[Type dossier]

Visite/Réunion le [date]

Avis de commission : [texte avis]
```

---

### AvisService

**Fichier :** `src/Service/Mail/AvisService.php`

**Responsabilité :** Récupération texte avis établissement

**Méthodes :**
- `getTexteFromEtablissement(Etablissement $etablissement): string`

**Règles métier :**
- Cellule (genre 3) → Recherche établissement père (ERP ou habitation)
- Établissement normal → Recherche avis directement

**Code :**
```php
public function getTexteFromEtablissement(Etablissement $etablissement): string
{
    // Si cellule → récupérer avis établissement père
    if ($etablissement->getGenre()?->getId() === GenreEtablissement::CELLULE) {
        $etablissementPere = $etablissement->getEtablissementPere();
        // ... recherche avis sur père
    }
    
    // Sinon avis direct
    return $this->trouverAvisEtablissement($etablissement);
}
```

---

### MailerConfiguration

**Fichier :** `src/Service/Mail/MailerConfiguration.php`

**Responsabilité :** Construction DSN Mailer dynamique depuis paramètres PREVARISC

**Paramètres :**
- `PREVARISC_MAIL_ENABLED` - Activation email (0/1)
- `PREVARISC_MAIL_TRANSPORT` - smtp/sendmail
- `PREVARISC_MAIL_HOST` - Serveur SMTP
- `PREVARISC_MAIL_PORT` - Port SMTP (défaut 25)
- `PREVARISC_MAIL_USERNAME` - Login SMTP
- `PREVARISC_MAIL_PASSWORD` - Mot de passe SMTP
- `PREVARISC_MAIL_SENDER` - Email expéditeur
- `PREVARISC_MAIL_SENDER_NAME` - Nom expéditeur

**Méthodes :**
- `getDsn(): string` - Construit DSN (smtp://user:pass@host:port ou sendmail://default)
- `getSender(): string` - Email expéditeur
- `getSenderName(): string` - Nom expéditeur
- `isEnabled(): bool` - Email activé ?

**DSN générées :**
```php
// SMTP avec auth
smtp://user:pass@smtp.example.com:587

// SMTP sans auth
smtp://smtp.example.com:25

// Sendmail
sendmail://default

// Désactivé
null://null
```

**EnvVarProcessor :**
- `PrevariscMailerDsnProcessor` - Processeur Symfony pour `%env(prevarisc_mailer_dsn:MAILER_DSN)%`
- Délègue à `MailerConfiguration::getDsn()` (DRY)

---

## Architecture JavaScript

### Organisation modulaire

**Principe :** Chaque fonctionnalité = 1 module JavaScript isolé

**Avantages :**
- ✅ Séparation des responsabilités
- ✅ Maintenance facilitée
- ✅ Réutilisabilité
- ✅ Tests unitaires possibles
- ✅ Pas de conflits de noms

### Modules JavaScript

| Module | Lignes | Responsabilité |
|--------|--------|----------------|
| `commission.js` | ~250 | Gestion commission, calendrier, dates |
| `calendar-init.js` | ~150 | Initialisation Flatpickr, boutons calendrier |
| `gestion-incomplet.js` | 682 | Workflow documents manquants complet |
| `gestion-avis.js` | ~100 | Affichage conditionnel avis commission |
| `gestion-affectations.js` | ~200 | Affectations préventionnistes, TomSelect |
| `gestion-prescriptions.js` | ~180 | Prescriptions réglementaires, TomSelect |
| `gestion-avis-donnant.js` | ~220 | Dossiers donnant avis, autocomplete |
| `mixte-handler.js` | ~120 | Dossiers mixtes, TomSelect |

### Événements custom

**Pattern :** Communication inter-modules via événements custom DOM

**Avantages :**
- ✅ Découplage complet entre modules
- ✅ Pas de dépendances directes
- ✅ Évolutivité (ajout listeners sans modifier émetteur)

**Événements disponibles :**

```javascript
// Commission
document.dispatchEvent(new CustomEvent('commission:changed', { 
    detail: { commissionId: 3 } 
}));
document.dispatchEvent(new CustomEvent('commission:cleared'));

// Calendrier
document.dispatchEvent(new CustomEvent('calendar:dateSelected', { 
    detail: { date: '2025-02-10' } 
}));
document.dispatchEvent(new CustomEvent('calendar:hidden'));
document.dispatchEvent(new CustomEvent('calendar:shown'));

// Avis
document.dispatchEvent(new CustomEvent('dossier:updateAvisVisibility'));

// Documents manquants
document.dispatchEvent(new CustomEvent('incomplet:changed', { 
    detail: { incomplet: true } 
}));
```

**Exemple d'utilisation :**

```javascript
// Émetteur (commission.js)
commissionSelect.addEventListener('change', (e) => {
    document.dispatchEvent(new CustomEvent('commission:changed', {
        detail: { commissionId: e.target.value }
    }));
});

// Récepteur (gestion-avis.js)
document.addEventListener('commission:changed', (e) => {
    updateAvisVisibility();
});
```

### Initialisation

**Pattern IIFE (Immediately Invoked Function Expression) :**

```javascript
(function() {
    'use strict';
    
    // Code isolé dans closure
    
    // Initialisation au DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        // Setup listeners, etc.
    }
})();
```

---

## Validations et règles métier

### Validations Symfony

**Entité Dossier :**
- `objet` - NotBlank si dossier nature études (5, 7, 16, etc.)
- `dateDepot` - NotBlank, Type date
- `incomplet` - Boolean nullable
- `avisCommission` - Requis si nature donnant avis

**Entité DossierDocumentManquant :**
- `libelle` - NotBlank, Length max 255
- `date` - NotBlank, Type date
- `delai` - GreaterThan 0

**Entité DossierPrescription :**
- `type` - NotBlank
- `texte` - NotBlank, Length max 1000

### Validations custom

#### UniqueUtilisateur

**Fichier :** `src/Validator/UniqueUtilisateurValidator.php`

**Règle métier :**
- Si `login` renseigné → Vérifier unicité login
- Si `login` vide → Vérifier unicité email (via JOIN utilisateurinformations)

**Code :**
```php
public function validate($value, Constraint $constraint): void
{
    if ($utilisateur->getUsername() !== null && $utilisateur->getUsername() !== '') {
        // Cas 1 : Valider unicité login
        $existing = $this->repository->findOneBy(['username' => $username]);
        if ($existing && $existing->getId() !== $utilisateur->getId()) {
            $this->context->buildViolation($constraint->messageLogin)->addViolation();
        }
    } else {
        // Cas 2 : Valider unicité email
        $qb = $this->repository->createQueryBuilder('u')
            ->innerJoin('u.informations', 'i')
            ->where('i.mail = :mail')
            ->andWhere('u.id != :userId')
            ->setParameter('mail', $mail)
            ->setParameter('userId', $utilisateur->getId());
        
        if ($qb->getQuery()->getOneOrNullResult()) {
            $this->context->buildViolation($constraint->messageMail)->addViolation();
        }
    }
}
```

### Validations JavaScript

**Documents manquants :**
- Libellé requis (alert si vide)
- Date incomplet requise (alert si vide)
- Délai requis (alert si vide)

**Affectations :**
- Préventionniste requis (TomSelect validation)
- Pas de doublons (détection côté serveur)

**Prescriptions :**
- Type requis
- Texte requis

---

## Système d'alertes email

### Architecture

```
Controller (AlerteController)
    ↓
AlerteService (orchestration)
    ↓
├─→ Message (construction objet/corps)
│       ↓
│   AvisService (récupération avis)
│       ↓
│   Changement (balises HTML)
│
├─→ MailerConfiguration (DSN + sender)
│
└─→ MailerInterface (Symfony Mailer)
```

### Configuration

**Fichier :** `config/prevarisc.php`

```php
'PREVARISC_MAIL_ENABLED' => '%env(int:PREVARISC_MAIL_ENABLED)%',
'PREVARISC_MAIL_TRANSPORT' => '%env(string:PREVARISC_MAIL_TRANSPORT)%',
'PREVARISC_MAIL_HOST' => '%env(string:PREVARISC_MAIL_HOST)%',
'PREVARISC_MAIL_PORT' => '%env(string:PREVARISC_MAIL_PORT)%',
'PREVARISC_MAIL_USERNAME' => '%env(string:PREVARISC_MAIL_USERNAME)%',
'PREVARISC_MAIL_PASSWORD' => '%env(string:PREVARISC_MAIL_PASSWORD)%',
'PREVARISC_MAIL_SENDER' => '%env(string:PREVARISC_MAIL_SENDER)%',
'PREVARISC_MAIL_SENDER_NAME' => '%env(string:PREVARISC_MAIL_SENDER_NAME)%',
```

**Fichier :** `.env`

```bash
# Configuration par défaut (peut être surchargée par Apache SetEnv)
PREVARISC_MAIL_ENABLED=0
PREVARISC_MAIL_TRANSPORT=smtp
PREVARISC_MAIL_HOST=localhost
PREVARISC_MAIL_PORT=25
PREVARISC_MAIL_USERNAME=
PREVARISC_MAIL_PASSWORD=
PREVARISC_MAIL_SENDER=prevarisc@example.com
PREVARISC_MAIL_SENDER_NAME="Prevarisc"
```

**Configuration Apache :**

```apache
<VirtualHost *:80>
    SetEnv PREVARISC_MAIL_ENABLED "1"
    SetEnv PREVARISC_MAIL_TRANSPORT "smtp"
    SetEnv PREVARISC_MAIL_HOST "smtp.example.com"
    SetEnv PREVARISC_MAIL_PORT "587"
    SetEnv PREVARISC_MAIL_USERNAME "prevarisc@example.com"
    SetEnv PREVARISC_MAIL_PASSWORD "secret"
    SetEnv PREVARISC_MAIL_SENDER "prevarisc@example.com"
    SetEnv PREVARISC_MAIL_SENDER_NAME "Prevarisc Prod"
</VirtualHost>
```

### Workflow envoi email

**1. Affichage modale :**
```javascript
// Template alerte/formulaire.html.twig
$('#modalAlerte').modal('show');
```

**2. Soumission formulaire :**
```javascript
fetch('/alerte/envoyer', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        $.toast.success('Email envoyé avec succès');
    }
});
```

**3. Controller validation :**
```php
// AlerteController::envoyerAction()
if (!is_string($objet) || $objet === '') {
    throw new InvalidArgumentException('L\'objet est requis');
}
if ($destinataires === []) {
    return new JsonResponse(['success' => false, 'error' => 'Destinataires requis'], 400);
}
```

**4. Service envoi :**
```php
// AlerteService::envoyerAlerte()
$email = (new Email())
    ->from(new Address($sender, $senderName))
    ->subject($objet)
    ->html($corps);

foreach ($destinataires as $dest) {
    $email->addBcc($dest); // BCC pour confidentialité
}

$this->mailer->send($email);
```

---

## Constantes métier

### DossierNature

**Fichier :** `src/Constant/DossierNature.php`

```php
class DossierNature
{
    // Natures donnant avis de commission
    public const NATURES_DONNANT_AVIS = [
        1, 2, 3, 4, 5, 6,      // Visites périodiques, travaux, etc.
        12, 13,                 // Visites périodiques IGH/IOP
        17, 18, 19, 20, 21,    // Autorisation travaux, groupes, réception
        26,                     // Visite de contrôle
        28, 29,                 // Réunion, visite
        46, 47, 48,            // Ouverture, VAO
    ];
    
    // Visite Avant Ouverture
    public const VISITE_AVANT_OUVERTURE = [47, 48];
}
```

### GenreEtablissement

**Fichier :** `src/Constant/GenreEtablissement.php`

```php
class GenreEtablissement
{
    public const CELLULE = 3; // Cellule commerciale
}
```

### DossierStatut

**Fichier :** `src/Constant/DossierStatut.php`

```php
class DossierStatut
{
    public const PROJET = 1;
    public const EN_COURS = 2;
    public const TRAITE = 3;
}
```

---

## Choix techniques

### Pourquoi Symfony Form au lieu de HTML pur ?

**Avantages :**
- ✅ Validation automatique côté serveur
- ✅ CSRF protection intégré
- ✅ Binding automatique entité ↔ formulaire
- ✅ Gestion collections (documents, prescriptions, etc.)
- ✅ Réutilisabilité des FormTypes
- ✅ Thèmes Twig personnalisables

**Exemple collection :**
```php
->add('documentsManquants', CollectionType::class, [
    'entry_type' => DossierDocumentManquantType::class,
    'allow_add' => true,
    'allow_delete' => true,
    'by_reference' => false, // Important pour trigger addDocumentManquant()
])
```

### Pourquoi des services séparés au lieu d'un seul gros service ?

**Principe :** Single Responsibility Principle (SRP)

**Services créés :**
- `DossierService` - Sauvegarde uniquement
- `AffectationService` - Affectations uniquement
- `AlerteService` - Emails uniquement
- `Informations` - Récupération données référentielles
- `Prescriptions` - Gestion prescriptions
- `Message` - Construction messages
- `AvisService` - Récupération avis
- `MailerConfiguration` - Configuration SMTP

**Avantages :**
- ✅ Testabilité (mock facile)
- ✅ Réutilisabilité (injection ciblée)
- ✅ Maintenance (modification isolée)
- ✅ Lisibilité (responsabilité claire)

### Pourquoi événements custom JavaScript au lieu de fonctions globales ?

**Problème des fonctions globales :**
```javascript
// ❌ Mauvais : couplage fort
function updateAvisVisibility() { ... }

// commission.js appelle directement
updateAvisVisibility(); // Dépendance directe !
```

**Solution avec événements :**
```javascript
// ✅ Bon : découplage
document.dispatchEvent(new CustomEvent('dossier:updateAvisVisibility'));

// gestion-avis.js écoute l'événement
document.addEventListener('dossier:updateAvisVisibility', () => {
    // Réagit à l'événement
});
```

**Avantages :**
- ✅ Pas de couplage entre modules
- ✅ Ajout listeners sans modifier émetteur
- ✅ Pattern pub/sub natif
- ✅ Testabilité (spy sur événements)

### Pourquoi désactiver les radios incomplet au lieu de les masquer ?

**Problème HTML5 :**
```html
<!-- ❌ Champ masqué mais required=true -->
<div style="display:none">
    <input type="radio" name="incomplet" value="1" required>
</div>
<!-- Erreur : "Le contrôle ne peut recevoir le focus" -->
```

**Solution :**
```javascript
// ✅ Désactiver les radios
radiosIncomplet.forEach(radio => radio.disabled = true);
// Champs disabled ne sont pas soumis → empty_data appliqué
```

**Avantages :**
- ✅ Pas d'erreur HTML5 validation
- ✅ Formulaire soumissible
- ✅ Symfony applique `empty_data`
- ✅ Valeur par défaut gérée côté serveur

### Pourquoi masquer l'option vide des radios via thème Twig ?

**Contexte :**
- `required => false` génère option "Vide" (valeur `''`)
- Permet distinction `null` vs `false` vs `true`
- Mais UX : pas d'option vide visible

**Solution thème inline :**
```twig
{% block _dossier_incomplet_widget %}
    {% for child in form %}
        {% if child.vars.value != '' %}
            {{ form_widget(child) }}
            {{ form_label(child) }}
        {% endif %}
    {% endfor %}
{% endblock %}
```

**Avantages :**
- ✅ Option vide existe côté serveur (gestion `null`)
- ✅ Masquée visuellement uniquement
- ✅ Appliqué uniquement dans `edit.html.twig`
- ✅ Pas de modification globale thèmes Symfony

### Pourquoi EnvVarProcessor pour MAILER_DSN ?

**Problème :**
```yaml
# ❌ DSN statique dans .env
MAILER_DSN=smtp://localhost
```

**Solution :**
```php
// PrevariscMailerDsnProcessor.php
public function getEnv($prefix, $name, \Closure $getEnv)
{
    return $this->mailerConfiguration->getDsn(); // Dynamique !
}
```

```yaml
# config/packages/mailer.php
'dsn' => '%env(prevarisc_mailer_dsn:MAILER_DSN)%',
```

**Avantages :**
- ✅ DSN construit dynamiquement depuis PREVARISC_MAIL_*
- ✅ Compatible Apache SetEnv
- ✅ Pas de modification `.env` pour clients
- ✅ Centralisé dans `MailerConfiguration`

---

## Tests

### Tests manuels réalisés

**Workflow complet :**
- ✅ Édition dossier avec tous les champs
- ✅ Sélection commission → Affichage calendrier
- ✅ Changement nature → Affichage/masquage avis
- ✅ Workflow documents manquants complet
- ✅ Affectations préventionnistes avec email
- ✅ Dossiers donnant avis avec alerte email
- ✅ Prescriptions réglementaires
- ✅ Dossiers mixtes
- ✅ Validation VAO avec flash warning

**Navigateurs testés :**
- ✅ Chrome 120+
- ✅ Firefox 120+
- ✅ Edge 120+

### Tests unitaires à créer

**Services prioritaires :**
- `AlerteService::envoyerAlerte()` - Mock MailerInterface
- `Message::genererObjet()` - Vérifier format
- `Message::genererCorps()` - Vérifier balises HTML
- `AvisService::getTexteFromEtablissement()` - Cas cellule
- `MailerConfiguration::getDsn()` - Formats SMTP/Sendmail
- `UniqueUtilisateurValidator::validate()` - Cas login/email

**Tests d'intégration :**
- `DossierController::editAction()` - Soumission formulaire
- `AlerteController::envoyerAction()` - Envoi email complet

**Tests fonctionnels :**
- Workflow documents manquants complet (Selenium)
- Affectation préventionniste avec email
- Dossier donnant avis avec alerte

---

## Bonnes pratiques appliquées

### Code

- ✅ **Services métier** : Logique métier hors controllers
- ✅ **Single Responsibility** : 1 service = 1 responsabilité
- ✅ **DRY** : Pas de duplication (MailerConfiguration réutilisé)
- ✅ **Type hints** : Tous paramètres et retours typés (PHP 7.1)
- ✅ **DocBlocks** : Documentation méthodes complexes
- ✅ **Constantes** : Valeurs magiques externalisées
- ✅ **Validation** : Strict (`===` au lieu de `empty()`)

### JavaScript

- ✅ **Modules** : 1 fichier = 1 responsabilité
- ✅ **IIFE** : Isolation scope (pas de pollution globale)
- ✅ **Événements custom** : Découplage inter-modules
- ✅ **'use strict'** : Mode strict activé
- ✅ **Nommage** : Fonctions explicites (`handleIncompletChangeModerne`)
- ✅ **Commentaires** : Sections et règles métier documentées

### Architecture

- ✅ **Symfony 4.4 LTS** : Framework stable
- ✅ **Doctrine 2.14** : ORM avec relations gérées
- ✅ **Symfony Form** : Validation automatique
- ✅ **Twig** : Templates sécurisés (auto-escape)
- ✅ **Symfony Mailer** : Email moderne (pas SwiftMailer)
- ✅ **EnvVarProcessor** : Configuration dynamique

---

## Prochaines étapes

### Tests automatisés

- [ ] Tests unitaires services (PHPUnit)
- [ ] Tests d'intégration controllers
- [ ] Tests fonctionnels JavaScript (Selenium)
- [ ] Tests email (MailPit assertions)

### Documentation utilisateur

- [ ] Guide utilisateur édition dossiers
- [ ] Guide configuration email SMTP
- [ ] Troubleshooting erreurs courantes

### Optimisations

- [ ] Cache requêtes Doctrine (Redis/APCu)
- [ ] Lazy loading collections
- [ ] Pagination affectations/prescriptions
- [ ] Compression assets JavaScript

### Refactoring

- [ ] Convertir formulaire alerte en FormType Symfony
- [ ] Ajouter validation client-side (Parsley.js)
- [ ] Améliorer accessibilité (ARIA labels)
- [ ] Responsive design modales

---

## Références

- [ARCHITECTURE_JS_MODULAIRE.md](ARCHITECTURE_JS_MODULAIRE.md) - Architecture JavaScript détaillée
- [CONSTANTES_METIER.md](../CONSTANTES_METIER.md) - Liste complète constantes
- [TOAST_NOTIFICATIONS.md](../TOAST_NOTIFICATIONS.md) - Système notifications
- [MAPPING_LEGACY_REFERENCE.md](../migration/MAPPING_LEGACY_REFERENCE.md) - Mapping Zend → Symfony

---

**Auteurs :** Équipe migration Prevarisc  
**Dernière mise à jour :** 10 février 2026  
**Version :** 1.0.0  
**Statut :** ✅ Production ready
