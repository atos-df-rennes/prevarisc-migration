<?php

namespace App\Service\Voter;

use App\Constant\GenreEtablissement;
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
    public function __construct(private readonly EtablissementErpVoter $erpVoter, private readonly EtablissementCelluleVoter $celluleVoter, private readonly EtablissementHabitationVoter $habitationVoter, private readonly EtablissementIghVoter $ighVoter, private readonly EtablissementBupVoter $bupVoter, private readonly EtablissementCampingVoter $campingVoter, private readonly EtablissementManifestationTemporaireVoter $manifestationTemporaireVoter, private readonly EtablissementIopVoter $iopVoter, private readonly EtablissementZoneVoter $zoneVoter)
    {
    }

    /**
     * Retourne le voter approprié pour un genre donné.
     *
     * @return VoterInterface|null null si le genre n'a pas de voter associé
     */
    public function getVoterForGenre(int $genreId): ?VoterInterface
    {
        return match ($genreId) {
            GenreEtablissement::ERP => $this->erpVoter,
            GenreEtablissement::CELLULE => $this->celluleVoter,
            GenreEtablissement::HABITATION => $this->habitationVoter,
            GenreEtablissement::IGH => $this->ighVoter,
            GenreEtablissement::BUP => $this->bupVoter,
            GenreEtablissement::CAMPING => $this->campingVoter,
            GenreEtablissement::MANIFESTATION_TEMPORAIRE => $this->manifestationTemporaireVoter,
            GenreEtablissement::IOP => $this->iopVoter,
            GenreEtablissement::ZONE => $this->zoneVoter,
            default => null,
        };
    }
}
