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

/**
 * Vérifie que DossierVoter n'applique pas le contrôle ets quand le dossier n'a aucun établissement lié.
 * Aligne sur le cas $i === 0 du Plugin_ACL legacy (ligne 294) : si aucune ressource établissement
 * n'est présente dans les ressources de la page, la vérification ets est ignorée.
 */
final class VoteReturnsAccessGrantedWhenNoEtablissementsLinkedTest extends TestCase
{
    public function testVoteReturnsAccessGrantedWhenNoEtablissementsLinked(): void
    {
        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $privilegesRepository = $this->createMock(PrivilegesRepository::class);

        // Le checker ne doit pas être appelé car aucun établissement n'est lié
        $collectionAccessChecker = $this->createMock(EtablissementCollectionAccessChecker::class);
        $collectionAccessChecker->expects($this->never())->method('hasAtLeastOneAccessible');

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

        $dossier = $this->createMock(Dossier::class);
        $nature = $this->createMock(DossierNatureListe::class);
        $nature->method('getId')->willReturn(1);
        $dossier->method('getNature')->willReturn($nature);
        $dossier->method('getEtablissements')->willReturn(new ArrayCollection([]));

        $result = $voter->vote($token, $dossier, [DossierVoter::LECTURE]);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }
}
