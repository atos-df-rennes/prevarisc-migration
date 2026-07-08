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
use App\Exception\FusionTechnicalException;
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

    /**
     * V-03 : lorsque tous les lots réussissent, le résultat indique un succès
     * global sans aucun avertissement.
     */
    public function testFusionRetourneUnSuccesGlobal(): void
    {
        $nouvelleCommune = new Commune();
        $nouvelleCommune->setLibelle('Nouvelle Commune');
        $nouvelleCommune->setCodePostal('00000');

        $ancienneCommune = new Commune();
        $ancienneCommune->setLibelle('Ancienne Commune');
        $ancienneCommune->setCodePostal('00000');
        $ancienneCommune->setUtilisateurInformations(new UtilisateurInformations());

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
        $this->utilisateurRepository->method('findOneBy')->willReturn(new Utilisateur());
        $this->commissionRegleRepository->method('findBy')->willReturn([]);

        $resultat = $this->buildService()->fusionner('{}');

        self::assertTrue($resultat->hasSuccess());
        self::assertFalse($resultat->hasFailures());
        self::assertSame(['Nouvelle Commune'], $resultat->succes());
    }

    /**
     * V-03 : l'échec fonctionnel d'un lot (commune cible introuvable) ne lève pas
     * d'exception mais est enregistré comme un échec dans le résultat.
     */
    public function testLotIntrouvableEstUnEchecFonctionnel(): void
    {
        $fusion = new FusionDeCommunes('Commune Fantôme', 'GHOST', [
            new CommuneDto('Ancienne Commune', 'OLD01'),
        ]);

        $this->serializer->method('deserialize')->willReturn([$fusion]);
        $this->communeRepository->method('find')->willReturn(null);

        $resultat = $this->buildService()->fusionner('{}');

        self::assertFalse($resultat->hasSuccess());
        self::assertTrue($resultat->hasFailures());
        self::assertCount(1, $resultat->echecs());
        self::assertSame('Commune Fantôme', $resultat->echecs()[0]['nom']);
    }

    /**
     * V-03 : lorsqu'un lot échoue et qu'un autre réussit, le traitement continue
     * et le résultat combine succès et avertissement (succès partiel).
     */
    public function testFusionPartielleContinueApresUnEchec(): void
    {
        $nouvelleCommune = new Commune();
        $nouvelleCommune->setLibelle('Nouvelle Commune');
        $nouvelleCommune->setCodePostal('00000');

        $ancienneCommune = new Commune();
        $ancienneCommune->setLibelle('Ancienne Commune');
        $ancienneCommune->setCodePostal('00000');
        $ancienneCommune->setUtilisateurInformations(new UtilisateurInformations());

        $fusionKo = new FusionDeCommunes('Commune Fantôme', 'GHOST', [
            new CommuneDto('Ancienne KO', 'OLDKO'),
        ]);
        $fusionOk = new FusionDeCommunes('Nouvelle Commune', 'NEW01', [
            new CommuneDto('Ancienne Commune', 'OLD01'),
        ]);

        $this->serializer->method('deserialize')->willReturn([$fusionKo, $fusionOk]);
        $this->communeRepository->method('find')->willReturnMap([
            ['GHOST', null, null, null],
            ['NEW01', null, null, $nouvelleCommune],
            ['OLD01', null, null, $ancienneCommune],
        ]);
        $this->etablissementAdresseRepository->method('findBy')->willReturn([]);
        $this->adresseRueRepository->method('findBy')->willReturn([]);
        $this->utilisateurRepository->method('findOneBy')->willReturn(new Utilisateur());
        $this->commissionRegleRepository->method('findBy')->willReturn([]);

        $resultat = $this->buildService()->fusionner('{}');

        self::assertTrue($resultat->hasSuccess());
        self::assertTrue($resultat->hasFailures());
        self::assertSame(['Nouvelle Commune'], $resultat->succes());
        self::assertSame('Commune Fantôme', $resultat->echecs()[0]['nom']);
    }

    /**
     * V-03 : un contenu de fichier illisible constitue une erreur technique qui
     * interrompt tout le traitement.
     */
    public function testFichierIllisibleLeveUneExceptionTechnique(): void
    {
        $this->expectException(FusionTechnicalException::class);

        $this->buildService()->fusionner(false);
    }

    /**
     * V-03 : un JSON invalide constitue une erreur technique qui interrompt tout
     * le traitement.
     */
    public function testJsonInvalideLeveUneExceptionTechnique(): void
    {
        $this->serializer->method('deserialize')->willThrowException(new \RuntimeException('JSON invalide'));

        $this->expectException(FusionTechnicalException::class);

        $this->buildService()->fusionner('{invalide');
    }
}
