<?php

namespace App\Service;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;

/**
 * Service de gestion des champs à afficher selon la nature du dossier.
 */
final class DossierFields
{
    private const array LISTE_CHAMPS = [
        // ETUDES
        // PC - OK
        '1' => ['type', 'dateInsert', 'referenceCourrier', 'objet', 'documentsUrbanisme', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // AT - OK
        '2' => ['type', 'dateInsert', 'referenceCourrier', 'objet', 'documentsUrbanisme', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Dérogation - OK
        '3' => ['type', 'dateInsert', 'referenceCourrier', 'objet', 'documentsUrbanisme', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'justificationDerogation', 'mesuresCompensatoires', 'mesuresComplementaires', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'reglesDerogation', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Cahier des charges fonctionnel du SSI - OK
        '4' => ['type', 'dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Cahier des charges de type T - OK
        '5' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Salon type T - OK
        '6' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'chargeSecurite', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // RVRMD (diag sécu) => Levée de prescriptions - OK
        '7' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Documents divers - OK
        '8' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'datePrefecture', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Changement de DUS - OK
        '9' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Suivi organisme formation SSIAP - OK
        '10' => ['dateInsert', 'referenceCourrier', 'objet', 'avis', 'dateReponse', 'datePrefecture', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Demande de registre de sécurité CTS - OK
        '11' => ['dateInsert', 'referenceCourrier', 'objet', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReception', 'datePrefecture', 'dateReponse', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Demande d'implantation CTS < 6mois - OK
        '12' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Demande d'implantation CTS > 6mois - OK
        '13' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Permis d'aménager - OK
        '14' => ['dateInsert', 'referenceCourrier', 'objet', 'documentsUrbanisme', 'commission', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Permis de démolir - OK
        '15' => ['dateInsert', 'referenceCourrier', 'objet', 'documentsUrbanisme', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // CR de visite des organismes d'ins.... - OK
        '16' => ['dateInsert', 'referenceCourrier', 'objet', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'datePrefecture', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Etude suite a un avis ne se prononce pas - OK MAIS VOIR POUR PARTICULARITé TABLEAU
        '17' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Utilisation exceptionnelle de locaux - OK
        '18' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Levée de réserves - OK
        '19' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'demandeur', 'avisCommission', 'observations'],
        // Echéncier de travaux - OK
        '46' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Déclaration préalable
        '30' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations', 'documentsUrbanisme'],
        // RVRMD diag sécu
        '33' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Autorisation d'une ICPE - OK
        '61' => ['type', 'dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Certificats d'urbanisme (CU) - OK
        '62' => ['type', 'dateInsert', 'referenceCourrier', 'objet', 'documentsUrbanisme', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Demande d'organisation de manifestation temporaire - OK
        '63' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'serviceInstructeur', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // Déclassement / Reclassement - OK
        '66' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateSecretariat', 'commission', 'dateCommission', 'avis', 'dateReponse', 'dateReception', 'preventionnistes', 'absenceDeQuorum', 'demandeur', 'incomplet', 'horsDelai', 'avisCommission', 'observations'],
        // VISITE DE COMMISSION
        // Réception de travaux - OK
        '20' => ['dateInsert', 'referenceCourrier', 'objet', 'documentsUrbanisme', 'commission', 'dateVisite', 'dateReponse', 'preventionnistes', 'absenceDeQuorum', 'nePeutSePrononcer', 'avisCommission', 'observations', 'dateReceptionRvrat', 'dateLimitePrescription'],
        // Avant ouverture - OK
        '47' => ['dateInsert', 'referenceCourrier', 'objet', 'documentsUrbanisme', 'commission', 'dateVisite', 'dateReponse', 'preventionnistes', 'absenceDeQuorum', 'nePeutSePrononcer', 'avisCommission', 'observations', 'dateReceptionRvrat', 'dateLimitePrescription'],
        // Périodique - OK
        // Note: analyseDeRisque (ANOMALIE) n'est pas dans la liste car affiché uniquement si valeur existe (comportement legacy)
        '21' => ['dateInsert', 'referenceCourrier', 'commission', 'dateVisite', 'dateReponse', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avis', 'avisCommission', 'observations', 'dateLimitePrescription'],
        // Chantier - OK
        '22' => ['dateInsert', 'referenceCourrier', 'objet', 'commission', 'dateVisite', 'dateReponse', 'preventionnistes', 'observations', 'dateLimitePrescription'],
        // Controle - OK
        '23' => ['dateInsert', 'referenceCourrier', 'objet', 'commission', 'dateVisite', 'dateReponse', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avisCommission', 'observations', 'dateLimitePrescription'],
        // Inopinéee - OK
        '24' => ['dateInsert', 'referenceCourrier', 'objet', 'commission', 'dateVisite', 'dateReponse', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avisCommission', 'observations', 'dateLimitePrescription'],
        // Visite conseil - OK
        '64' => ['dateInsert', 'referenceCourrier', 'objet', 'commission', 'dateVisite', 'dateReponse', 'preventionnistes', 'observations', 'dateLimitePrescription'],
        // GROUPE DE VISITE
        // Réception de travaux - OK
        '25' => ['type', 'dateInsert', 'referenceCourrier', 'objet', 'documentsUrbanisme', 'commission', 'dateCommission', 'dateVisite', 'avis', 'dateReponse', 'preventionnistes', 'nePeutSePrononcer', 'absenceDeQuorum', 'avisCommission', 'observations', 'dateReceptionRvrat', 'dateLimitePrescription'],
        // Avant ouverture - OK
        '48' => ['dateInsert', 'referenceCourrier', 'objet', 'documentsUrbanisme', 'commission', 'dateCommission', 'dateVisite', 'avis', 'dateReponse', 'preventionnistes', 'nePeutSePrononcer', 'absenceDeQuorum', 'avisCommission', 'observations', 'dateReceptionRvrat', 'dateLimitePrescription'],
        // Périodique - OK
        '26' => ['dateInsert', 'referenceCourrier', 'commission', 'dateCommission', 'dateVisite', 'avis', 'dateReponse', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avisCommission', 'observations', 'dateLimitePrescription'],
        // Chantier - OK
        '27' => ['dateInsert', 'referenceCourrier', 'objet', 'commission', 'dateVisite', 'dateReponse', 'preventionnistes', 'observations', 'dateLimitePrescription'],
        // Controle - OK (Nature 28 - Groupe de visite contrôle)
        '28' => ['dateInsert', 'referenceCourrier', 'objet', 'commission', 'dateCommission', 'dateVisite', 'avis', 'dateReponse', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avisCommission', 'observations', 'dateLimitePrescription'],
        // Inopinéee - OK (Nature 29 - Groupe de visite inopinée)
        '29' => ['dateInsert', 'referenceCourrier', 'objet', 'commission', 'dateCommission', 'dateVisite', 'avis', 'dateReponse', 'preventionnistes', 'avisDiffere', 'absenceDeQuorum', 'avisCommission', 'observations', 'dateLimitePrescription'],
        // REUNION (noms Symfony standardisés)
        // Locaux SDIS - OK
        // Legacy: DATEINSERT, OBJET, DATEREUN, DATEREP, PREVENTIONNISTE, DEMANDEUR, OBSERVATION
        '31' => ['dateInsert', 'referenceCourrier', 'objet', 'dateReunion', 'dateReponse', 'preventionnistes', 'demandeur', 'observations'],
        // Exterieur SDIS - OK
        // Legacy: DATEINSERT, OBJET, LIEUREUNION, DATEREUN, DATEREP, PREVENTIONNISTE, DEMANDEUR, OBSERVATION
        '32' => ['dateInsert', 'referenceCourrier', 'objet', 'lieuReunion', 'dateReunion', 'dateReponse', 'preventionnistes', 'demandeur', 'observations'],
        // Téléphonique - OK
        // Legacy: DATEINSERT, OBJET, DATEREUN, DATEREP, PREVENTIONNISTE, DEMANDEUR, OBSERVATION
        '43' => ['dateInsert', 'referenceCourrier', 'objet', 'dateReunion', 'dateReponse', 'preventionnistes', 'demandeur', 'observations'],
        // COURRIER/COURRIEL (noms Symfony standardisés)
        // Lettre - OK
        // Legacy: DATEINSERT, OBJET, NUMCHRONO, DATEMAIRIE, PREVENTIONNISTE, DATEREP, DATEENVTRANSIT, PREVENTIONNISTE, DATESDIS, DEMANDEUR, DATETRANSFERTCOMM, DATERECEPTIONCOMM, OBSERVATION
        '52' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Mise en demeure - OK
        '55' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Avis écrit motivé - OK
        '51' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Consultation PLU - OK
        '53' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Rapport d'organisme agréé - OK
        '49' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Demande de renseignements - OK
        '54' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Demande de visite périodique - OK
        // Legacy: DATEINSERT, OBJET, NUMCHRONO, DATEMAIRIE, PREVENTIONNISTE, DATEREP, DATEENVTRANSIT, PREVENTIONNISTE, DATESDIS, DEMANDEUR, DATETRANSFERTCOMM, DATERECEPTIONCOMM, OBSERVATION
        '59' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Demande de visite technique - OK
        '57' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Demande de visite inopinée - OK
        '58' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Demande de visite hors programme - OK
        '50' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Demande de visite de réception - OK
        '60' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // Autorisation de travaux - OK
        '65' => ['dateInsert', 'referenceCourrier', 'objet', 'dateMairie', 'dateEnvoiTransit', 'dateReponse', 'dateReception', 'demandeur', 'dateTransfertCommission', 'dateReceptionCommission', 'preventionnistes', 'observations'],
        // INTERVENTION
        // Incendie - OK
        '37' => ['dateInsert', 'referenceCourrier', 'objet', 'operationSdis', 'rcci', 'rex', 'numeroIntervention', 'dateIntervention', 'dureeIntervention', 'dateReponse', 'preventionnistes', 'observations'],
        // SAP - OK
        '38' => ['dateInsert', 'referenceCourrier', 'objet', 'operationSdis', 'rex', 'numeroIntervention', 'dateIntervention', 'dureeIntervention', 'dateReponse', 'preventionnistes', 'observations'],
        // Intervention div - OK
        '39' => ['dateInsert', 'referenceCourrier', 'objet', 'operationSdis', 'rex', 'numeroIntervention', 'dateIntervention', 'dureeIntervention', 'dateReponse', 'preventionnistes', 'observations'],
        // ARRETE
        // Ouverture - OK
        '40' => ['dateInsert', 'referenceCourrier', 'dateSignature', 'dateReponse', 'preventionnistes', 'observations'],
        // Fermeture - OK
        '41' => ['dateInsert', 'referenceCourrier', 'objet', 'dateSignature', 'dateReponse', 'preventionnistes', 'observations'],
        // Mise en demeure - OK
        '42' => ['dateInsert', 'referenceCourrier', 'objet', 'dateSignature', 'dateReponse', 'preventionnistes', 'observations'],
        // Utilisation exceptionnelle de locaux - OK
        '44' => ['dateInsert', 'referenceCourrier', 'objet', 'dateSignature', 'dateReponse', 'preventionnistes', 'observations'],
        // Courrier - OK
        '45' => ['dateInsert', 'referenceCourrier', 'objet', 'dateSignature', 'dateReponse', 'preventionnistes', 'observations'],
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

        if (!$nature instanceof DossierNatureListe) {
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
