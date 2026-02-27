<?php

namespace App\Service\Voter;

use App\Security\Voter\Etablissement\EtablissementBupVoter;
use App\Security\Voter\Etablissement\EtablissementCampingVoter;
use App\Security\Voter\Etablissement\EtablissementCelluleVoter;
use App\Security\Voter\Etablissement\EtablissementErpVoter;
use App\Security\Voter\Etablissement\EtablissementHabitationVoter;
use App\Security\Voter\Etablissement\EtablissementIghVoter;
use App\Security\Voter\Etablissement\EtablissementIopVoter;
use App\Security\Voter\Etablissement\EtablissementManifestationTemporaireVoter;
use App\Security\Voter\Etablissement\EtablissementZoneVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Factory pour récupérer le voter approprié selon le genre d'établissement.
 *
 * Implémente le pattern Factory pour déléguer la logique de vote aux voters spécialisés.
 */
class EtablissementVoterFactory
{
    /** @var EtablissementErpVoter */
    private $erpVoter;

    /** @var EtablissementCelluleVoter */
    private $celluleVoter;

    /** @var EtablissementHabitationVoter */
    private $habitationVoter;

    /** @var EtablissementIghVoter */
    private $ighVoter;

    /** @var EtablissementBupVoter */
    private $bupVoter;

    /** @var EtablissementCampingVoter */
    private $campingVoter;

    /** @var EtablissementManifestationTemporaireVoter */
    private $manifestationTemporaireVoter;

    /** @var EtablissementIopVoter */
    private $iopVoter;

    /** @var EtablissementZoneVoter */
    private $zoneVoter;

    public function __construct(
        EtablissementErpVoter $erpVoter,
        EtablissementCelluleVoter $celluleVoter,
        EtablissementHabitationVoter $habitationVoter,
        EtablissementIghVoter $ighVoter,
        EtablissementBupVoter $bupVoter,
        EtablissementCampingVoter $campingVoter,
        EtablissementManifestationTemporaireVoter $manifestationTemporaireVoter,
        EtablissementIopVoter $iopVoter,
        EtablissementZoneVoter $zoneVoter
    ) {
        $this->erpVoter = $erpVoter;
        $this->celluleVoter = $celluleVoter;
        $this->habitationVoter = $habitationVoter;
        $this->ighVoter = $ighVoter;
        $this->bupVoter = $bupVoter;
        $this->campingVoter = $campingVoter;
        $this->manifestationTemporaireVoter = $manifestationTemporaireVoter;
        $this->iopVoter = $iopVoter;
        $this->zoneVoter = $zoneVoter;
    }

    /**
     * Retourne le voter approprié pour un genre donné.
     *
     * @return VoterInterface|null null si le genre n'a pas de voter associé
     */
    public function getVoterForGenre(int $genreId): ?VoterInterface
    {
        switch ($genreId) {
            case EtablissementErpVoter::ID_ERP:
                return $this->erpVoter;

            case EtablissementCelluleVoter::ID_CELLULE:
                return $this->celluleVoter;

            case EtablissementHabitationVoter::ID_HABITATION:
                return $this->habitationVoter;

            case EtablissementIghVoter::ID_IGH:
                return $this->ighVoter;

            case EtablissementBupVoter::ID_BUP:
                return $this->bupVoter;

            case EtablissementCampingVoter::ID_CAMPING:
                return $this->campingVoter;

            case EtablissementManifestationTemporaireVoter::ID_MANIFESTATION_TEMPORAIRE:
                return $this->manifestationTemporaireVoter;

            case EtablissementIopVoter::ID_IOP:
                return $this->iopVoter;

            case EtablissementZoneVoter::ID_ZONE:
                return $this->zoneVoter;

            default:
                return null;
        }
    }
}
