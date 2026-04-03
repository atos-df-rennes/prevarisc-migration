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

        if (in_array($type->getId(), DossierType::TYPES_VISITE)) {
            if (in_array($nature->getId(), DossierNature::RECEPTIONS_TRAVAUX)) {
                $docConsultables = $this->listeDocumentConsulteRepository->getDocVisiteRT();
            } elseif (in_array($nature->getId(), DossierNature::VISITE_AVANT_OUVERTURE)) {
                $docConsultables = $this->listeDocumentConsulteRepository->getDocVisiteVAO();
            } else {
                $docConsultables = $this->listeDocumentConsulteRepository->getDocVisite();
            }
        } elseif (DossierType::ETUDE === $type->getId()) {
            if (in_array($nature->getId(), DossierNature::VISITE_STD)) {
                $docConsultables = $this->listeDocumentConsulteRepository->getDocVisite();
            } else {
                $docConsultables = $this->listeDocumentConsulteRepository->getDocEtude();
            }
        } else {
            $docConsultables = [];
        }

        return $docConsultables;
    }

    public function getLibelleDocumentConsulte(string $idDocument): ?string
    {
        $documentConsulte = $this->listeDocumentConsulteRepository->findOneBy(['id' => $idDocument]);

        return $documentConsulte ? $documentConsulte->getLibelle() : null;
    }
}
