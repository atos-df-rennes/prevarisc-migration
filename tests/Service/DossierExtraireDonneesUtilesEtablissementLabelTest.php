<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Dossier as DossierEntity;
use App\Entity\DossierNatureListe;
use App\Entity\DossierType;
use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Repository\DossierRepository;
use App\Service\Dossier;
use App\Service\Platau\PlatauTempsRestant;
use App\Service\TexteApplicable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Tests pour Dossier::extraireDonneesUtiles() - résolution du libellé établissement historique.
 *
 * Contexte : le legacy (Search.php) résout le libellé établissement affiché dans la liste des
 * dossiers en fonction de la date du dossier (DATECOMM/DATEVISITE/DATEINSERT) et de l'historique
 * des fiches etablissementinformations, et non systématiquement la fiche la plus récente.
 *
 * Iso-legacy (quirk assumé) : quand DATECOMM_DOSSIER est renseignée, le legacy compare en réalité
 * à DATEINSERT_DOSSIER (et non DATECOMM_DOSSIER).
 */
final class DossierExtraireDonneesUtilesEtablissementLabelTest extends TestCase
{
    private Dossier $service;

    protected function setUp(): void
    {
        $dossierRepository = $this->createStub(DossierRepository::class);
        $dossierRepository->method('hasAlerteReceptionTravauxBulk')->willReturn([]);
        $dossierRepository->method('hasEcheancierTravauxBulk')->willReturn([]);
        $dossierRepository->method('countPiecesJointesBulk')->willReturn([]);

        $this->service = new Dossier(
            $this->createStub(EntityManagerInterface::class),
            $dossierRepository,
            $this->createStub(Security::class),
            new PlatauTempsRestant(),
            $this->createStub(TexteApplicable::class)
        );
    }

    private function creerEtablissementAvecHistorique(): Etablissement
    {
        $etablissement = new Etablissement();

        $ancienne = new EtablissementInformations();
        $ancienne->setDate(new \DateTime('2010-01-01'));
        $ancienne->setLibelle('Ancien nom');

        $etablissement->addInformations($ancienne);

        $recente = new EtablissementInformations();
        $recente->setDate(new \DateTime('2022-01-01'));
        $recente->setLibelle('Nom actuel');

        $etablissement->addInformations($recente);

        return $etablissement;
    }

    private function creerDossier(Etablissement $etablissement): DossierEntity
    {
        $dossier = new DossierEntity();
        $dossier->addEtablissement($etablissement);
        $dossier->setType(new DossierType());
        $dossier->setNature(new DossierNatureListe());
        $dossier->setDateInsert(new \DateTime('2015-06-01'));

        $this->setEntityId($dossier, 1);

        return $dossier;
    }

    private function setEntityId(DossierEntity $entity, int $id): void
    {
        $ref = new \ReflectionProperty($entity::class, 'id');
        $ref->setValue($entity, $id);
    }

    public function testDateCommissionRenseigneeUtiliseDateInsertIsoLegacyQuirk(): void
    {
        $etablissement = $this->creerEtablissementAvecHistorique();
        $dossier = $this->creerDossier($etablissement);
        $dossier->setDateCommission('2021-01-01'); // Date largement postérieure à DATEINSERT.
        $dossier->setDateVisite(new \DateTime('2021-01-01'));

        $result = $this->service->extraireDonneesUtiles([$dossier]);

        // Doit correspondre à la fiche en vigueur au 2015-06-01 (DATEINSERT), pas au 2021-01-01 (DATECOMM).
        $this->assertSame('Ancien nom', $result[0]['etablissement']);
    }

    public function testDateVisiteUtiliseeQuandDateCommissionAbsente(): void
    {
        $etablissement = $this->creerEtablissementAvecHistorique();
        $dossier = $this->creerDossier($etablissement);
        $dossier->setDateVisite(new \DateTime('2023-01-01'));

        $result = $this->service->extraireDonneesUtiles([$dossier]);

        $this->assertSame('Nom actuel', $result[0]['etablissement']);
    }

    public function testDateInsertUtiliseeQuandAucuneAutreDate(): void
    {
        $etablissement = $this->creerEtablissementAvecHistorique();
        $dossier = $this->creerDossier($etablissement);
        // DATEINSERT = 2015-06-01, uniquement date disponible.

        $result = $this->service->extraireDonneesUtiles([$dossier]);

        $this->assertSame('Ancien nom', $result[0]['etablissement']);
    }

    public function testEtablissementSansFicheRenvoieLibelleNull(): void
    {
        $etablissement = new Etablissement();
        $dossier = $this->creerDossier($etablissement);

        $result = $this->service->extraireDonneesUtiles([$dossier]);

        $this->assertNull($result[0]['etablissement']);
    }
}
