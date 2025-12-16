<?php

namespace App\Tests\Service;

use App\Entity\Avis;
use App\Entity\Dossier;
use App\Repository\AvisDerogationsRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementPieceJointeRepository;
use App\Repository\EtablissementRepository;
use App\Service\DataStore\DataStore;
use App\Service\Etablissement;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class EtablissementTest extends TestCase
{
    /** @dataProvider lastAvisLibelleProvider */
    public function testGetLastAvisLibelle(array $dossiersDataParams, string $expected): void
    {
        $etablissementRepository = $this->createMock(EtablissementRepository::class);
        $dossierRepository = $this->createMock(DossierRepository::class);
        $avisDerogationsRepository = $this->createMock(AvisDerogationsRepository::class);
        $etablissementPJRepository = $this->createMock(EtablissementPieceJointeRepository::class);
        $dataStore = $this->createMock(DataStore::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Construire les mocks de dossiers à partir des paramètres
        $dossiersData = [];
        foreach ($dossiersDataParams as $params) {
            $dossiersData[] = $this->createDossierMock($params['avisLibelle'], $params['date']);
        }

        $dossierRepository->expects($this->once())
            ->method('getHistoriqueDossiersData')
            ->with(1)
            ->willReturn($dossiersData);

        $service = new Etablissement(
            $etablissementRepository,
            $dossierRepository,
            $avisDerogationsRepository,
            $etablissementPJRepository,
            $dataStore,
            $parameterBag,
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
        $property->setAccessible(true);
        $property->setValue($avis, $avisLibelle);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getAvisCommission')->willReturn($avis);
        $dossier->method('getDateCommission')->willReturn(null);
        $dossier->method('getDateVisite')->willReturn($date);
        $dossier->method('getCreateur')->willReturn(null);

        return $dossier;
    }

    public static function lastAvisLibelleProvider(): array
    {
        return [
            'no dossiers' => [
                [],
                '',
            ],
            'one favorable avis' => [
                [self::buildDossierData('Favorable', new \DateTime('2024-01-15'))],
                'Favorable',
            ],
            'one defavorable avis' => [
                [self::buildDossierData('Défavorable', new \DateTime('2024-01-15'))],
                'Défavorable',
            ],
            'multiple avis - last is favorable' => [
                [
                    self::buildDossierData('Défavorable', new \DateTime('2024-01-15')),
                    self::buildDossierData('Favorable', new \DateTime('2024-02-20')),
                ],
                'Favorable',
            ],
            'multiple avis - last is defavorable' => [
                [
                    self::buildDossierData('Favorable', new \DateTime('2024-01-15')),
                    self::buildDossierData('Défavorable', new \DateTime('2024-02-20')),
                ],
                'Défavorable',
            ],
            'same avis multiple times' => [
                [
                    self::buildDossierData('Favorable', new \DateTime('2024-01-15')),
                    self::buildDossierData('Favorable', new \DateTime('2024-02-20')),
                ],
                'Favorable',
            ],
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
