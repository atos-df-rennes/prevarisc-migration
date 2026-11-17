<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\DossierVoter;
use App\Service\Voter\EtablissementCollectionAccessChecker;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class VoteReturnsAccessGrantedWhenUserHasPrivilegeForSpecificNatureEvenIfAllNaturesDeniedTest extends TestCase
{
    public function testVoteReturnsAccessGrantedWhenUserHasPrivilegeForSpecificNatureEvenIfAllNaturesDenied(): void
    {
        $resourcesRepository = $this->createStub(ResourcesRepository::class);
        $privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $collectionAccessChecker = $this->createStub(EtablissementCollectionAccessChecker::class);
        $voter = new DossierVoter($resourcesRepository, $privilegesRepository, $collectionAccessChecker);

        $token = $this->createStub(TokenInterface::class);
        $user = $this->createStub(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        $dossier = $this->createStub(Dossier::class);
        $nature = $this->createStub(DossierNatureListe::class);
        $nature->method('getId')->willReturn(3);
        $dossier->method('getNature')->willReturn($nature);
        $dossier->method('getEtablissements')->willReturn(new ArrayCollection([]));

        $resourceAll = $this->createStub(Resources::class);
        $resourceAll->method('getName')->willReturn('resource_0');
        $privilegeAll = $this->createStub(Privileges::class);
        $privilegeAll->method('getResource')->willReturn($resourceAll);

        $resourceSpecific = $this->createStub(Resources::class);
        $resourceSpecific->method('getName')->willReturn('resource_3');
        $privilegeSpecific = $this->createStub(Privileges::class);
        $privilegeSpecific->method('getResource')->willReturn($resourceSpecific);

        $resourcesRepository->method('findRessourcesSpecialiseesSimplesDossier')->willReturn([$resourceAll, $resourceSpecific]);

        $privilegesRepository->method('findOneBy')->willReturnCallback(function (array $criteria) use ($privilegeSpecific, $resourceSpecific): ?Privileges {
            if ($criteria['resource'] === $resourceSpecific) {
                return $privilegeSpecific;
            }

            return null;
        });

        $group = $this->createStub(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([$privilegeSpecific]));
        $user->method('getGroupe')->willReturn($group);

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }
}
