<?php

namespace App\Service;

use App\Entity\PrescriptionTexteListe;
use Doctrine\ORM\EntityManagerInterface;

final class TexteApplicable
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    public function save(\App\Entity\TexteApplicable $texteApplicable, bool $prescription): void
    {
        if ($prescription) {
            $prescriptionListe = new PrescriptionTexteListe();
            $prescriptionListe->setLibelle($texteApplicable->getLibelle() ?: '');
            $this->entityManager->persist($prescriptionListe);
        }

        $this->entityManager->persist($texteApplicable);
        $this->entityManager->flush();
    }
}
