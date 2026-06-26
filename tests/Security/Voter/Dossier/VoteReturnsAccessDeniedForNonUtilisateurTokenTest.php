<?php

namespace App\Tests\Security\Voter\Dossier;

use App\Entity\Dossier;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\DossierVoter;
use App\Service\Voter\EtablissementCollectionAccessChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VoteReturnsAccessDeniedForNonUtilisateurTokenTest extends TestCase
{
    public function testVoteReturnsAccessDeniedForNonUtilisateurToken(): void
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository, $collectionAccessChecker);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $dossier = $this->createMock(Dossier::class);
        $resourcesRepository->method('findRessourcesSpecialiseesSimplesDossier')->willReturn(['resource_1']);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}
