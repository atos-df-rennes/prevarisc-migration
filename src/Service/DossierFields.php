<?php

namespace App\Service;

use App\Entity\Dossier;

/**
 * Service de gestion des champs à afficher selon la nature du dossier.
 */
class DossierFields
{
    private const LISTE_CHAMPS = [
        // ETUDES
        // PC - OK
        '1' => ['type', 'dateInsert', 'objet', 'documentsUrbanisme', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'coordonneesSsi', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // AT - OK
        '2' => ['type', 'dateInsert', 'objet', 'documentsUrbanisme', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'coordonneesSsi', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Dérogation - OK
        '3' => ['type', 'dateInsert', 'objet', 'documentsUrbanisme', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'descriptifGeneral', 'justificationDerogation', 'mesuresCompensatoires', 'mesuresComplementaires', 'descriptifEffectifs', 'dateCommission', 'avis', 'coordonneesSsi', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'reglesDerogation', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Cahier des charges fonctionnel du SSI - OK
        '4' => ['type', 'dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'dateCommission', 'avis', 'coordonneesSsi', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Cahier des charges de type T - OK
        '5' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Salon type T - OK
        '6' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'chargeSecurite', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // RVRMD (diag sécu) => Levée de prescriptions - OK
        '7' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Documents divers - OK
        '8' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'datePrefecture', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Changement de DUS - OK
        '9' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Suivi organisme formation SSIAP - OK
        '10' => ['dateInsert', 'objet', 'numeroChronologique', 'avis', 'dateReponse', 'datePrefecture', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Demande de registre de sécurité CTS - OK
        '11' => ['dateInsert', 'objet', 'numeroChronologique', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'datePrefecture', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Demande d'implantation CTS < 6mois - OK
        '12' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Demande d'implantation CTS > 6mois - OK
        '13' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Permis d'aménager - OK
        '14' => ['dateInsert', 'objet', 'documentsUrbanisme', 'numeroChronologique', 'commission', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Permis de démolir - OK
        '15' => ['dateInsert', 'objet', 'documentsUrbanisme', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // CR de visite des organismes d'ins.... - OK
        '16' => ['dateInsert', 'objet', 'numeroChronologique', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'datePrefecture', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Etude suite a un avis ne se prononce pas - OK MAIS VOIR POUR PARTICULARITé TABLEAU
        '17' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Utilisation exceptionnelle de locaux - OK
        '18' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Levée de réserves - OK
        '19' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'avisCommission', 'observationDossier'],
        // Echéncier de travaux - OK
        '46' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Déclaration préalable
        '30' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier', 'documentsUrbanisme'],
        // RVRMD diag sécu
        '33' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Autorisation d'une ICPE - OK
        '61' => ['type', 'dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'coordonneesSsi', 'dateReponse', 'dateReception', 'preventionnistes', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Certificats d'urbanisme (CU) - OK
        '62' => ['type', 'dateInsert', 'objet', 'documentsUrbanisme', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'coordonneesSsi', 'dateReponse', 'dateReception', 'preventionnistes', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Demande d'organisation de manifestation temporaire - OK
        '63' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // Déclassement / Reclassement - OK
        '66' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'dateSecretariat', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observationDossier'],
        // VISITE DE COMMISSION
        // Réception de travaux - OK
        '20' => ['dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateVisite', 'coordonneesSsi', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'nePeutSePrononcer', 'avisCommission', 'observationDossier', 'dateReceptionRvrat', 'dateLimitePrescription'],
        // Avant ouverture - OK
        '47' => ['dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateVisite', 'coordonneesSsi', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'nePeutSePrononcer', 'avisCommission', 'observationDossier', 'dateReceptionRvrat', 'dateLimitePrescription'],
        // Périodique - OK
        '21' => ['dateInsert', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateVisite', 'dateReception', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avis', 'avisCommission', 'observationDossier', 'dateLimitePrescription'],
        // Chantier - OK
        '22' => ['dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateVisite', 'coordonneesSsi', 'dateReception', 'preventionnistes', 'observationDossier', 'dateLimitePrescription'],
        // Controle - OK
        '23' => ['dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateVisite', 'coordonneesSsi', 'dateReception', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avisCommission', 'observationDossier', 'dateLimitePrescription'],
        // Inopinéee - OK
        '24' => ['dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateVisite', 'dateReception', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avisCommission', 'observationDossier', 'dateLimitePrescription'],
        // Visite conseil - OK
        '64' => ['dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateVisite', 'coordonneesSsi', 'dateReception', 'preventionnistes', 'observationDossier', 'dateLimitePrescription'],
        // GROUPE DE VISITE
        // Réception de travaux - OK
        '25' => ['type', 'dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'dateVisite', 'avis', 'coordonneesSsi', 'dateReception', 'preventionnistes', 'nePeutSePrononcer', 'absenceDeQuorum', 'avisCommission', 'observationDossier', 'dateReceptionRvrat', 'dateLimitePrescription'],
        // Avant ouverture - OK
        '48' => ['dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'dateVisite', 'avis', 'coordonneesSsi', 'dateReception', 'preventionnistes', 'nePeutSePrononcer', 'absenceDeQuorum', 'avisCommission', 'observationDossier', 'dateReceptionRvrat', 'dateLimitePrescription'],
        // Périodique - OK
        '26' => ['dateInsert', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'dateVisite', 'avis', 'dateReception', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avisCommission', 'observationDossier', 'dateLimitePrescription'],
        // Chantier - OK
        '27' => ['dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateVisite', 'coordonneesSsi', 'dateReception', 'preventionnistes', 'observationDossier', 'dateLimitePrescription'],
        // Controle - OK
        '28' => ['dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'dateVisite', 'avis', 'coordonneesSsi', 'dateReception', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avisCommission', 'observationDossier', 'dateLimitePrescription'],
        // Inopinéee - OK
        '29' => ['dateInsert', 'objet', 'commission', 'descriptifGeneral', 'descriptifEffectifs', 'dateCommission', 'dateVisite', 'avis', 'dateReception', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avisCommission', 'observationDossier', 'dateLimitePrescription'],
        // REUNION (noms Symfony standardisés)
        // Locaux SDIS - OK
        // Legacy: DATEINSERT, OBJET, DATEREUN, DATEREP, PREVENTIONNISTE, DEMANDEUR, OBSERVATION
        '31' => ['dateInsert', 'objet', 'dateReunion', 'dateReponse', 'preventionnistes', 'demandeur', 'observations'],
        // Exterieur SDIS - OK
        // Legacy: DATEINSERT, OBJET, LIEUREUNION, DATEREUN, DATEREP, PREVENTIONNISTE, DEMANDEUR, OBSERVATION
        '32' => ['dateInsert', 'objet', 'lieuReunion', 'dateReunion', 'dateReponse', 'preventionnistes', 'demandeur', 'observations'],
        // Téléphonique - OK
        // Legacy: DATEINSERT, OBJET, DATEREUN, DATEREP, PREVENTIONNISTE, DEMANDEUR, OBSERVATION
        '43' => ['dateInsert', 'objet', 'dateReunion', 'dateReponse', 'preventionnistes', 'demandeur', 'observations'],
        // COURRIER/COURRIEL
        // Lettre - OK
        '52' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Mise en demeure - OK
        '55' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Avis écrit motivé - OK
        '51' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Consultation PLU - OK
        '53' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Rapport d'organisme agréé - OK
        '49' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Demande de renseignements
        '54' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Demande de visite périodique
        '59' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Demande de visite technique
        '57' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Demande de visite inopinée
        '58' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Demande de visite hors programme
        '50' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Demande de visite de réception
        '60' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // Autorisation de travaux
        '65' => ['dateInsert', 'objet', 'numeroChronologique', 'dateMairie', 'preventionnistes', 'dateReception', 'dateEnvoiTransit', 'preventionnistes', 'dateReponse', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'observationDossier'],
        // INTERVENTION
        // Incendie - OK
        '37' => ['dateInsert', 'objet', 'operationSdis', 'rcci', 'rex', 'numeroIntervention', 'dateIntervention', 'dureeIntervention', 'dateReception', 'preventionnistes', 'observationDossier'],
        // SAP - OK
        '38' => ['dateInsert', 'objet', 'operationSdis', 'rex', 'numeroIntervention', 'dateIntervention', 'dureeIntervention', 'dateReception', 'preventionnistes', 'observationDossier'],
        // Intervention div - OK
        '39' => ['dateInsert', 'objet', 'operationSdis', 'rex', 'numeroIntervention', 'dateIntervention', 'dureeIntervention', 'dateReception', 'preventionnistes', 'observationDossier'],
        // ARRETE
        // Ouverture - OK
        '40' => ['dateInsert', 'DATESIGN', 'dateReception', 'preventionnistes', 'observationDossier'],
        // Fermeture - OK
        '41' => ['dateInsert', 'objet', 'DATESIGN', 'dateReception', 'preventionnistes', 'observationDossier'],
        // Mise en demeure - OK
        '42' => ['dateInsert', 'objet', 'DATESIGN', 'dateReception', 'preventionnistes', 'observationDossier'],
        // Utilisation exceptionnelle de locaux - OK
        '44' => ['dateInsert', 'objet', 'DATESIGN', 'dateReception', 'preventionnistes', 'observationDossier'],
        // Courrier - OK
        '45' => ['dateInsert', 'objet', 'DATESIGN', 'dateReception', 'preventionnistes', 'observationDossier'],
    ];

    /**
     * Détermine si un champ doit être affiché pour un dossier donné.
     *
     * @param string $nomChamp Le nom du champ (ex: 'OPERSDIS', 'RCCI')
     */
    public function shouldDisplayField(Dossier $dossier, string $nomChamp): bool
    {
        $afficherChamps = $this->getFieldsToDisplay($dossier);

        return in_array($nomChamp, $afficherChamps, true);
    }

    /**
     * Récupère la liste des champs à afficher pour un dossier.
     *
     * @return array<string>
     */
    public function getFieldsToDisplay(Dossier $dossier): array
    {
        $nature = $dossier->getNature();

        if (null === $nature) {
            return [];
        }

        $idNature = (string) $nature->getId();

        if (!isset(self::LISTE_CHAMPS[$idNature])) {
            return [];
        }

        return self::LISTE_CHAMPS[$idNature];
    }

    /**
     * Retourne la liste complète des champs par nature sous forme de tableau associatif.
     * Utilisé pour générer le JSON pour le JavaScript.
     *
     * @return array<int, list<string>>
     */
    public function getAllFieldsByNature(): array
    {
        return self::LISTE_CHAMPS;
    }

    /**
     * Retourne les champs d'une nature spécifique.
     *
     * @return array<string>
     */
    public function getFieldsForNature(int $natureId): array
    {
        $idNature = (string) $natureId;

        if (!isset(self::LISTE_CHAMPS[$idNature])) {
            return [];
        }

        return self::LISTE_CHAMPS[$idNature];
    }
}
