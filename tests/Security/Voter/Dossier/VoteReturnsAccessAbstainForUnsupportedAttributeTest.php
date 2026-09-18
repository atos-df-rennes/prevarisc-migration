<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter\Dossier;

use App\Entity\Dossier;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\DossierVoter;
use App\Service\Voter\EtablissementCollectionAccessChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class VoteReturnsAccessAbstainForUnsupportedAttributeTest extends TestCase
{
    public function testVoteReturnsAccessAbstainForUnsupportedAttribute(): void
    {
        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $collectionAccessChecker = $this->createStub(EtablissementCollectionAccessChecker::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository, $collectionAccessChecker);

        $dossier = $this->createStub(Dossier::class);
        $token = $this->createStub(TokenInterface::class);

        $result = $voter->vote($token, $dossier, ['unsupported_attribute']);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
