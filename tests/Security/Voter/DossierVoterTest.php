<?php

namespace App\Tests\Security\Voter;

use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\Groupe;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\DossierVoter;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DossierVoterTest extends TestCase
{
    // @todo: Traiter le cas spécial du dossier_0
    public function testAccessIsGranted(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->expects($this->once())->method('getName')->willReturn('dossier_1');

        $privilege = new Privileges();
        $privilege->setName(DossierVoter::LECTURE);
        $privilege->setResource($resource);

        $groupe = $this->createMock(Groupe::class);
        $groupe->expects($this->once())->method('getPrivileges')->willReturn(new ArrayCollection([$privilege]));

        $user = $this->createMock(Utilisateur::class);
        $user->expects($this->once())->method('getGroupe')->willReturn($groupe);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->expects($this->any())->method('findRessourcesSpecialiseesDossier')->willReturn(
            [$resource]
        );

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->expects($this->once())->method('findOneBy')->willReturn($privilege);

        $nature = $this->createMock(DossierNatureListe::class);
        $nature->expects($this->once())->method('getId')->willReturn(1);

        $dossier = $this->createMock(Dossier::class);
        $dossier->expects($this->once())->method('getNature')->willReturn($nature);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            (new DossierVoter($resourcesRepository, $privilegesRepository))->vote($token, $dossier, [DossierVoter::LECTURE])
        );
    }

    public function testAccessIsDenied(): void
    {
        $resource = $this->createMock(Resources::class);
        $resource->expects($this->once())->method('getName')->willReturn('dossier_1');

        $privilege = new Privileges();
        $privilege->setName(DossierVoter::LECTURE);
        $privilege->setResource($resource);

        $groupe = $this->createMock(Groupe::class);
        $groupe->expects($this->once())->method('getPrivileges')->willReturn(new ArrayCollection([]));

        $user = $this->createMock(Utilisateur::class);
        $user->expects($this->once())->method('getGroupe')->willReturn($groupe);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->expects($this->any())->method('findRessourcesSpecialiseesDossier')->willReturn(
            [$resource]
        );

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->expects($this->once())->method('findOneBy')->willReturn($privilege);

        $nature = $this->createMock(DossierNatureListe::class);
        $nature->expects($this->once())->method('getId')->willReturn(1);

        $dossier = $this->createMock(Dossier::class);
        $dossier->expects($this->once())->method('getNature')->willReturn($nature);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            (new DossierVoter($resourcesRepository, $privilegesRepository))->vote($token, $dossier, [DossierVoter::LECTURE])
        );
    }
}
