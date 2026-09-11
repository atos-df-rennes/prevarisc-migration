<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter\Dossier;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\DossierVoter;
use App\Service\Voter\EtablissementCollectionAccessChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class VoteReturnsAccessDeniedWhenDossierHasNoNatureTest extends TestCase
{
    public function testVoteReturnsAccessDeniedWhenDossierHasNoNature(): void
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $collectionAccessChecker = $this->createStub(EtablissementCollectionAccessChecker::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository, $collectionAccessChecker);

        $token = $this->createMock(TokenInterface::class);
        $user = $this->createStub(Utilisateur::class);
        $token->method('getUser')->willReturn($user);
        $resourcesRepository->method('findRessourcesSpecialiseesSimplesDossier')->willReturn(['resource_1']);

        $dossier = $this->createMock(Dossier::class);
        $dossier->method('getNature')->willReturn(null);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}
