<?php

namespace App\Tests\Security\Voter;

use App\Entity\Commune;
use App\Entity\Etablissement;
use App\Entity\EtablissementAdresse;
use App\Entity\EtablissementInformations;
use App\Entity\Famille;
use App\Entity\Genre;
use App\Entity\Groupement;
use App\Entity\Privileges;
use App\Entity\Resources;
use App\Entity\Utilisateur;
use App\Entity\Groupe;
use App\Repository\PrivilegesRepository;
use App\Repository\ResourcesRepository;
use App\Security\Voter\EtablissementHabitationVoter;
use App\Security\Voter\EtablissementVoter;
use App\Service\Voter\VoterUtils;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementHabitationVoterTest extends TestCase
{
    public function testAccessAllFamillesRuleGrantsAccess()
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_0_0_0');

        $privilege = $this->createMock(Privileges::class);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')
            ->with(4)
            ->willReturn([$resource]);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')
            ->willReturn($privilege);

        $userPrivileges = $this->createMock('Doctrine\\Common\\Collections\\Collection');
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getFamille')->willReturn(null);
        $latestInformations->method('getGenre')->willReturn($genre);

        $subject = $this->createMock(Etablissement::class);
        $subject->method('getLatestInformations')->willReturn($latestInformations);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voterUtils = $this->createMock(VoterUtils::class);
        $voterUtils->method('isUserGroupementsAndCommuneAllowed')->willReturn(true);

        $voter = new EtablissementHabitationVoter($resourcesRepository, $privilegesRepository, $voterUtils);

        $result = $voter->vote($token, $subject, [EtablissementVoter::LECTURE]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result,
            "L’utilisateur doit avoir accès à tous les établissements de genre Habitation si la règle toutes familles est présente et le privilège activé.");
    }

    public function testAccessSpecificFamilleRuleGrantsAccess()
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_3_0_0');

        $privilege = $this->createMock(Privileges::class);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')
            ->with(4)
            ->willReturn([$resource]);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')
            ->willReturn($privilege);

        $userPrivileges = $this->createMock('Doctrine\\Common\\Collections\\Collection');
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);
        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getFamille')->willReturn($famille);
        $latestInformations->method('getGenre')->willReturn($genre);

        $subject = $this->createMock(Etablissement::class);
        $subject->method('getLatestInformations')->willReturn($latestInformations);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voterUtils = $this->createMock(VoterUtils::class);
        $voterUtils->method('isUserGroupementsAndCommuneAllowed')->willReturn(true);

        $voter = new EtablissementHabitationVoter($resourcesRepository, $privilegesRepository, $voterUtils);

        $result = $voter->vote($token, $subject, [EtablissementVoter::LECTURE]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result,
            "L’utilisateur doit avoir accès aux établissements de la famille 3 si la règle famille 3 est présente et le privilège activé.");
    }

    public function testAccessMultipleFamillesRuleGrantsAccess()
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_2-3_0_0');

        $privilege = $this->createMock(Privileges::class);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')
            ->with(4)
            ->willReturn([$resource]);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')
            ->willReturn($privilege);

        $userPrivileges = $this->createMock('Doctrine\\Common\\Collections\\Collection');
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille2 = $this->createConfiguredMock(Famille::class, ['getId' => 2]);
        $famille3 = $this->createConfiguredMock(Famille::class, ['getId' => 3]);

        // Test accès famille 2
        $latestInformations2 = $this->createMock(EtablissementInformations::class);
        $latestInformations2->method('getFamille')->willReturn($famille2);
        $latestInformations2->method('getGenre')->willReturn($genre);
        $subject2 = $this->createMock(Etablissement::class);
        $subject2->method('getLatestInformations')->willReturn($latestInformations2);

        // Test accès famille 3
        $latestInformations3 = $this->createMock(EtablissementInformations::class);
        $latestInformations3->method('getFamille')->willReturn($famille3);
        $latestInformations3->method('getGenre')->willReturn($genre);
        $subject3 = $this->createMock(Etablissement::class);
        $subject3->method('getLatestInformations')->willReturn($latestInformations3);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $voterUtils = $this->createMock(VoterUtils::class);
        $voterUtils->method('isUserGroupementsAndCommuneAllowed')->willReturn(true);

        $voter = new EtablissementHabitationVoter($resourcesRepository, $privilegesRepository, $voterUtils);

        $result2 = $voter->vote($token, $subject2, [EtablissementVoter::LECTURE]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result2,
            "L’utilisateur doit avoir accès aux établissements de la famille 2 si la règle famille 2-3 est présente et le privilège activé.");

        $result3 = $voter->vote($token, $subject3, [EtablissementVoter::LECTURE]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result3,
            "L’utilisateur doit avoir accès aux établissements de la famille 3 si la règle famille 2-3 est présente et le privilège activé.");
    }

    public function testAccessFamilleWithGroupementsRuleGrantsAccessOnlyForMatchingGroupements()
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_3_1_0');

        $privilege = $this->createMock(Privileges::class);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')
            ->with(4)
            ->willReturn([$resource]);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')
            ->willReturn($privilege);

        $userPrivileges = $this->createMock('Doctrine\\Common\\Collections\\Collection');
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $commune10 = $this->createConfiguredMock(Commune::class, ['getId' => 100]);
        $commune20 = $this->createConfiguredMock(Commune::class, ['getId' => 200]);
        $commune30 = $this->createConfiguredMock(Commune::class, ['getId' => 300]);

        // Mocks pour les groupements et adresses
        $userGroupement10 = $this->createMock(Groupement::class);
        $userGroupement10->method('getCommunes')->willReturn(new ArrayCollection([$commune10]));
        $userGroupement20 = $this->createMock(Groupement::class);
        $userGroupement20->method('getCommunes')->willReturn(new ArrayCollection([$commune20]));

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);
        $user->method('getGroupements')->willReturn(new ArrayCollection([$userGroupement10, $userGroupement20]));

        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);

        // Adresse pour groupement 10
        $adresse10 = $this->createMock(EtablissementAdresse::class);
        $adresse10->method('getCommune')->willReturn($commune10);
        // Adresse pour groupement 30
        $adresse30 = $this->createMock(EtablissementAdresse::class);
        $adresse30->method('getCommune')->willReturn($commune30);

        // Commune du groupement 10 (doit donner accès)
        $subjectCommune10 = $this->createMock(Etablissement::class);
        $latestInformations10 = $this->createMock(EtablissementInformations::class);
        $latestInformations10->method('getFamille')->willReturn($famille);
        $latestInformations10->method('getGenre')->willReturn($genre);
        $subjectCommune10->method('getLatestInformations')->willReturn($latestInformations10);
        $subjectCommune10->method('getAdresses')->willReturn(new ArrayCollection([$adresse10]));

        // Commune du groupement 30 (ne doit pas donner accès)
        $subjectCommune30 = $this->createMock(Etablissement::class);
        $latestInformations30 = $this->createMock(EtablissementInformations::class);
        $latestInformations30->method('getFamille')->willReturn($famille);
        $latestInformations30->method('getGenre')->willReturn($genre);
        $subjectCommune30->method('getLatestInformations')->willReturn($latestInformations30);
        $subjectCommune30->method('getAdresses')->willReturn(new ArrayCollection([$adresse30]));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Utilisation réelle de VoterUtils
        $voterUtils = new VoterUtils();

        $voter = new EtablissementHabitationVoter($resourcesRepository, $privilegesRepository, $voterUtils);

        $result10 = $voter->vote($token, $subjectCommune10, [EtablissementVoter::LECTURE]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result10,
            "L’utilisateur doit avoir accès à l’établissement dont la commune appartient à un groupement présent dans la liste des groupements de l’utilisateur.");

        $result30 = $voter->vote($token, $subjectCommune30, [EtablissementVoter::LECTURE]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result30,
            "L’utilisateur ne doit pas avoir accès à l’établissement dont la commune n’appartient à aucun groupement de l’utilisateur.");
    }

    public function testAccessFamilleWithCommuneRuleGrantsAccessOnlyForMatchingCommune()
    {
        $resource = $this->createMock(Resources::class);
        $resource->method('getName')->willReturn('etablissement_habitation_3_0_1');

        $privilege = $this->createMock(Privileges::class);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')
            ->with(4)
            ->willReturn([$resource]);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')
            ->willReturn($privilege);

        $userPrivileges = $this->createMock('Doctrine\\Common\\Collections\\Collection');
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $commune100 = $this->createConfiguredMock(Commune::class, ['getId' => 100]);
        $commune200 = $this->createConfiguredMock(Commune::class, ['getId' => 200]);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);
        $user->method('getCommune')->willReturn($commune100);

        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);

        // Adresse pour commune 100 (doit donner accès)
        $adresse100 = $this->createMock(EtablissementAdresse::class);
        $adresse100->method('getCommune')->willReturn($commune100);
        // Adresse pour commune 200 (ne doit pas donner accès)
        $adresse200 = $this->createMock(EtablissementAdresse::class);
        $adresse200->method('getCommune')->willReturn($commune200);

        // Etablissement avec commune 100
        $subjectCommune100 = $this->createMock(Etablissement::class);
        $latestInformations100 = $this->createMock(EtablissementInformations::class);
        $latestInformations100->method('getFamille')->willReturn($famille);
        $latestInformations100->method('getGenre')->willReturn($genre);
        $subjectCommune100->method('getLatestInformations')->willReturn($latestInformations100);
        $subjectCommune100->method('getAdresses')->willReturn(new ArrayCollection([$adresse100]));

        // Etablissement avec commune 200
        $subjectCommune200 = $this->createMock(Etablissement::class);
        $latestInformations200 = $this->createMock(EtablissementInformations::class);
        $latestInformations200->method('getFamille')->willReturn($famille);
        $latestInformations200->method('getGenre')->willReturn($genre);
        $subjectCommune200->method('getLatestInformations')->willReturn($latestInformations200);
        $subjectCommune200->method('getAdresses')->willReturn(new ArrayCollection([$adresse200]));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Utilisation réelle de VoterUtils
        $voterUtils = new VoterUtils();

        $voter = new EtablissementHabitationVoter($resourcesRepository, $privilegesRepository, $voterUtils);

        $result100 = $voter->vote($token, $subjectCommune100, [EtablissementVoter::LECTURE]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result100,
            "L’utilisateur doit avoir accès à l’établissement dont la commune est celle de l’utilisateur.");

        $result200 = $voter->vote($token, $subjectCommune200, [EtablissementVoter::LECTURE]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result200,
            "L’utilisateur ne doit pas avoir accès à l’établissement dont la commune est différente de celle de l’utilisateur.");
    }

    public function testAccessFamilleWithGroupementsAndAllRuleGrantsAccessToAllEstablishmentsOfFamille()
    {
        // Deux ressources : une pour la famille 3 avec groupements, une pour la famille 3 sans groupements
        $resourceGroupements = $this->createMock(Resources::class);
        $resourceGroupements->method('getName')->willReturn('etablissement_habitation_3_1_0');
        $resourceAll = $this->createMock(Resources::class);
        $resourceAll->method('getName')->willReturn('etablissement_habitation_3_0_0');

        $privilege = $this->createMock(Privileges::class);

        $resourcesRepository = $this->createMock(ResourcesRepository::class);
        $resourcesRepository->method('findRessourcesSpecialiseesEtablissement')
            ->with(4)
            ->willReturn([$resourceGroupements, $resourceAll]);

        $privilegesRepository = $this->createMock(PrivilegesRepository::class);
        $privilegesRepository->method('findOneBy')
            ->willReturn($privilege);

        $userPrivileges = $this->createMock('Doctrine\\Common\\Collections\\Collection');
        $userPrivileges->method('contains')->with($privilege)->willReturn(true);

        $groupe = $this->createMock(Groupe::class);
        $groupe->method('getPrivileges')->willReturn($userPrivileges);

        $commune10 = $this->createConfiguredMock(Commune::class, ['getId' => 100]);
        $commune20 = $this->createConfiguredMock(Commune::class, ['getId' => 200]);
        $commune30 = $this->createConfiguredMock(Commune::class, ['getId' => 300]);

        // Mocks pour les groupements et adresses
        $userGroupement10 = $this->createMock(Groupement::class);
        $userGroupement10->method('getCommunes')->willReturn(new ArrayCollection([$commune10]));
        $userGroupement20 = $this->createMock(Groupement::class);
        $userGroupement20->method('getCommunes')->willReturn(new ArrayCollection([$commune20]));

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);
        $user->method('getGroupements')->willReturn(new ArrayCollection([$userGroupement10, $userGroupement20]));

        $genre = $this->createConfiguredMock(Genre::class, ['getId' => 4]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 3]);

        // Adresse pour commune 10
        $adresse10 = $this->createMock(EtablissementAdresse::class);
        $adresse10->method('getCommune')->willReturn($commune10);
        // Adresse pour commune 30
        $adresse30 = $this->createMock(EtablissementAdresse::class);
        $adresse30->method('getCommune')->willReturn($commune30);

        // Etablissement de la famille 3, commune 10 (doit donner accès)
        $subjectCommune10 = $this->createMock(Etablissement::class);
        $latestInformations10 = $this->createMock(EtablissementInformations::class);
        $latestInformations10->method('getFamille')->willReturn($famille);
        $latestInformations10->method('getGenre')->willReturn($genre);
        $subjectCommune10->method('getLatestInformations')->willReturn($latestInformations10);
        $subjectCommune10->method('getAdresses')->willReturn(new ArrayCollection([$adresse10]));

        // Etablissement de la famille 3, commune 30 (doit aussi donner accès grâce à la règle sans groupements)
        $subjectCommune30 = $this->createMock(Etablissement::class);
        $latestInformations30 = $this->createMock(EtablissementInformations::class);
        $latestInformations30->method('getFamille')->willReturn($famille);
        $latestInformations30->method('getGenre')->willReturn($genre);
        $subjectCommune30->method('getLatestInformations')->willReturn($latestInformations30);
        $subjectCommune30->method('getAdresses')->willReturn(new ArrayCollection([$adresse30]));

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Utilisation réelle de VoterUtils
        $voterUtils = new VoterUtils();

        $voter = new EtablissementHabitationVoter($resourcesRepository, $privilegesRepository, $voterUtils);

        $result10 = $voter->vote($token, $subjectCommune10, [EtablissementVoter::LECTURE]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result10,
            "L’utilisateur doit avoir accès à l’établissement de la famille 3, commune 10 (règle groupements ou toutes communes).");

        $result30 = $voter->vote($token, $subjectCommune30, [EtablissementVoter::LECTURE]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result30,
            "L’utilisateur doit avoir accès à l’établissement de la famille 3, commune 30 grâce à la règle toutes communes (groupements ignorés).");
    }
}
