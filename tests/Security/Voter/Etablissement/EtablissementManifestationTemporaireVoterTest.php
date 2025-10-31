<?php

namespace App\Tests\Security\Voter;

use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\EtablissementManifestationTemporaireVoter;
use App\Security\Voter\EtablissementVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementManifestationTemporaireVoterTest extends TestCase
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

        $this->voter = new EtablissementManifestationTemporaireVoter(
            $this->resourcesRepository,
            $this->privilegesRepository,
            $this->logger
        );
    }

    private function createEtablissementMock(): Etablissement
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => EtablissementManifestationTemporaireVoter::ID_MANIFESTATION_TEMPORAIRE]);
        $latestInformations = $this->createConfiguredMock(EtablissementInformations::class, ['getGenre' => $genre]);

        return $this->createConfiguredMock(Etablissement::class, ['getLatestInformations' => $latestInformations]);
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
            ->method('findRessourcesSpecialiseesEtablissementManifestationTemporaire')
            ->willThrowException(new NoResultException());

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->equalTo("Aucune règle trouvée pour les manifestations temporaires. Il est recommandé d'en ajouter une."));

        $this->token->method('getUser')->willReturn($this->createConfiguredMock(Utilisateur::class, ['getGroupe' => $this->createMock(Groupe::class)]));

        $etablissement = $this->createEtablissementMock();

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testDenyIfMultipleResourcesFound(): void
    {
        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementManifestationTemporaire')
            ->willThrowException(new NonUniqueResultException());

        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->equalTo('Plusieurs règles trouvées pour les manifestations temporaires. Il est recommandé de supprimer les doublons.'));

        $this->token->method('getUser')->willReturn($this->createConfiguredMock(Utilisateur::class, ['getGroupe' => $this->createMock(Groupe::class)]));

        $etablissement = $this->createEtablissementMock();

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testDenyIfUserIsNotInstanceOfUtilisateur(): void
    {
        $resource = $this->createConfiguredMock(Resources::class, ['getName' => 'etablissement_temp_0_0']);

        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementManifestationTemporaire')
            ->willReturn($resource);

        $this->token->method('getUser')->willReturn(null);

        $etablissement = $this->createEtablissementMock();

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testDenyIfUserGroupIsNull(): void
    {
        $resource = $this->createConfiguredMock(Resources::class, ['getName' => 'etablissement_temp_0_0']);

        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementManifestationTemporaire')
            ->willReturn($resource);

        $user = $this->createConfiguredMock(Utilisateur::class, ['getGroupe' => null]);
        $this->token->method('getUser')->willReturn($user);

        $etablissement = $this->createEtablissementMock();

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testGrantIfPrivilegeExistsInUserGroup(): void
    {
        $resource = $this->createConfiguredMock(Resources::class, ['getName' => 'etablissement_temp_0_0']);

        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementManifestationTemporaire')
            ->willReturn($resource);

        $privilege = $this->createMock(Privileges::class);
        $this->privilegesRepository
            ->method('findOneBy')
            ->with(['name' => EtablissementVoter::LECTURE, 'resource' => $resource])
            ->willReturn($privilege);

        $userGroup = $this->createConfiguredMock(Groupe::class, [
            'getPrivileges' => new ArrayCollection([$privilege])
        ]);

        $user = $this->createConfiguredMock(Utilisateur::class, ['getGroupe' => $userGroup]);
        $this->token->method('getUser')->willReturn($user);

        $etablissement = $this->createEtablissementMock();

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }
}
