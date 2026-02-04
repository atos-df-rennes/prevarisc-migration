<?php

namespace App\Tests\Security\Voter\Etablissement;

use App\Entity\Etablissement;
use App\Entity\EtablissementCategorie;
use App\Entity\EtablissementInformations;
use App\Entity\Famille;
use App\Entity\Genre;
use App\Entity\Groupe;
use App\Entity\Utilisateur;
use App\Security\Voter\EtablissementErpVoter;
use App\Security\Voter\EtablissementHabitationVoter;
use App\Security\Voter\EtablissementIghVoter;
use App\Security\Voter\EtablissementSiteVoter;
use App\Security\Voter\EtablissementVoter;
use App\Service\Voter\EtablissementVoterFactory;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EtablissementSiteVoterTest extends TestCase
{
    /**
     * Crée un mock d'utilisateur avec un groupe.
     *
     * @return MockObject&Utilisateur
     */
    private function createMockUser(): MockObject
    {
        $groupe = $this->createMock(Groupe::class);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getGroupe')->willReturn($groupe);

        return $user;
    }

    /**
     * Crée un mock de token avec un utilisateur.
     *
     * @param MockObject&Utilisateur $user
     *
     * @return MockObject&TokenInterface
     */
    private function createMockToken(MockObject $user): MockObject
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    /**
     * Crée un mock d'établissement Site avec des fils.
     *
     * @param Etablissement[] $fils
     *
     * @return MockObject&Etablissement
     */
    private function createMockSite(array $fils = []): MockObject
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => EtablissementSiteVoter::ID_SITE]);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getGenre')->willReturn($genre);

        $site = $this->createMock(Etablissement::class);
        $site->method('getLatestInformations')->willReturn($latestInformations);
        $site->method('getFils')->willReturn(new ArrayCollection($fils));

        return $site;
    }

    /**
     * Crée un mock d'établissement ERP fils.
     *
     * @return MockObject&Etablissement
     */
    private function createMockErpFils(): MockObject
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => EtablissementErpVoter::ID_ERP]);
        $categorie = $this->createConfiguredMock(EtablissementCategorie::class, ['getId' => 1]);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getGenre')->willReturn($genre);
        $latestInformations->method('getCategorie')->willReturn($categorie);
        $latestInformations->method('getTypeActivite')->willReturn(null);

        $erp = $this->createMock(Etablissement::class);
        $erp->method('getLatestInformations')->willReturn($latestInformations);
        $erp->method('getAdresses')->willReturn(new ArrayCollection([]));

        return $erp;
    }

    /**
     * Crée un mock d'établissement IGH fils.
     *
     * @return MockObject&Etablissement
     */
    private function createMockIghFils(): MockObject
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => EtablissementIghVoter::ID_IGH]);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getGenre')->willReturn($genre);
        $latestInformations->method('getClasse')->willReturn(null);

        $igh = $this->createMock(Etablissement::class);
        $igh->method('getLatestInformations')->willReturn($latestInformations);
        $igh->method('getAdresses')->willReturn(new ArrayCollection([]));

        return $igh;
    }

    /**
     * Crée un mock d'établissement Habitation fils.
     *
     * @return MockObject&Etablissement
     */
    private function createMockHabitationFils(): MockObject
    {
        $genre = $this->createConfiguredMock(Genre::class, ['getId' => EtablissementHabitationVoter::ID_HABITATION]);
        $famille = $this->createConfiguredMock(Famille::class, ['getId' => 1]);

        $latestInformations = $this->createMock(EtablissementInformations::class);
        $latestInformations->method('getGenre')->willReturn($genre);
        $latestInformations->method('getFamille')->willReturn($famille);

        $habitation = $this->createMock(Etablissement::class);
        $habitation->method('getLatestInformations')->willReturn($latestInformations);
        $habitation->method('getAdresses')->willReturn(new ArrayCollection([]));

        return $habitation;
    }

    /**
     * Crée un voter ERP qui accorde l'accès.
     *
     * @return MockObject&EtablissementErpVoter
     */
    private function createGrantingErpVoter(): MockObject
    {
        $voter = $this->createMock(EtablissementErpVoter::class);
        $voter->method('vote')->willReturn(VoterInterface::ACCESS_GRANTED);

        return $voter;
    }

    /**
     * Crée un voter ERP qui refuse l'accès.
     *
     * @return MockObject&EtablissementErpVoter
     */
    private function createDenyingErpVoter(): MockObject
    {
        $voter = $this->createMock(EtablissementErpVoter::class);
        $voter->method('vote')->willReturn(VoterInterface::ACCESS_DENIED);

        return $voter;
    }

    /**
     * Test : Site avec fils ERP et accès ERP autorisé → ACCESS_GRANTED.
     */
    public function testSiteAvecFilsErpAccordeAccesSiAccesErpAutorise(): void
    {
        $user = $this->createMockUser();
        $token = $this->createMockToken($user);
        $erpFils = $this->createMockErpFils();
        $site = $this->createMockSite([$erpFils]);

        $erpVoter = $this->createGrantingErpVoter();
        $factory = $this->createMock(EtablissementVoterFactory::class);
        $factory->method('getVoterForGenre')
            ->with(EtablissementErpVoter::ID_ERP)
            ->willReturn($erpVoter);

        $logger = $this->createMock(LoggerInterface::class);
        $siteVoter = new EtablissementSiteVoter($factory, $logger);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site avec fils ERP mais accès ERP refusé → ACCESS_DENIED.
     */
    public function testSiteAvecFilsErpRefuseAccesSiAccesErpRefuse(): void
    {
        $user = $this->createMockUser();
        $token = $this->createMockToken($user);
        $erpFils = $this->createMockErpFils();
        $site = $this->createMockSite([$erpFils]);

        $erpVoter = $this->createDenyingErpVoter();
        $factory = $this->createMock(EtablissementVoterFactory::class);
        $factory->method('getVoterForGenre')
            ->with(EtablissementErpVoter::ID_ERP)
            ->willReturn($erpVoter);

        $logger = $this->createMock(LoggerInterface::class);
        $siteVoter = new EtablissementSiteVoter($factory, $logger);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * Test : Site avec fils IGH et accès IGH autorisé → ACCESS_GRANTED.
     */
    public function testSiteAvecFilsIghAccordeAccesSiAccesIghAutorise(): void
    {
        $user = $this->createMockUser();
        $token = $this->createMockToken($user);
        $ighFils = $this->createMockIghFils();
        $site = $this->createMockSite([$ighFils]);

        // Mock de la factory qui retourne un IGH voter autorisant l'accès
        $ighVoter = $this->createMock(EtablissementIghVoter::class);
        $ighVoter->method('vote')->willReturn(VoterInterface::ACCESS_GRANTED);

        $factory = $this->createMock(EtablissementVoterFactory::class);
        $factory->method('getVoterForGenre')
            ->with(EtablissementIghVoter::ID_IGH)
            ->willReturn($ighVoter);

        $logger = $this->createMock(LoggerInterface::class);
        $siteVoter = new EtablissementSiteVoter($factory, $logger);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site avec fils Habitation et accès Habitation autorisé → ACCESS_GRANTED.
     */
    public function testSiteAvecFilsHabitationAccordeAccesSiAccesHabitationAutorise(): void
    {
        $user = $this->createMockUser();
        $token = $this->createMockToken($user);
        $habitationFils = $this->createMockHabitationFils();
        $site = $this->createMockSite([$habitationFils]);

        // Mock de la factory qui retourne un Habitation voter autorisant l'accès
        $habitationVoter = $this->createMock(EtablissementHabitationVoter::class);
        $habitationVoter->method('vote')->willReturn(VoterInterface::ACCESS_GRANTED);

        $factory = $this->createMock(EtablissementVoterFactory::class);
        $factory->method('getVoterForGenre')
            ->with(EtablissementHabitationVoter::ID_HABITATION)
            ->willReturn($habitationVoter);

        $logger = $this->createMock(LoggerInterface::class);
        $siteVoter = new EtablissementSiteVoter($factory, $logger);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site avec fils mixtes (ERP refusé, IGH autorisé) → ACCESS_GRANTED (au moins un autorisé).
     */
    public function testSiteAvecFilsMixtesAccordeAccesSiAuMoinsUnAccesAutorise(): void
    {
        $user = $this->createMockUser();
        $token = $this->createMockToken($user);
        $erpFils = $this->createMockErpFils();
        $ighFils = $this->createMockIghFils();
        $site = $this->createMockSite([$erpFils, $ighFils]);

        // Mock ERP voter refuse, IGH voter autorise
        $erpVoter = $this->createMock(EtablissementErpVoter::class);
        $erpVoter->method('vote')->willReturn(VoterInterface::ACCESS_DENIED);

        $ighVoter = $this->createMock(EtablissementIghVoter::class);
        $ighVoter->method('vote')->willReturn(VoterInterface::ACCESS_GRANTED);

        $factory = $this->createMock(EtablissementVoterFactory::class);
        $factory->method('getVoterForGenre')
            ->willReturnMap([
                [EtablissementErpVoter::ID_ERP, $erpVoter],
                [EtablissementIghVoter::ID_IGH, $ighVoter],
            ]);

        $logger = $this->createMock(LoggerInterface::class);
        $siteVoter = new EtablissementSiteVoter($factory, $logger);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * Test : Site sans fils → ACCESS_DENIED.
     */
    public function testSiteSansFilsRefuseAcces(): void
    {
        $user = $this->createMockUser();
        $token = $this->createMockToken($user);
        $site = $this->createMockSite([]); // Aucun fils

        $factory = $this->createMock(EtablissementVoterFactory::class);
        $logger = $this->createMock(LoggerInterface::class);
        $siteVoter = new EtablissementSiteVoter($factory, $logger);

        $result = $siteVoter->vote($token, $site, [EtablissementVoter::LECTURE]);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}
