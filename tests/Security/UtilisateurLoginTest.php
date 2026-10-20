<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Constant\GenreEtablissement;
use App\Entity\Dossier;
use App\Entity\DossierNatureListe;
use App\Entity\Etablissement;
use App\Entity\EtablissementInformations;
use App\Entity\Genre;
use App\Tests\Factory\UtilisateurFactory;
use App\Tests\Factory\UtilisateurPreferencesFactory;
use App\Tests\Story\NavigationAdminStory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\DataCollector\LoggerDataCollector;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Zenstruck\Foundry\Attribute\ResetDatabase;

#[ResetDatabase]
#[Group('database')]
final class UtilisateurLoginTest extends WebTestCase
{
    public function testAdminCanLogInAndOpenDashboardWithoutErrors(): void
    {
        $client = self::createClient();
        NavigationAdminStory::load();

        $this->submitLogin($client, NavigationAdminStory::admin()->getUsername());
        self::assertResponseRedirects('/accueil');
        $client->request('GET', '/');
        self::assertResponseRedirects('/accueil', 301);
        $this->assertDashboardWithoutErrors($client);

        $this->assertTrue(self::getContainer()->get(AuthorizationCheckerInterface::class)->isGranted('admin'));
    }

    public function testDefaultUserCanReadAndSearchButCannotWriteOrAdminister(): void
    {
        $client = self::createClient();
        $user = UtilisateurPreferencesFactory::createOne(['blocsDashboard' => '["feeds"]'])->getUtilisateur();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $authorization = self::getContainer()->get(AccessDecisionManagerInterface::class);
        $this->assertTrue($authorization->decide($token, ['search_ets']));
        $this->assertTrue($authorization->decide($token, ['search_dossiers']));
        foreach (['admin', 'add_etablissement', 'add_dossier', 'fil_actus', 'export_ets', 'export_doss'] as $privilege) {
            $this->assertFalse($authorization->decide($token, [$privilege]), $privilege);
        }

        // @todo: à modifier par les factories/stories dédiées une fois créées
        $etablissement = new Etablissement();
        $etablissement->addInformations(new EtablissementInformations()
            ->setGenre($entityManager->find(Genre::class, GenreEtablissement::ERP)));
        $dossier = new Dossier()
            ->setNature($entityManager->find(DossierNatureListe::class, 1))
            ->addEtablissement($etablissement);

        $this->assertTrue($authorization->decide($token, ['view_ets'], $etablissement));
        $this->assertTrue($authorization->decide($token, ['view_doss'], $dossier));
        $this->assertFalse($authorization->decide($token, ['edit_ets'], $etablissement));
        $this->assertFalse($authorization->decide($token, ['edit_doss'], $dossier));
        $this->assertFalse($authorization->decide($token, ['verrouillage_dossier'], $dossier));

        $this->submitLogin($client, $user->getUserIdentifier());
        self::assertResponseRedirects('/accueil');
        $this->assertDashboardWithoutErrors($client);
    }

    public function testInactiveUserCannotLogIn(): void
    {
        $client = self::createClient();
        $user = UtilisateurFactory::createOne(['actif' => false]);

        $this->submitLogin($client, $user->getUsername());
        self::assertResponseRedirects('/session/login');
        $client->request('GET', '/accueil');
        self::assertResponseRedirects('/session/login');
    }

    private function submitLogin(KernelBrowser $client, string $username): void
    {
        $crawler = $client->request('GET', '/session/login');
        self::assertResponseIsSuccessful();
        $client->submit($crawler->selectButton('Connexion')->form([
            '_username' => $username,
            '_password' => UtilisateurFactory::DEFAULT_PASSWORD,
        ]));
    }

    private function assertDashboardWithoutErrors(KernelBrowser $client): void
    {
        $client->enableProfiler();
        $client->request('GET', '/accueil');
        self::assertResponseIsSuccessful();
        $profile = $client->getProfile();
        $this->assertNotNull($profile);
        $logger = $profile->getCollector('logger');
        $this->assertInstanceOf(LoggerDataCollector::class, $logger);
        $this->assertSame(0, $logger->countErrors(), 'The dashboard must not hide failed counters behind an HTTP 200.');
    }
}
