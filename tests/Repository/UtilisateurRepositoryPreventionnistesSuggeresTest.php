<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Commune;
use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\EtablissementStatut;
use App\Entity\Fonction;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Groupement;
use App\Entity\GroupementType;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurInformations;
use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('functional')]
final class UtilisateurRepositoryPreventionnistesSuggeresTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private Connection $connection;

    private UtilisateurRepository $repository;

    private Fonction $fonction;

    private Groupe $groupe;

    private Genre $genre;

    private EtablissementStatut $statut;

    private Commune $commune;

    private int $userNumber = 0;

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        $this->connection = $this->entityManager->getConnection();
        $this->connection->beginTransaction();

        $this->repository = self::getContainer()->get(UtilisateurRepository::class);

        $genre = $this->entityManager->getRepository(Genre::class)->findOneBy([]);
        $statut = $this->entityManager->getRepository(EtablissementStatut::class)->findOneBy([]);
        if (null === $genre || null === $statut) {
            self::markTestSkipped('Les données de référence nécessaires sont absentes.');
        }

        $this->genre = $genre;
        $this->statut = $statut;
        do {
            $numinsee = (string) random_int(10000, 99999);
            $existingCommune = $this->connection->executeQuery(
                'SELECT COUNT(*) FROM adressecommune WHERE NUMINSEE_COMMUNE = ?',
                [$numinsee]
            )->fetchOne();
            if (!is_numeric($existingCommune)) {
                throw new \UnexpectedValueException('Impossible de vérifier le code INSEE de test.');
            }
        } while (0 < (int) $existingCommune);

        $this->connection->insert('adressecommune', [
            'NUMINSEE_COMMUNE' => $numinsee,
            'LIBELLE_COMMUNE' => 'Commune test preventionnistes',
            'CODEPOSTAL_COMMUNE' => '99999',
        ]);
        $commune = $this->entityManager->getReference(Commune::class, $numinsee);
        if (!$commune instanceof Commune) {
            throw new \UnexpectedValueException('La commune de test n\'a pas pu être chargée.');
        }

        $this->commune = $commune;

        $this->fonction = new Fonction()->setLibelle('Test preventionniste '.$this->uniqueSuffix());
        $this->groupe = new Groupe()->setLibelle('Test groupe '.$this->uniqueSuffix());
        $this->entityManager->persist($this->fonction);
        $this->entityManager->persist($this->groupe);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        if (null !== $this->connection && $this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        if (null !== $this->entityManager && $this->entityManager->isOpen()) {
            $this->entityManager->clear();
        }

        parent::tearDown();
    }

    public function testFindPreventionnistesSuggeresPourEtablissementReturnsOnlyLatestVersionWithoutDuplicates(): void
    {
        $shared = $this->createUser('Partagé', true);
        $historiqueOnly = $this->createUser('Historique', true);
        $latestOnly = $this->createUser('Dernier', true);
        $etablissement = $this->createEtablissement();
        $this->createInformations($etablissement, '2024-01-01', [$shared, $historiqueOnly]);
        $this->createInformations($etablissement, '2024-02-01', [$shared, $latestOnly]);
        $this->entityManager->flush();

        $results = $this->repository->findPreventionnistesSuggeresPourEtablissement($etablissement->getId());

        $this->assertSame([$latestOnly->getId(), $shared->getId()], $this->resultIds($results));
    }

    public function testFindPreventionnistesSuggeresPourEtablissementExcludesInactiveUsers(): void
    {
        $actif = $this->createUser('Actif', true);
        $inactif = $this->createUser('Inactif', false);
        $etablissement = $this->createEtablissement();
        $this->createInformations($etablissement, '2024-02-01', [$actif, $inactif]);
        $this->entityManager->flush();

        $results = $this->repository->findPreventionnistesSuggeresPourEtablissement($etablissement->getId());

        $this->assertSame([$actif->getId()], $this->resultIds($results));
    }

    public function testFindPreventionnistesSuggeresPourCommuneDeduplicatesAndUsesLegacyOrder(): void
    {
        $typePrioritaire = new GroupementType()->setLibelle('Type A '.$this->uniqueSuffix());
        $typeSecondaire = new GroupementType()->setLibelle('Type B '.$this->uniqueSuffix());
        $this->entityManager->persist($typePrioritaire);
        $this->entityManager->persist($typeSecondaire);
        $this->entityManager->flush();

        $shared = $this->createUser('Milieu', true);
        $premier = $this->createUser('Alpha', true);
        $dernier = $this->createUser('Zulu', true);
        $typeTwo = $this->createUser('TypeDeux', true);

        $this->createGroupement('Groupe B '.$this->uniqueSuffix(), $typePrioritaire, [$shared, $dernier]);
        $this->createGroupement('Groupe A '.$this->uniqueSuffix(), $typePrioritaire, [$premier]);
        $this->createGroupement('Groupe A '.$this->uniqueSuffix(), $typeSecondaire, [$shared, $typeTwo]);
        $this->entityManager->flush();

        $results = $this->repository->findPreventionnistesSuggeresPourCommune($this->commune->getId());

        $this->assertSame([$premier->getId(), $shared->getId(), $dernier->getId(), $typeTwo->getId()], $this->resultIds($results));
    }

    public function testFindPreventionnistesSuggeresPourCommuneKeepsInactiveUsers(): void
    {
        $inactif = $this->createUser('Inactif commune', false);
        $type = new GroupementType()->setLibelle('Type commune '.$this->uniqueSuffix());
        $this->entityManager->persist($type);
        $this->entityManager->flush();
        $this->createGroupement('Groupe commune '.$this->uniqueSuffix(), $type, [$inactif]);
        $this->entityManager->flush();

        $results = $this->repository->findPreventionnistesSuggeresPourCommune($this->commune->getId());

        $this->assertSame([$inactif->getId()], $this->resultIds($results));
    }

    /**
     * @param Utilisateur[] $preventionnistes
     */
    private function createInformations(Etablissement $etablissement, string $date, array $preventionnistes): EtablissementInformations
    {
        $informations = new EtablissementInformations()
            ->setLibelle('Test établissement')
            ->setDate(new \DateTime($date))
            ->setEtablissement($etablissement)
            ->setGenre($this->genre)
            ->setStatut($this->statut);

        foreach ($preventionnistes as $preventionniste) {
            $informations->addPreventionniste($preventionniste);
        }

        $this->entityManager->persist($informations);

        return $informations;
    }

    private function createEtablissement(): Etablissement
    {
        $etablissement = new Etablissement();
        $this->entityManager->persist($etablissement);

        return $etablissement;
    }

    private function createUser(string $nom, bool $actif): Utilisateur
    {
        $informations = new UtilisateurInformations()
            ->setNom($nom)
            ->setPrenom('Test')
            ->setFonction($this->fonction);
        $utilisateur = new Utilisateur()
            ->setUsername('suggestion-test-'.$this->userNumber++)
            ->setActif($actif)
            ->setGroupe($this->groupe)
            ->setInformations($informations);
        $this->entityManager->persist($informations);
        $this->entityManager->persist($utilisateur);

        return $utilisateur;
    }

    /**
     * @param Utilisateur[] $preventionnistes
     */
    private function createGroupement(string $libelle, GroupementType $type, array $preventionnistes): void
    {
        $groupement = new Groupement()
            ->setLibelle($libelle)
            ->setType($type)
            ->addCommune($this->commune);

        foreach ($preventionnistes as $preventionniste) {
            $groupement->addPreventionniste($preventionniste);
        }

        $this->entityManager->persist($groupement);
    }

    /**
     * @param array<int, array{uid: mixed, nom: mixed, prenom: mixed}> $results
     *
     * @return int[]
     */
    private function resultIds(array $results): array
    {
        $ids = [];
        foreach ($results as $result) {
            if (!is_numeric($result['uid'])) {
                throw new \UnexpectedValueException('Identifiant utilisateur invalide dans le résultat.');
            }

            $ids[] = (int) $result['uid'];
        }

        return $ids;
    }

    private function uniqueSuffix(): string
    {
        return uniqid('', true);
    }
}
