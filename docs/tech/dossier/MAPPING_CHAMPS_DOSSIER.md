# Mapping des champs Dossier - Legacy → Symfony

**Date :** 10 décembre 2025  
**Décision :** Standardisation sur les noms Symfony (camelCase) partout

---

## Principe

**Avant :** Noms legacy en MAJUSCULES dans `DossierFields` → conversion JavaScript → noms Symfony  
**Maintenant :** Noms Symfony (camelCase) partout → pas de conversion nécessaire

**Avantages :**
- ✅ Cohérence entre consultation et édition
- ✅ Pas de mapping bidirectionnel à maintenir
- ✅ Code JavaScript plus simple
- ✅ Moins d'erreurs potentielles

---

## Mapping Legacy → Symfony

### Champs généraux

| Legacy (UPPERCASE) | Symfony (camelCase)  | Propriété Entité      | Description          |
|--------------------|----------------------|-----------------------|----------------------|
| `DATEINSERT`       | `dateInsert`         | `$dateInsert`         | Date de création     |
| `OBJET`            | `objet`              | `$objet`              | Objet du dossier     |
| `OBSERVATION`      | `observationDossier` | `$observationDossier` | Observations         |
| `HORSDELAI`        | `horsDelai`          | `$horsDelai`          | Hors délai (boolean) |
| `DATESIGNATURE`    | `dateSignature`      | `$dateSignature`      | Date de signature    |

### Champs réunions (Phase 1)

| Legacy            | Symfony            | Propriété           | Description                           |
|-------------------|--------------------|---------------------|---------------------------------------|
| `DATEREUN`        | `dateReunion`      | `$dateReunion`      | Date de réunion (réunions uniquement) |
| `DATEVISITE`      | `dateVisite`       | `$dateVisite`       | Date de visite (visites uniquement)   |
| `LIEUREUNION`     | `lieuReunion`      | `$lieuReunion`      | Lieu de réunion                       |
| `DEMANDEUR`       | `demandeur`        | `$demandeur`        | Demandeur                             |
| `DATEREP`         | `dateReponse`      | `$dateReponse`      | Date réponse SDIS                     |
| `PREVENTIONNISTE` | `preventionnistes` | `$preventionnistes` | Collection préventionnistes           |

### Champs études (Phase future)

| Legacy            | Symfony              | Propriété             | Description                     |
|-------------------|----------------------|-----------------------|---------------------------------|
| `NUMDOCURBA`      | `documentsUrbanisme` | `$documentsUrbanisme` | N° documents urbanisme          |
| `DATEMAIRIE`      | `dateMairie`         | `$dateMairie`         | Date réception mairie           |
| `DATESECRETARIAT` | `dateSecretariat`    | `$dateSecretariat`    | Date réception secrétariat SDIS |
| `SERVICEINSTRUC`  | `serviceInstructeur` | `$serviceInstructeur` | Service instructeur             |
| `COMMISSION`      | `commission`         | `$commission`         | Commission                      |
| `DATECOMM`        | `dateCommission`     | `$dateCommission`     | Date commission                 |
| `INCOMPLET`       | `incomplet`          | `$incomplet`          | Dossier incomplet (boolean)     |
| `AVIS`            | `avis`               | `$avis`               | Avis du rapporteur              |
| `AVIS_COMMISSION` | `avisCommission`     | `$avisCommission`     | Avis de la commission           |
| `ABSQUORUM`       | `absenceDeQuorum`    | `$absenceDeQuorum`    | Absence de quorum (boolean)     |
| `NPSP`            | `nePeutSePrononcer`  | `$nePeutSePrononcer`  | Ne peut se prononcer (boolean)  |

### Champs dérogations (Phase future)

| Legacy           | Symfony                   | Propriété                  | Description             |
|------------------|---------------------------|----------------------------|-------------------------|
| `REGLEDEROG`     | `reglesDerogation`        | `$reglesDerogation`        | Règles dérogation       |
| `JUSTIFDEROG`    | `justificationDerogation` | `$justificationDerogation` | Justification           |
| `MESURESCOMPENS` | `mesuresCompensatoires`   | `$mesuresCompensatoires`   | Mesures compensatoires  |
| `MESURESCOMPLE`  | `mesuresComplementaires`  | `$mesuresComplementaires`  | Mesures complémentaires |

### Champs visites (Phase future)

