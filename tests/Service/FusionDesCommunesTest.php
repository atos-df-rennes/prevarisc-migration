<?php

namespace App\Tests\Service;

use App\Dto\FusionDesCommunes\Commune as CommuneDto;
use App\Dto\FusionDesCommunes\FusionDeCommunes;
use App\Entity\Commission;
use App\Entity\CommissionRegle;
use App\Entity\Commune;
use App\Entity\Groupement;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurInformations;
use App\Repository\AdresseRueRepository;
use App\Repository\CommissionRegleRepository;
use App\Repository\CommuneRepository;
use App\Repository\EtablissementAdresseRepository;
use App\Repository\UtilisateurRepository;
use App\Service\FusionDesCommunes;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class FusionDesCommunesTest extends TestCase
{
    /** @var SerializerInterface|MockObject */
    private $serializer;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var EntityManagerInterface|MockObject */
    private $entityManager;

    /** @var CommuneRepository|MockObject */
    private $communeRepository;

    /** @var EtablissementAdresseRepository|MockObject */
    private $etablissementAdresseRepository;

    /** @var AdresseRueRepository|MockObject */
    private $adresseRueRepository;

    /** @var UtilisateurRepository|MockObject */
    private $utilisateurRepository;

    /** @var CommissionRegleRepository|MockObject */
    private $commissionRegleRepository;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->communeRepository = $this->createMock(CommuneRepository::class);
        $this->etablissementAdresseRepository = $this->createMock(EtablissementAdresseRepository::class);
        $this->adresseRueRepository = $this->createMock(AdresseRueRepository::class);
        $this->utilisateurRepository = $this->createMock(UtilisateurRepository::class);
        $this->commissionRegleRepository = $this->createMock(CommissionRegleRepository::class);
    }

    private function buildService(): FusionDesCommunes
    {
        return new FusionDesCommunes(
            $this->serializer,
            $this->logger,
            $this->entityManager,
            $this->communeRepository,
            $this->etablissementAdresseRepository,
            $this->adresseRueRepository,
            $this->utilisateurRepository,
            $this->commissionRegleRepository
        );
    }

    /**
     * E-01 : lors d'une fusion, les liaisons groupement-commune des communes
     * absorbées doivent être supprimées.
     */
    public function testFusionSupprimeLesLiaisonsGroupementCommune(): void
    {
        $nouvelleCommune = new Commune();
        $nouvelleCommune->setLibelle('Nouvelle Commune');
        $nouvelleCommune->setCodePostal('00000');

        $ancienneCommune = new Commune();
        $ancienneCommune->setLibelle('Ancienne Commune');
        $ancienneCommune->setCodePostal('00000');

        $informations = new UtilisateurInformations();
        $ancienneCommune->setUtilisateurInformations($informations);

        $utilisateur = new Utilisateur();

        $groupement = new Groupement();
        $groupement->addCommune($ancienneCommune);

        $ancienneCommune->addGroupement($groupement);

        self::assertTrue($groupement->getCommunes()->contains($ancienneCommune));

        $fusion = new FusionDeCommunes('Nouvelle Commune', 'NEW01', [
            new CommuneDto('Ancienne Commune', 'OLD01'),
        ]);

        $this->serializer->method('deserialize')->willReturn([$fusion]);

        $this->communeRepository->method('find')->willReturnMap([
            ['NEW01', null, null, $nouvelleCommune],
            ['OLD01', null, null, $ancienneCommune],
        ]);

        $this->etablissementAdresseRepository->method('findBy')->willReturn([]);
        $this->adresseRueRepository->method('findBy')->willReturn([]);
        $this->utilisateurRepository->method('findOneBy')->willReturn($utilisateur);
        $this->commissionRegleRepository->method('findBy')->willReturn([]);

        $this->buildService()->fusionner('{}');

        self::assertFalse($groupement->getCommunes()->contains($ancienneCommune));
    }

    /**
     * E-02 : lors d'une fusion, les règles de commission liées aux communes
     * absorbées doivent être supprimées.
     */
    public function testFusionSupprimeLesReglesDeCommission(): void
    {
        $nouvelleCommune = new Commune();
        $nouvelleCommune->setLibelle('Nouvelle Commune');
        $nouvelleCommune->setCodePostal('00000');

        $ancienneCommune = new Commune();
        $ancienneCommune->setLibelle('Ancienne Commune');
        $ancienneCommune->setCodePostal('00000');

        $informations = new UtilisateurInformations();
        $ancienneCommune->setUtilisateurInformations($informations);

        $utilisateur = new Utilisateur();

        $regle = new CommissionRegle();
        $regle->setCommune($ancienneCommune);
        $regle->setCommission(new Commission());

        $fusion = new FusionDeCommunes('Nouvelle Commune', 'NEW01', [
            new CommuneDto('Ancienne Commune', 'OLD01'),
        ]);

        $this->serializer->method('deserialize')->willReturn([$fusion]);

        $this->communeRepository->method('find')->willReturnMap([
            ['NEW01', null, null, $nouvelleCommune],
            ['OLD01', null, null, $ancienneCommune],
        ]);

        $this->etablissementAdresseRepository->method('findBy')->willReturn([]);
        $this->adresseRueRepository->method('findBy')->willReturn([]);
        $this->utilisateurRepository->method('findOneBy')->willReturn($utilisateur);
        $this->commissionRegleRepository->method('findBy')->willReturnMap([
            [['commune' => $ancienneCommune], null, null, null, [$regle]],
        ]);

        $removedEntities = [];
        $this->entityManager->method('remove')->willReturnCallback(static function ($entity) use (&$removedEntities): void {
            $removedEntities[] = $entity;
        });

        $this->buildService()->fusionner('{}');

        self::assertContains($regle, $removedEntities);
    }
}
