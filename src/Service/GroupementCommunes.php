<?php

namespace App\Service;

use App\Repository\EtablissementInformationsRepository;

final class GroupementCommunes
{
    /** @var EtablissementInformationsRepository */
    private $etablissementInformationsRepository;

    public function __construct(
        EtablissementInformationsRepository $etablissementInformationsRepository
    ) {
        $this->etablissementInformationsRepository = $etablissementInformationsRepository;
    }

    public function reaffectationPreventioniste(string $groupement): void
    {
        $this->etablissementInformationsRepository->deleteEtablissementsPreventioniste($groupement);
        $this->etablissementInformationsRepository->deleteCellulesPreventioniste($groupement);
        $this->etablissementInformationsRepository->deleteSitesPreventioniste($groupement);

        $etablissementsPreventioniste = $this->etablissementInformationsRepository->getEtablissementsPreventioniste($groupement);
        $this->etablissementInformationsRepository->addPreventioniste($etablissementsPreventioniste);

        $cellulesPreventioniste = $this->etablissementInformationsRepository->getCellulesPreventioniste($groupement);
        $this->etablissementInformationsRepository->addPreventioniste($cellulesPreventioniste);

        $sitesPreventioniste = $this->etablissementInformationsRepository->getSitesPreventioniste($groupement);
        $this->etablissementInformationsRepository->addPreventioniste($sitesPreventioniste);
    }
}
