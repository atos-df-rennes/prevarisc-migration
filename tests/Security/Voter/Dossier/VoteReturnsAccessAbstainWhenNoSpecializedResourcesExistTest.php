<?php

namespace App\Tests\Security\Voter\Dossier;

use App\Entity\Dossier;
use App\Security\Voter\DossierVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use App\Repository\ResourcesRepository;
use App\Repository\PrivilegesRepository;

class VoteReturnsAccessAbstainWhenNoSpecializedResourcesExistTest extends TestCase
{
    public function testVoteReturnsAccessAbstainWhenNoSpecializedResourcesExist()
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository);

        $dossier = $this->createMock(Dossier::class);
        $resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn([]);
        $token = $this->createMock(TokenInterface::class);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
