<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Avis;
use App\Entity\Dossier;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Service\EtablissementHistoriqueService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class EtablissementHistoriqueServiceTest extends TestCase
{
    #[DataProvider('lastAvisLibelleProvider')]
    public function testGetLastAvisLibelle(array $dossiersDataParams, string $expected): void
    {
        $etablissementRepository = $this->createStub(EtablissementRepository::class);
        $dossierRepository = $this->createMock(DossierRepository::class);
        $logger = $this->createStub(LoggerInterface::class);

        // Construire les mocks de dossiers à partir des paramètres
        $dossiersData = [];
        foreach ($dossiersDataParams as $params) {
            $dossiersData[] = $this->createDossierMock($params['avisLibelle'], $params['date']);
        }

        $dossierRepository->expects($this->once())
            ->method('getHistoriqueDossiersData')
            ->with(1)
            ->willReturn($dossiersData);

        $service = new EtablissementHistoriqueService(
            $etablissementRepository,
            $dossierRepository,
            $logger
        );

        $result = $service->getLastAvisLibelle(1);

        $this->assertSame($expected, $result);
    }

    private function createDossierMock(string $avisLibelle, \DateTime $date): Dossier
    {
        $avis = new Avis();
        // Use reflection to set the private libelle property
        $reflectionClass = new \ReflectionClass($avis);
        $property = $reflectionClass->getProperty('libelle');
        $property->setValue($avis, $avisLibelle);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getAvisCommission')->willReturn($avis);
        $dossier->method('getDateCommission')->willReturn(null);
        $dossier->method('getDateVisite')->willReturn($date);
        $dossier->method('getCreateur')->willReturn(null);

        return $dossier;
    }

    public static function lastAvisLibelleProvider(): \Iterator
    {
        yield 'no dossiers' => [
            [],
            '',
        ];
        yield 'one favorable avis' => [
            [self::buildDossierData('Favorable', new \DateTime('2024-01-15'))],
            'Favorable',
        ];
        yield 'one defavorable avis' => [
            [self::buildDossierData('Défavorable', new \DateTime('2024-01-15'))],
            'Défavorable',
        ];
        yield 'multiple avis - last is favorable' => [
            [
                self::buildDossierData('Défavorable', new \DateTime('2024-01-15')),
                self::buildDossierData('Favorable', new \DateTime('2024-02-20')),
            ],
            'Favorable',
        ];
        yield 'multiple avis - last is defavorable' => [
            [
                self::buildDossierData('Favorable', new \DateTime('2024-01-15')),
                self::buildDossierData('Défavorable', new \DateTime('2024-02-20')),
            ],
            'Défavorable',
        ];
        yield 'same avis multiple times' => [
            [
                self::buildDossierData('Favorable', new \DateTime('2024-01-15')),
                self::buildDossierData('Favorable', new \DateTime('2024-02-20')),
            ],
            'Favorable',
        ];
    }

    private static function buildDossierData(string $avisLibelle, \DateTime $date): array
    {
        return [
            'avisLibelle' => $avisLibelle,
            'date' => $date,
        ];
    }
}
