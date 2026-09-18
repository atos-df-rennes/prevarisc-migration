<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Dossier;
use App\Entity\DossierDocumentManquant;
use App\Service\DossierDocumentManquantService;
use PHPUnit\Framework\TestCase;

final class DossierDocumentManquantServiceTest extends TestCase
{
    private DossierDocumentManquantService $service;

    protected function setUp(): void
    {
        $this->service = new DossierDocumentManquantService();
    }

    public function testAssignerNumerosAttribueUnNumeroAuxNouveauxDocuments(): void
    {
        $dossier = new Dossier();

        $document = new DossierDocumentManquant();
        $dossier->addDocumentsManquant($document);

        $this->service->assignerNumeros($dossier);

        $this->assertSame('1', $document->getNumero());
    }

    public function testAssignerNumerosNeModifiePasLesNumerosExistants(): void
    {
        $dossier = new Dossier();

        $documentExistant = new DossierDocumentManquant();
        $documentExistant->setNumero('3');

        $dossier->addDocumentsManquant($documentExistant);

        $nouveauDocument = new DossierDocumentManquant();
        $dossier->addDocumentsManquant($nouveauDocument);

        $this->service->assignerNumeros($dossier);

        $this->assertSame('3', $documentExistant->getNumero());
        $this->assertSame('4', $nouveauDocument->getNumero());
    }

    public function testAssignerNumerosGereUnDossierSansDocument(): void
    {
        $dossier = new Dossier();

        $this->service->assignerNumeros($dossier);

        $this->assertCount(0, $dossier->getDocumentsManquants());
    }
}
