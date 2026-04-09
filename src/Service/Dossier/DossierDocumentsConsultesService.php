<?php

namespace App\Service\Dossier;

use App\Constant\DossierNature;
use App\Constant\DossierType;
use App\Entity\Dossier as DossierEntity;
use App\Entity\ListeDocumentConsulte;
use App\Repository\ListeDocumentConsulteRepository;

final class DossierDocumentsConsultesService
{
    /** @var ListeDocumentConsulteRepository */
    private $listeDocumentConsulteRepository;

    public function __construct(ListeDocumentConsulteRepository $listeDocumentConsulteRepository)
    {
        $this->listeDocumentConsulteRepository = $listeDocumentConsulteRepository;
    }

    /**
     * @return array<ListeDocumentConsulte>
     */
    public function getDocumentsConsultables(DossierEntity $dossier): array
    {
        $nature = $dossier->getNature();
        $type = $dossier->getType();

        if (null === $nature || null === $type) {
            return [];
        }

        $isVisite = in_array($type->getId(), DossierType::TYPES_VISITE);
        $isEtude = DossierType::ETUDE === $type->getId();

        if (!$isVisite && !$isEtude) {
            return [];
        }

        if ($isVisite) {
            if (in_array($nature->getId(), DossierNature::RECEPTIONS_TRAVAUX)) {
                return $this->listeDocumentConsulteRepository->getDocVisiteRT();
            }

            if (in_array($nature->getId(), DossierNature::VISITE_AVANT_OUVERTURE)) {
                return $this->listeDocumentConsulteRepository->getDocVisiteVAO();
            }

            return $this->listeDocumentConsulteRepository->getDocVisite();
        }

        // Cas étude : certaines natures utilisent les docs de visite (levées)
        if (in_array($nature->getId(), DossierNature::LEVEES)) {
            return $this->listeDocumentConsulteRepository->getDocVisite();
        }

        return $this->listeDocumentConsulteRepository->getDocEtude();
    }

    public function getLibelleDocumentConsulte(string $idDocument): ?string
    {
        $documentConsulte = $this->listeDocumentConsulteRepository->findOneBy(['id' => $idDocument]);

        return $documentConsulte ? $documentConsulte->getLibelle() : null;
    }
}
