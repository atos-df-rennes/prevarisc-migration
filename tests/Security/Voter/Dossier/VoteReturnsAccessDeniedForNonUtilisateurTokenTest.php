<?php

namespace App\Tests\Security\Voter\Dossier;

use App\Entity\Dossier;
use App\Security\Voter\DossierVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use App\Repository\ResourcesRepository;
use App\Repository\PrivilegesRepository;

class VoteReturnsAccessDeniedForNonUtilisateurTokenTest extends TestCase
{
    public function testVoteReturnsAccessDeniedForNonUtilisateurToken()
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $dossier = $this->createMock(Dossier::class);
        $resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn(['resource_1']);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}
