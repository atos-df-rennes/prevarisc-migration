<?php

namespace App\Tests\Security\Voter\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\Groupe;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\DossierVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class VoteIgnoresResourceWithMultipleNaturesTest extends TestCase
{
    public function testVoteIgnoresResourceWithMultipleNatures(): void
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository);

        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(1);
        $dossier->method('getNature')->willReturn($nature);

        $resourceMultiple = $this->createMock(Resources::class);
        $resourceMultiple->method('getName')->willReturn('resource_1-2');
        $resourcesRepository->method('findRessourcesSpecialiseesDossier')->willReturn([$resourceMultiple]);

        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([]));
        $user->method('getGroupe')->willReturn($group);

        $privilegesRepository->method('findOneBy')->willReturn(null);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}
