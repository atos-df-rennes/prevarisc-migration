<?php

namespace App\Tests\Service;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Repository\AvisDerogationsRepository;
use App\Repository\DossierRepository;
use App\Repository\EtablissementRepository;
use App\Service\Etablissement as EtablissementService;
use App\Service\TexteApplicable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Tests unitaires pour EtablissementService::save().
 *
 * Ces tests vérifient la logique de validation des genres (fils/père)
 * sans nécessiter de base de données.
 *
 * @covers \App\Service\Etablissement::save
 */
class EtablissementSaveTest extends TestCase
{
    /**
     * Crée le service avec des mocks minimaux pour tester la logique de validation.
     *
     * @param Etablissement|null $filsEtablissement Établissement fils retourné par le repo (id=10)
     * @param Etablissement|null $pereEtablissement Établissement père retourné par le repo (id=99)
     */
    private function buildService(
        ?Etablissement $filsEtablissement = null,
        ?Etablissement $pereEtablissement = null
    ): EtablissementService {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('rollBack');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        // On simule la persistance en affectant l'ID=1 via réflexion
        $em->method('persist')->willReturnCallback(static function ($entity): void {
            if ($entity instanceof Etablissement) {
                $r = new \ReflectionClass($entity);
                $p = $r->getProperty('id');
                $p->setAccessible(true);
                $p->setValue($entity, 1);
            }
        });
        $em->method('flush');
        $em->method('getReference')->willReturnCallback(function (string $class, $id): MockObject {
            return $this->createMock($class);
        });

        $etablissementRepo = $this->createMock(EtablissementRepository::class);
        $etablissementRepo->method('find')->willReturnCallback(
            function ($id) use ($filsEtablissement, $pereEtablissement): ?Etablissement {
                if (null !== $filsEtablissement && 10 === (int) $id) {
                    return $filsEtablissement;
                }

                if (null !== $pereEtablissement && 99 === (int) $id) {
                    return $pereEtablissement;
                }

                return null;
            }
        );

        $logger = $this->createMock(LoggerInterface::class);
        $dossierRepo = $this->createMock(DossierRepository::class);
        $avisDerogationsRepo = $this->createMock(AvisDerogationsRepository::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $texteApplicable = $this->createMock(TexteApplicable::class);

        return new EtablissementService(
            $etablissementRepo,
            $dossierRepo,
            $avisDerogationsRepo,
            $parameterBag,
            $em,
            $logger,
            $texteApplicable
        );
    }

    /**
     * Crée un mock d'établissement avec un genre donné.
     */
    private function createEtablissementWithGenre(int $genreId): Etablissement
    {
        $genre = $this->createMock(Genre::class);
        $genre->method('getId')->willReturn($genreId);

        $informations = $this->createMock(EtablissementInformations::class);
        $informations->method('getGenre')->willReturn($genre);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($informations);
        $etablissement->method('getId')->willReturn(10);

        return $etablissement;
    }

    /**
     * Ajouter un établissement non-cellule comme fils d'un ERP doit lever une exception.
     */
    public function testSaveErpWithNonCelluleFilsThrowsException(): void
    {
        $filsZone = $this->createEtablissementWithGenre(GenreEtablissement::ZONE);
        $service = $this->buildService($filsZone);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut contenir que des cellules');

        $service->save(GenreEtablissement::ERP, [
            'ID_FILS_ETABLISSEMENT' => ['10'],
        ]);
    }

    /**
     * Un site ne peut pas avoir de père — lever une exception.
     */
    public function testSaveSiteWithPereThrowsException(): void
    {
        $pere = $this->createEtablissementWithGenre(GenreEtablissement::SITE);
        $service = $this->buildService(null, $pere);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas avoir de père');

        $service->save(GenreEtablissement::SITE, [
            'ID_PERE' => '99',
        ]);
    }

    /**
     * Une cellule avec un père qui n'est pas ERP/IGH doit lever une exception.
     */
    public function testSaveCelluleWithNonErpPereThrowsException(): void
    {
        $pere = $this->createEtablissementWithGenre(GenreEtablissement::HABITATION);
        $service = $this->buildService(null, $pere);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ERP ou un IGH');

        $service->save(GenreEtablissement::CELLULE, [
            'ID_PERE' => '99',
        ]);
    }

    /**
     * Un fils de type site ne peut pas être rattaché à un parent — exception.
     */
    public function testSaveSiteAsFilsThrowsException(): void
    {
        $fils = $this->createEtablissementWithGenre(GenreEtablissement::SITE);
        $service = $this->buildService($fils);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("n'est pas compatible avec un site");

        $service->save(GenreEtablissement::SITE, [
            'ID_FILS_ETABLISSEMENT' => ['10'],
        ]);
    }
}
