<?php

namespace App\Service;

use App\DTO\PlatauTempsRestantInfo;
use App\Entity\PlatauConsultation as PlatauConsultationEntity;
use App\Enum\PlatauStatutAvisEnum;

/**
 * Service métier pour la gestion des consultations Plat'AU.
 * Centralise la logique d'affichage et de traitement.
 */
final class PlatauConsultation
{
    /** @var PlatauTempsRestant */
    private $platauTempsRestantService;

    public function __construct(PlatauTempsRestant $platauTempsRestantService)
    {
        $this->platauTempsRestantService = $platauTempsRestantService;
    }

    /**
     * Calcule les informations de temps restant pour une consultation.
     * Retourne null si :
     * - La date de réponse attendue n'est pas définie
     * - L'avis a déjà été envoyé (statut treated, to_export ou unknown).
     */
    public function getTempsRestantInfo(?PlatauConsultationEntity $consultation): PlatauTempsRestantInfo
    {
        if (null === $consultation) {
            return PlatauTempsRestantInfo::empty();
        }

        $dateReponseAttendue = $consultation->getDateReponseAttendue();
        if (null === $dateReponseAttendue) {
            return PlatauTempsRestantInfo::empty();
        }

        // Ne pas afficher le temps restant si l'avis est déjà traité
        $statutAvis = $consultation->getStatutAvis();
        if ($this->isAvisTraite($statutAvis)) {
            return PlatauTempsRestantInfo::empty();
        }

        // Calculer le temps restant et sa couleur
        $tempsRestant = $this->platauTempsRestantService->calculate($dateReponseAttendue);
        $couleur = $this->platauTempsRestantService->getCouleurTempsRestant($dateReponseAttendue);

        return new PlatauTempsRestantInfo($tempsRestant, $couleur);
    }

    /**
     * Prépare les données Plat'AU pour l'affichage dans les templates.
     *
     * @return array<string, mixed>|null
     */
    public function getPlatauDataForView(?PlatauConsultationEntity $consultation): ?array
    {
        if (null === $consultation) {
            return null;
        }

        $tempsRestantInfo = $this->getTempsRestantInfo($consultation);

        return [
            'identifiant' => $consultation->getIdentifiant(),
            'temps_restant' => $tempsRestantInfo->getTempsRestant(),
            'couleur_temps_restant' => $tempsRestantInfo->getCouleur(),
            'statut_pec' => $consultation->getStatutPriseEnCompte(),
            'date_pec' => $consultation->getDatePriseEnCompte(),
            'statut_avis' => $consultation->getStatutAvis(),
            'date_avis' => $consultation->getDateAvis(),
        ];
    }

    /**
     * Vérifie si l'avis est dans un état "traité" (plus besoin d'afficher le temps restant).
     */
    private function isAvisTraite(?string $statutAvis): bool
    {
        return \in_array($statutAvis, [
            PlatauStatutAvisEnum::TRAITE,
            PlatauStatutAvisEnum::A_RENVOYER,
            PlatauStatutAvisEnum::INCONNU,
        ], true);
    }
}
