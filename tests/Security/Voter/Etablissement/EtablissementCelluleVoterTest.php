<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\Etablissement\EtablissementCelluleVoter;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\Voter\VoterUtils;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[AllowMockObjectsWithoutExpectations]
final class EtablissementCelluleVoterTest extends TestCase
{
    private ResourcesRepository $resourcesRepository;

    private PrivilegesRepository $privilegesRepository;

    private EtablissementCelluleVoter $voter;

    protected function setUp(): void
    {
        $this->resourcesRepository = $this->createStub(ResourcesRepository::class);
        $this->privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $this->voter = new EtablissementCelluleVoter($this->resourcesRepository, $this->privilegesRepository, new VoterUtils());
    }

    private function createEtablissementMock(?EtablissementActivite $typeActivite = null): Etablissement
    {
        $genre = $this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::CELLULE]);
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, ['getGenre' => $genre]);
        $latestInformations->method('getTypeActivite')->willReturn($typeActivite);

        return $this->createConfiguredStub(Etablissement::class, ['getLatestInformations' => $latestInformations]);
    }

    public function testAbstainIfParentDoesNotSupport(): void
    {
        $etablissement = $this->createStub(Etablissement::class);
        $token = $this->createStub(TokenInterface::class);
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $etablissement, ['unknown_attribute'])
        );
    }

    public function testDenyIfNoResourcesFound(): void
    {
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementCellule')
            ->willReturn([]);
        $token = $this->createStub(TokenInterface::class);
        $etablissement = $this->createEtablissementMock();
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testDenyIfUserIsNotUtilisateur(): void
    {
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementCellule')
            ->willReturn([$this->createStub(Resources::class)]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $etablissement = $this->createEtablissementMock();
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testGrantIfPrivilegeAllExists(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_cell_0_0']);
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementCellule')
            ->willReturn([$resource]);
        $privilege = new Privileges();
        $userGroup = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $userGroup]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissement = $this->createEtablissementMock();
        $this->privilegesRepository
            ->method('findOneBy')
            ->willReturn($privilege);
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testDenyIfNoMatchingPrivileges(): void
    {
        $resource = $this->createStub(Resources::class);
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementCellule')
            ->willReturn([$resource]);
        $userGroup = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => new ArrayCollection()]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $userGroup]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissement = $this->createEtablissementMock();
        $this->privilegesRepository
            ->method('findOneBy')
            ->willReturn(null);
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testGrantIfPrivilegeSpecificClassementExists(): void
    {
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_cell_1_0']);
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementCellule')
            ->willReturn([$resource]);
        $privilege = new Privileges();
        $userGroup = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $userGroup]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissement = $this->createEtablissementMock($this->createConfiguredStub(EtablissementActivite::class, ['getId' => 1]));
        // On simule le type d'activité de la cellule à 1
        $this->privilegesRepository
            ->method('findOneBy')
            ->willReturn($privilege);
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testGrantIfPrivilegeOnAnyMatchingClassementRule(): void
    {
        $resourceSingle = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_cell_1_0']);
        $resourceMulti = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_cell_1-2_0']);
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementCellule')
            ->willReturn([$resourceSingle, $resourceMulti]);
        $privilege = new Privileges();
        $userGroup = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $userGroup]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissement = $this->createEtablissementMock($this->createConfiguredStub(EtablissementActivite::class, ['getId' => 1]));
        // On simule le type d'activité de la cellule à 1
        $this->privilegesRepository
            ->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($privilege, $resourceSingle, $resourceMulti): ?Privileges {
                // On accorde le privilège si la ressource est l'une des deux
                if ($criteria['resource'] === $resourceSingle || $criteria['resource'] === $resourceMulti) {
                    return $privilege;
                }

                return null;
            });
        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    #[DataProvider('categorieEffectiveProvider')]
    public function testDroitsSelonCategorieDuParent(string $resourceName, ?int $categorieParentId, int $expectedVote): void
    {
        $this->resourcesRepository = $this->createMock(ResourcesRepository::class);
        $this->voter = new EtablissementCelluleVoter($this->resourcesRepository, $this->privilegesRepository, new VoterUtils());
        $categorieParent = null !== $categorieParentId ? $this->createConfiguredStub(EtablissementCategorie::class, ['getId' => $categorieParentId]) : null;
        $parent = new Etablissement();
        $parentInformations = new EtablissementInformations();
        $parentInformations->setCategorie($categorieParent);

        $parent->addInformations($parentInformations);

        $etablissement = new Etablissement();
        $informations = new EtablissementInformations();
        $informations->setGenre($this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::CELLULE]));
        $informations->setTypeActivite($this->createConfiguredStub(EtablissementActivite::class, ['getId' => 1]));
        $informations->setCategorie($this->createConfiguredStub(EtablissementCategorie::class, ['getId' => 2]));

        $etablissement->addInformations($informations);
        $etablissement->addParent($parent);

        $resource = new Resources()->setName($resourceName);
        $this->resourcesRepository->expects($this->once())
            ->method('findRessourcesSpecialiseesEtablissementCellule')
            ->with($informations->getTypeActivite(), $categorieParent)
            ->willReturn([$resource]);
        $privilege = new Privileges();
        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);
        $groupe = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $groupe]);
        $token = $this->createConfiguredStub(TokenInterface::class, ['getUser' => $user]);

        $this->assertSame($expectedVote, $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE]));
    }

    public static function categorieEffectiveProvider(): \Iterator
    {
        yield 'categorie du parent' => ['etablissement_cell_1_5', 5, VoterInterface::ACCESS_GRANTED];
        yield 'categorie propre ignoree' => ['etablissement_cell_1_2', 5, VoterInterface::ACCESS_DENIED];
        yield 'liste de categories' => ['etablissement_cell_1-3_2-5', 5, VoterInterface::ACCESS_GRANTED];
        yield 'toutes categories' => ['etablissement_cell_1_0', 5, VoterInterface::ACCESS_GRANTED];
        yield 'toutes activites' => ['etablissement_cell_0_5', 5, VoterInterface::ACCESS_GRANTED];
        yield 'sans categorie et droits globaux' => ['etablissement_cell_0_0', null, VoterInterface::ACCESS_GRANTED];
        yield 'zero dans les listes sans categorie' => ['etablissement_cell_0-3_0-5', null, VoterInterface::ACCESS_GRANTED];
        yield 'sans categorie et droits specialises' => ['etablissement_cell_1_2', null, VoterInterface::ACCESS_DENIED];
        yield 'identifiant exact de categorie' => ['etablissement_cell_1_15', 5, VoterInterface::ACCESS_DENIED];
        yield 'identifiant exact de type' => ['etablissement_cell_11_5', 5, VoterInterface::ACCESS_DENIED];
    }
}
