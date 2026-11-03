<?php

namespace App\Service\Platau;

use App\Dto\PlatauTempsRestantInfo;
use App\Entity\Avis;
use App\Entity\Dossier;
use App\Entity\PlatauConsultation as PlatauConsultationEntity;
use App\Enum\PlatauStatutAvisEnum;
use App\Enum\PlatauStatutPecEnum;

/**
 * Service métier pour la gestion des consultations Plat'AU.
 * Centralise la logique d'affichage et de traitement.
 */
final readonly class PlatauConsultation
{
    public function __construct(private PlatauTempsRestant $platauTempsRestantService)
    {
    }

    /**
     * Calcule les informations de temps restant pour une consultation.
     * Retourne null si :
     * - La date de réponse attendue n'est pas définie
     * - L'avis a déjà été envoyé (statut treated, to_export ou unknown).
     */
    public function getTempsRestantInfo(?PlatauConsultationEntity $consultation): PlatauTempsRestantInfo
    {
        if (!$consultation instanceof PlatauConsultationEntity) {
            return PlatauTempsRestantInfo::empty();
        }

        $dateReponseAttendue = $consultation->getDateReponseAttendue();
        if (!$dateReponseAttendue instanceof \DateTimeInterface) {
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
    public function getPlatauDataForView(?PlatauConsultationEntity $consultation, Dossier $dossier): ?array
    {
        if (!$consultation instanceof PlatauConsultationEntity) {
            return null;
        }

        $tempsRestantInfo = $this->getTempsRestantInfo($consultation);

        return [
            'identifiant' => $consultation->getIdentifiant(),
            'temps_restant' => $tempsRestantInfo->getTempsRestant(),
            'couleur_temps_restant' => $tempsRestantInfo->getCouleur(),
            'statut_pec' => $this->resolveStatutPecAffiche($consultation->getStatutPriseEnCompte(), $dossier->isIncomplet()),
            'date_pec' => $consultation->getDatePriseEnCompte(),
            'statut_avis' => $this->resolveStatutAvisAffiche($consultation->getStatutAvis(), $dossier->getAvisCommission() instanceof Avis),
            'date_avis' => $consultation->getDateAvis(),
        ];
    }

    private function resolveStatutPecAffiche(?string $statut, ?bool $incomplet): ?string
    {
        if (PlatauStatutPecEnum::EN_ATTENTE === $statut && null !== $incomplet) {
            return PlatauStatutPecEnum::SAISIE_A_TRANSMETTRE;
        }

        return $statut;
    }

    private function resolveStatutAvisAffiche(?string $statut, bool $avisRenseigne): ?string
    {
        if ($avisRenseigne && \in_array($statut, [null, PlatauStatutAvisEnum::INCONNU, PlatauStatutAvisEnum::EN_COURS], true)) {
            return PlatauStatutAvisEnum::SAISI_A_TRANSMETTRE;
        }

        return $statut;
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
