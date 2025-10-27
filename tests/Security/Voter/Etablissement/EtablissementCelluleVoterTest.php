<?php

namespace App\Tests\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\EtablissementActivite;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\EtablissementCelluleVoter;
use App\Security\Voter\EtablissementVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementCelluleVoterTest extends TestCase
{
    private $resourcesRepository;

    private $privilegesRepository;

    private $voter;

    protected function setUp(): void
    {
        $this->resourcesRepository = $this->createMock(ResourcesRepository::class);
        $this->privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $this->voter = new EtablissementCelluleVoter($this->resourcesRepository, $this->privilegesRepository);
    }

    private function createEtablissementMock(int $genreId, ?int $typeActiviteId = null): Etablissement
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => $genreId]);
        $latestInformations = $this->createConfiguredMock(EtablissementInformations::class, ['getGenre' => $genre]);

        if (null !== $typeActiviteId) {
            $typeActivite = $this->createConfiguredMock(EtablissementActivite::class, ['getId' => $typeActiviteId]);

            $latestInformations->method('getTypeActivite')->willReturn($typeActivite);
        }

        return $this->createConfiguredMock(Etablissement::class, ['getLatestInformations' => $latestInformations]);
    }

    public function testAbstainIfParentDoesNotSupport(): void
    {
        $etablissement = $this->createMock(Etablissement::class);
        $token = $this->createMock(TokenInterface::class);
        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($token, $etablissement, ['unknown_attribute'])
        );
    }

    public function testDenyIfNoResourcesFound(): void
    {
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->with(3)
            ->willReturn([]);
        $token = $this->createMock(TokenInterface::class);
        $etablissement = $this->createEtablissementMock(3);
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testDenyIfUserIsNotUtilisateur(): void
    {
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->willReturn([$this->createMock(Resources::class)]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $etablissement = $this->createEtablissementMock(3);
        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testGrantIfPrivilegeAllExists(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_cell_0_0');
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->willReturn([$resource]);
        $privilege = new Privileges();
        $userGroup = $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $user = $this->createConfiguredMock(Utilisateur::class, ['getGroupe' => $userGroup]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissement = $this->createEtablissementMock(3);
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
        $resource = $this->createMock(Resources::class);
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->willReturn([$resource]);
        $userGroup = $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection()]);
        $user = $this->createConfiguredMock(Utilisateur::class, ['getGroupe' => $userGroup]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissement = $this->createEtablissementMock(3);
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
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_cell_1_0');
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->willReturn([$resource]);
        $privilege = new Privileges();
        $userGroup = $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $user = $this->createConfiguredMock(Utilisateur::class, ['getGroupe' => $userGroup]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissement = $this->createEtablissementMock(3, 1);
        // On simule le classement de la zone à 4
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
        $resourceSingle = $this->createMock(Resources::class);
        $resourceSingle->method('getName')->willReturn('etablissement_cell_1_0');
        $resourceMulti = $this->createMock(Resources::class);
        $resourceMulti->method('getName')->willReturn('etablissement_cell_1-2_0');
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->willReturn([$resourceSingle, $resourceMulti]);
        $privilege = new Privileges();
        $userGroup = $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $user = $this->createConfiguredMock(Utilisateur::class, ['getGroupe' => $userGroup]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $etablissement = $this->createEtablissementMock(3, 1);
        // On simule le type d'activité de la cellule à 1
        $this->privilegesRepository
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($privilege, $resourceSingle, $resourceMulti): ?Privileges {
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
}
