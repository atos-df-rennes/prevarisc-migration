<?php

namespace App\Tests\Security\Voter\Dossier;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\Etablissement;
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

/**
 * Vérifie que DossierVoter refuse l'accès quand aucun établissement lié n'est accessible (view_ets).
 * Aligne sur le Plugin_ACL legacy (lignes 278–302) : le contrôle ets précède le contrôle nature.
 */
class VoteReturnsAccessDeniedWhenLinkedEtablissementNotAccessibleTest extends TestCase
{
    public function testVoteReturnsAccessDeniedWhenLinkedEtablissementNotAccessible(): void
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);

        // Le checker signale qu'aucun établissement lié n'est accessible en view_ets
        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $collectionAccessChecker->method('hasAtLeastOneAccessible')->willReturn(false);

        $voter = new DossierVoter($resourcesRepository, $privilegesRepository, $collectionAccessChecker);

        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(Utilisateur::class);
        $token->method('getUser')->willReturn($user);

        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('dossier_1');
        $resourcesRepository->method('findRessourcesSpecialiseesSimplesDossier')->willReturn([$resource]);

        $privilege = $this->createStub(Privileges::class);
        $privilegesRepository->method('findOneBy')->willReturn($privilege);

        $group = $this->createMock(Groupe::class);
        $group->method('getPrivileges')->willReturn(new ArrayCollection([$privilege]));
        $user->method('getGroupe')->willReturn($group);

        $etablissement = $this->createStub(Etablissement::class);
        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(1);
        $dossier->method('getNature')->willReturn($nature);
        $dossier->method('getEtablissements')->willReturn(new ArrayCollection([$etablissement]));

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }
}
