<?php

namespace App\Service;

use App\Entity\ListeDocumentConsulte;
use App\Repository\ListeDocumentConsulteRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DocumentsConsultes
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var ListeDocumentConsulteRepository */
    private $listeDocumentConsulteRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->listeDocumentConsulteRepository = $entityManager->getRepository(ListeDocumentConsulte::class);
    }

    public function prepareOrdre(ListeDocumentConsulte $documentConsulte): void
    {
        $lastOrdre = $this->listeDocumentConsulteRepository->findMaxOrdre();
        $documentConsulte->setOrdre($lastOrdre + 1);
    }

    public function save(ListeDocumentConsulte $document): void
    {
        if (null === $document->getId()) {
            $this->prepareOrdre($document);
        }

        $this->listeDocumentConsulteRepository->add($document);
    }
}
