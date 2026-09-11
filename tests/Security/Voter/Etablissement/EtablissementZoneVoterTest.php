<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Classement;
use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Security\Voter\Etablissement\EtablissementZoneVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EtablissementZoneVoterTest extends TestCase
{
    private ResourcesRepository $resourcesRepository;

    private PrivilegesRepository $privilegesRepository;

    private EtablissementZoneVoter $voter;

    protected function setUp(): void
    {
        $this->resourcesRepository = $this->createStub(ResourcesRepository::class);
        $this->privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $this->voter = new EtablissementZoneVoter($this->resourcesRepository, $this->privilegesRepository);
    }

    private function createEtablissementMock(?Classement $classement = null): Etablissement
    {
        $genre = $this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::ZONE]);
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, ['getGenre' => $genre]);
        $latestInformations->method('getClassement')->willReturn($classement);

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
            ->method('findRessourcesSpecialiseesEtablissementZone')
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
            ->method('findRessourcesSpecialiseesEtablissementZone')
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
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_zone_0_0_0']);
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementZone')
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
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_zone_0_0_0']);
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementZone')
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
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_zone_4_0_0']);
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementZone')
            ->willReturn([$resource]);
        $privilege = new Privileges();
        $userGroup = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $userGroup]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $classement = $this->createConfiguredStub(Classement::class, ['getId' => 4]);
        $etablissement = $this->createEtablissementMock($classement);
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
        $resourceSingle = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_zone_4_0_0']);
        $resourceMulti = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_zone_4-5_0_0']);
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementZone')
            ->willReturn([$resourceSingle, $resourceMulti]);
        $privilege = new Privileges();
        $userGroup = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]);
        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $userGroup]);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $classement = $this->createConfiguredStub(Classement::class, ['getId' => 4]);
        $etablissement = $this->createEtablissementMock($classement);
        // On simule le classement de la zone à 4
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
}
