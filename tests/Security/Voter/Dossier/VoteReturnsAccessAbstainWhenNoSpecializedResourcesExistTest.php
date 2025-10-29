<?php

namespace App\Tests\Security\Voter\Dossier;

use App\Entity\Dossier;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\DossierVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VoteReturnsAccessAbstainWhenNoSpecializedResourcesExistTest extends TestCase
{
    public function testVoteReturnsAccessAbstainWhenNoSpecializedResourcesExist(): void
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository);

        $dossier = $this->createMock(Dossier::class);
        $resourcesRepository->method('findRessourcesSpecialiseesSimplesDossier')->willReturn([]);
        $token = $this->createMock(TokenInterface::class);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
