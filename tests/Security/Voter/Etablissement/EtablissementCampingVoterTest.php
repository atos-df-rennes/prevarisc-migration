<?php

namespace App\Tests\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Entity\Privileges;
use App\Entity\Utilisateur;
use App\Entity\Groupe;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\EtablissementCampingVoter;
use App\Security\Voter\EtablissementVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementCampingVoterTest extends TestCase
{
    private $resourcesRepository;
    private $privilegesRepository;
    private $logger;
    private $voter;
    private $token;

    protected function setUp(): void
    {
        $this->resourcesRepository = $this->createMock(ResourcesRepository::class);
        $this->privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->token = $this->createMock(TokenInterface::class);

        $this->voter = new EtablissementCampingVoter(
            $this->resourcesRepository,
            $this->privilegesRepository,
            $this->logger
        );
    }

    private function createEtablissementMock(int $genreId): Etablissement
    {
        $genre = $this->createMock(Genre::class);
        $genre->method('getId')->willReturn($genreId);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getGenre')->willReturn($genre);

        $etablissement = $this->createMock(Etablissement::class);
        $etablissement->method('getLatestInformations')->willReturn($latestInformations);

        return $etablissement;
    }

    public function testAbstainIfParentDoesNotSupport(): void
    {
        $etablissement = $this->createMock(Etablissement::class);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $etablissement, ['unknown_attribute'])
        );
    }

    public function testDenyIfNoResourcesFound(): void
    {
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->with($this->equalTo(7))
            ->willReturn([]);

        $etablissement = $this->createEtablissementMock(7);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testDenyIfMultipleResourcesFound(): void
    {
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->with($this->equalTo(7))
            ->willReturn(['resource1', 'resource2']);

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with("Plusieurs règles trouvées pour les campings. Il est recommandé de supprimer les doublons.");

        $etablissement = $this->createEtablissementMock(7);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testDenyIfUserIsNotInstanceOfUtilisateur(): void
    {
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->with($this->equalTo(7))
            ->willReturn(['resource1']);

        $this->token->method('getUser')->willReturn(null);

        $etablissement = $this->createEtablissementMock(7);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testDenyIfUserGroupIsNull(): void
    {
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->with($this->equalTo(7))
            ->willReturn(['resource1']);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn(null);

        $this->token->method('getUser')->willReturn($user);

        $etablissement = $this->createEtablissementMock(7);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testGrantIfPrivilegeExistsInUserGroup(): void
    {
        $resource = 'resource1';
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissement')
            ->with($this->equalTo(7))
            ->willReturn([$resource]);

        $privilege = $this->createMock(Privileges::class);
        $this->privilegesRepository
            ->method('findOneBy')
            ->with(['name' => EtablissementVoter::LECTURE, 'resource' => $resource])
            ->willReturn($privilege);

        $userGroup = $this->createMock(Groupe::class);
        $userGroup->method('getPrivileges')->willReturn(new ArrayCollection([$privilege]));

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($userGroup);

        $this->token->method('getUser')->willReturn($user);

        $etablissement = $this->createEtablissementMock(7);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }
}
