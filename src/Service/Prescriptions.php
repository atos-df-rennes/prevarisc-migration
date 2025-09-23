<?php

namespace App\Service;

use App\Entity\PrescriptionArticleListe;
use App\Entity\PrescriptionTexteListe;
use App\Repository\PrescriptionArticleListeRepository;
use App\Repository\PrescriptionTexteListeRepository;

final class Prescriptions
{
    /** @var PrescriptionTexteListeRepository */
    private $prescriptionTexteListeRepository;

    /** @var PrescriptionArticleListeRepository */
    private $prescriptionArticleListeRepository;

    public function __construct(
        PrescriptionTexteListeRepository $prescriptionTexteListeRepository,
        PrescriptionArticleListeRepository $prescriptionArticleListeRepository
    ){
        $this->prescriptionTexteListeRepository = $prescriptionTexteListeRepository;
        $this->prescriptionArticleListeRepository = $prescriptionArticleListeRepository;
    }

    /**
     * @return PrescriptionTexteListe[]
     */
    public function getTextesListe(): array
    {
        return $this->prescriptionTexteListeRepository->findAllTextes();
    }

    /**
     * @param bool|null $isVisible
     * @return PrescriptionArticleListe[]
     */
    public function getArticlesListe(?bool $isVisible = null):array
    {
        $criteria = [];
        if($isVisible !== null) {
            $criteria['visible'] = $isVisible;
        }

        return $this->prescriptionArticleListeRepository->findBy($criteria, ['libelle' => 'ASC']);
    }
}
