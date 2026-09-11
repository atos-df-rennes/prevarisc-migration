<?php

declare(strict_types=1);

namespace App\Tests\Security\Voter\Etablissement;

use App\Constant\GenreEtablissement;
use App\Entity\Commune;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Groupement;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\Etablissement\EtablissementBupVoter;
use App\Security\Voter\Etablissement\EtablissementVoter;
use App\Service\Voter\VoterUtils;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class EtablissementBupVoterTest extends TestCase
{
    private ResourcesRepository $resourcesRepository;

    private PrivilegesRepository $privilegesRepository;

    private EtablissementBupVoter $voter;

    protected function setUp(): void
    {
        $this->resourcesRepository = $this->createStub(ResourcesRepository::class);
        $this->privilegesRepository = $this->createStub(PrivilegesRepository::class);
        $this->voter = new EtablissementBupVoter($this->resourcesRepository, $this->privilegesRepository, new VoterUtils());
    }

    private function createEtablissementMock(): Etablissement
    {
        $genre = $this->createConfiguredStub(Genre::class, ['getId' => GenreEtablissement::BUP]);
        $latestInformations = $this->createConfiguredStub(EtablissementInformations::class, ['getGenre' => $genre]);

        return $this->createConfiguredStub(Etablissement::class, ['getLatestInformations' => $latestInformations]);
    }

    private function createTokenWithUser(Utilisateur $user): TokenInterface
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function createUserWithPrivileges(array $privileges = []): Utilisateur
    {
        $groupe = $this->createConfiguredStub(Groupe::class, ['getPrivileges' => new ArrayCollection($privileges)]);

        return $this->createConfiguredStub(Utilisateur::class, ['getGroupe' => $groupe]);
    }

    public function testUserWithPrivilegeOnGeneralBupRuleHasAccess(): void
    {
        $this->resourcesRepository->method('findRessourcesSpecialiseesEtablissementBup')
            ->willReturn([$this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_bup_0_0'])]);
        $privilege = new Privileges();
        $user = $this->createUserWithPrivileges([$privilege]);
        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->createTokenWithUser($user), $this->createEtablissementMock(), [EtablissementVoter::LECTURE])
        );
    }

    public function testUserWithoutPrivilegeOnGeneralBupRuleIsDeniedAccess(): void
    {
        $this->resourcesRepository->method('findRessourcesSpecialiseesEtablissementBup')
            ->willReturn([$this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_bup_0_0'])]);
        $user = $this->createUserWithPrivileges();
        $this->privilegesRepository->method('findOneBy')->willReturn(null);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->createTokenWithUser($user), $this->createEtablissementMock(), [EtablissementVoter::LECTURE])
        );
    }

    public function testUserWithSpecificGroupementAndCommunePrivilegesHasAccess(): void
    {
        $this->resourcesRepository->method('findRessourcesSpecialiseesEtablissementBup')
            ->willReturn([$this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_bup_1_1'])]);
        $privilege = new Privileges();
        $commune = $this->createStub(Commune::class);
        $user = $this->createConfiguredStub(Utilisateur::class, [
            'getGroupe' => $this->createConfiguredStub(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]),
            'getGroupements' => new ArrayCollection([$this->createConfiguredStub(Groupement::class, ['getCommunes' => new ArrayCollection([$commune])])]),
            'getCommune' => $commune
        ]);
        $etablissement = $this->createEtablissementMock();
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([
            $this->createConfiguredStub(EtablissementAdresse::class, ['getCommune' => $commune])
        ]));
        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->createTokenWithUser($user), $etablissement, [EtablissementVoter::LECTURE])
        );
    }

    public function testUserWithoutMatchingGroupementOrCommunePrivilegesIsDeniedAccess(): void
    {
        $this->resourcesRepository->method('findRessourcesSpecialiseesEtablissementBup')
            ->willReturn([$this->createConfiguredStub(Resources::class, ['getName' => 'etablissement_bup_1_1'])]);
        $privilege = new Privileges();
        $user = $this->createConfiguredStub(Utilisateur::class, [
            'getGroupe' => $this->createConfiguredStub(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]),
            'getGroupements' => new ArrayCollection([$this->createConfiguredStub(Groupement::class, ['getCommunes' => new ArrayCollection()])]),
            'getCommune' => $this->createStub(Commune::class)
        ]);
        $etablissement = $this->createEtablissementMock();
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([
            $this->createConfiguredStub(EtablissementAdresse::class, ['getCommune' => $this->createStub(Commune::class)])
        ]));
        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->createTokenWithUser($user), $etablissement, [EtablissementVoter::LECTURE])
        );
    }
}
