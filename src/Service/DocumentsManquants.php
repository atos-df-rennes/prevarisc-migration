<?php

namespace App\Service;

use App\Entity\DocumentManquant;
use App\Repository\DocumentManquantRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DocumentsManquants
{
    /** @var DocumentManquantRepository */
    private $documentManquantRepository;

    public function __construct(DocumentManquantRepository $repository)
    {
        $this->documentManquantRepository = $repository;
    }

    public function save(DocumentManquant $documentManquant): void
    {
        $this->documentManquantRepository->add($documentManquant);
    }

    public function create(DocumentManquant $documentManquant): void
    {
        $this->save($documentManquant);
    }
}