| Legacy        | Symfony                  | Propriété                 | Description               |
|---------------|--------------------------|---------------------------|---------------------------|
| `DIFFEREAVIS` | `avisDiffere`            | `$avisDiffere`            | Avis différé (boolean)    |
| `DATERVRAT`   | `dateReceptionRvrat`     | `$dateReceptionRvrat`     | Date réception RVRAT      |
| `DELAIPRESC`  | `dateLimitePrescription` | `$dateLimitePrescription` | Délai levée prescriptions |

### Champs courriers (Phase future)

| Legacy              | Symfony                   | Propriété                  | Description               |
|---------------------|---------------------------|----------------------------|---------------------------|
| `DATEENVTRANSIT`    | `dateEnvoiTransit`        | `$dateEnvoiTransit`        | Date envoi/transit        |
| `DATEPREF`          | `datePrefecture`          | `$datePrefecture`          | Date réception préfecture |
| `DATETRANSFERTCOMM` | `dateTransfertCommission` | `$dateTransfertCommission` | Date transfert commission |
| `DATERECEPTIONCOMM` | `dateReceptionCommission` | `$dateReceptionCommission` | Date réception commission |

### Champs interventions (Phase future)

| Legacy        | Symfony              | Propriété             | Description              |
|---------------|----------------------|-----------------------|--------------------------|
| `OPERSDIS`    | `operationSdis`      | `$operationSdis`      | Opération SDIS (boolean) |
| `RCCI`        | `rcci`               | `$rcci`               | RCCI (boolean)           |
| `REX`         | `rex`                | `$rex`                | REX (boolean)            |
| `NUMINTERV`   | `numeroIntervention` | `$numeroIntervention` | N° intervention          |
| `DATEINTERV`  | `dateIntervention`   | `$dateIntervention`   | Date intervention        |
| `DUREEINTERV` | `dureeIntervention`  | `$dureeIntervention`  | Durée intervention       |

### Champs spécifiques

| Legacy      | Symfony               | Propriété              | Description         |
|-------------|-----------------------|------------------------|---------------------|
| `CHARGESEC` | `chargeSecurite`      | `$chargeSecurite`      | Chargé de sécurité  |
| `DATESDIS`  | `dateReception`       | `$dateReception`       | Date réception SDIS |
| `DATESIGN`  | `dateSignature`       | `$dateSignature`       | Date signature      |
| `NUMCHRONO` | `numeroChronologique` | `$numeroChronologique` | N° chronologique    |

---

## Migration progressive

### Phase 1 : Réunions ✅ FAIT
- Champs : `dateInsert`, `objet`, `dateVisite`, `lieuReunion`, `dateReception`, `demandeur`, `observationDossier`
- Natures : 31, 32, 43
- Services : `DossierFields` mis à jour
- Templates : Consultation ET édition mis à jour

### Phase 2 : Courriers (à venir)
- Ajouter : `dateEnvoiTransit`, `datePrefecture`, `dateTransfertCommission`, `dateReceptionCommission`
- Mettre à jour `DossierFields` pour natures 49-54

### Phases suivantes
- Convertir progressivement tous les champs legacy restants
- Mettre à jour les templates au fur et à mesure
- Supprimer les anciens noms en majuscules

---

## Impact sur le code

### DossierFields.php
```php
// ❌ Avant
'31' => ['DATEINSERT', 'OBJET', 'DATEREUN', 'DATEREP', 'DEMANDEUR', 'OBSERVATION']

// ✅ Maintenant
'31' => ['dateInsert', 'objet', 'dateVisite', 'dateReception', 'demandeur', 'observationDossier']
```

### Templates Twig
```twig
{# ❌ Avant #}
{% if dossier|should_display_field('DATEVISITE') %}

{# ✅ Maintenant #}
{% if dossier|should_display_field('dateVisite') %}
```

### JavaScript
```javascript
// ❌ Avant : Besoin de mapping
const fieldMapping = {
    'DATEVISITE': 'dateVisite',
    'LIEUREUNION': 'lieuReunion'
};

// ✅ Maintenant : Pas de mapping nécessaire
fieldsToShow.forEach(fieldName => {
    showField(fieldName); // Nom déjà correct
});
```

---

## TODO

- [ ] Convertir toutes les natures dans `DossierFields.php` (68 natures)
- [ ] Mettre à jour tous les templates de consultation
- [ ] Supprimer le code de mapping JavaScript devenu inutile
- [ ] Mettre à jour les tests unitaires si nécessaire

---

**Auteur :** Équipe Prevarisc  
**Version :** 1.0  
**Dernière mise à jour :** 10 décembre 2025
