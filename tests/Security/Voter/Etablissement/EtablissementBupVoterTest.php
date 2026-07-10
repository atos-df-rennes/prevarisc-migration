<?php

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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementBupVoterTest extends TestCase
{
    private MockObject $resourcesRepository;

    private MockObject $privilegesRepository;

    private EtablissementBupVoter $voter;

    protected function setUp(): void
    {
        $this->resourcesRepository = $this->createMock(ResourcesRepository::class);
        $this->privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $this->voter = new EtablissementBupVoter($this->resourcesRepository, $this->privilegesRepository, new VoterUtils());
    }

    private function createEtablissementMock(): Etablissement
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => GenreEtablissement::BUP]);
        $latestInformations = $this->createConfiguredMock(EtablissementInformations::class, ['getGenre' => $genre]);

        return $this->createConfiguredMock(Etablissement::class, ['getLatestInformations' => $latestInformations]);
    }

    private function createTokenWithUser(Utilisateur $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    private function createUserWithPrivileges(array $privileges = []): Utilisateur
    {
        $groupe = $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection($privileges)]);

        return $this->createConfiguredMock(Utilisateur::class, ['getGroupe' => $groupe]);
    }

    public function testUserWithPrivilegeOnGeneralBupRuleHasAccess(): void
    {
        $this->resourcesRepository->method('findRessourcesSpecialiseesEtablissementBup')
            ->willReturn([$this->createConfiguredMock(Resources::class, ['getName' => 'etablissement_bup_0_0'])]);
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
            ->willReturn([$this->createConfiguredMock(Resources::class, ['getName' => 'etablissement_bup_0_0'])]);
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
            ->willReturn([$this->createConfiguredMock(Resources::class, ['getName' => 'etablissement_bup_1_1'])]);
        $privilege = new Privileges();
        $commune = $this->createMock(Commune::class);
        $user = $this->createConfiguredMock(Utilisateur::class, [
            'getGroupe' => $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]),
            'getGroupements' => new ArrayCollection([$this->createConfiguredMock(Groupement::class, ['getCommunes' => new ArrayCollection([$commune])])]),
            'getCommune' => $commune
        ]);
        $etablissement = $this->createEtablissementMock();
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([
            $this->createConfiguredMock(EtablissementAdresse::class, ['getCommune' => $commune])
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
            ->willReturn([$this->createConfiguredMock(Resources::class, ['getName' => 'etablissement_bup_1_1'])]);
        $privilege = new Privileges();
        $user = $this->createConfiguredMock(Utilisateur::class, [
            'getGroupe' => $this->createConfiguredMock(Groupe::class, ['getPrivileges' => new ArrayCollection([$privilege])]),
            'getGroupements' => new ArrayCollection([$this->createConfiguredMock(Groupement::class, ['getCommunes' => new ArrayCollection()])]),
            'getCommune' => $this->createMock(Commune::class)
        ]);
        $etablissement = $this->createEtablissementMock();
        $etablissement->method('getAdresses')->willReturn(new ArrayCollection([
            $this->createConfiguredMock(EtablissementAdresse::class, ['getCommune' => $this->createMock(Commune::class)])
        ]));
        $this->privilegesRepository->method('findOneBy')->willReturn($privilege);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->createTokenWithUser($user), $etablissement, [EtablissementVoter::LECTURE])
        );
    }
}
