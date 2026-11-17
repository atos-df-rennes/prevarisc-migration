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

final class VoteReturnsAccessAbstainWhenNoSpecializedResourcesExistTest extends TestCase
{
    public function testVoteReturnsAccessAbstainWhenNoSpecializedResourcesExist(): void
    {
        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $collectionAccessChecker = $this->createStub(EtablissementCollectionAccessChecker::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository, $collectionAccessChecker);

        $dossier = $this->createStub(Dossier::class);
        $resourcesRepository->method('findRessourcesSpecialiseesSimplesDossier')->willReturn([]);
        $token = $this->createStub(TokenInterface::class);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }
}
