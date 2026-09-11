<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\Etablissement\EtablissementManifestationTemporaireVoter;
use App\Security\Voter\Etablissement\EtablissementVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EtablissementManifestationTemporaireVoterTest extends TestCase
{
    private ResourcesRepository $resourcesRepository;

    private PrivilegesRepository $privilegesRepository;

    /**
     * @var MockObject&LoggerInterface
     */
    private MockObject $logger;

    private EtablissementManifestationTemporaireVoter $voter;

    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->resourcesRepository = $this->createStub(ResourcesRepository::class);
        $this->privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->token = $this->createStub(TokenInterface::class);

        $this->voter = new EtablissementManifestationTemporaireVoter(
            $this->resourcesRepository,
            $this->privilegesRepository,
            $this->logger
        );
    }

    private function createEtablissementMock(): Etablissement
    {
        $genre = $this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::MANIFESTATION_TEMPORAIRE]);
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, ['getGenre' => $genre]);

        return $this->createConfiguredStub(Etablissement::class, ['getLatestInformations' => $latestInformations]);
    }

    public function testAbstainIfParentDoesNotSupport(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $etablissement = $this->createStub(Etablissement::class);

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
            ->with("Aucune règle trouvée pour les manifestations temporaires. Il est recommandé d'en ajouter une.");

        $this->token->method('getUser')->willReturn($this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $this->createStub(Groupe::class)]));

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
            ->with('Plusieurs règles trouvées pour les manifestations temporaires. Il est recommandé de supprimer les doublons.');

        $this->token->method('getUser')->willReturn($this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $this->createStub(Groupe::class)]));

        $etablissement = $this->createEtablissementMock();

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testDenyIfUserIsNotInstanceOfUtilisateur(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_temp_0_0']);

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
        $this->logger->expects($this->never())->method('warning');
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_temp_0_0']);

        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementManifestationTemporaire')
            ->willReturn($resource);

        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => null]);
        $this->token->method('getUser')->willReturn($user);

        $etablissement = $this->createEtablissementMock();

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testGrantIfPrivilegeExistsInUserGroup(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $resource = $this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_temp_0_0']);

        $this->resourcesRepository
            ->method('findRessourcesSpecialiseesEtablissementManifestationTemporaire')
            ->willReturn($resource);

        $privilege = $this->createStub(Privileges::class);
        $this->privilegesRepository
            ->method('findOneBy')
            ->willReturnCallback(function (array $criteria) use ($privilege, $resource): ?Privileges {
                $this->assertSame(['name' => EtablissementVoter::LECTURE, 'resource' => $resource], $criteria);

                return $privilege;
            });

        $userGroup = $this->createConfiguredStub(Groupe::class, [
            'getPrivileges' => new ArrayCollection([$privilege])
        ]);

        $user = $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $userGroup]);
        $this->token->method('getUser')->willReturn($user);

        $etablissement = $this->createEtablissementMock();

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $etablissement, [EtablissementVoter::LECTURE])
        );
    }
}
