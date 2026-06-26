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

class VoteReturnsAccessAbstainForUnsupportedAttributeTest extends TestCase
{
    public function testVoteReturnsAccessAbstainForUnsupportedAttribute(): void
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository, $collectionAccessChecker);

        $dossier = $this->createMock(Dossier::class);
        $token = $this->createMock(TokenInterface::class);

        $result = $voter->vote($token, $dossier, ['unsupported_attribute']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
