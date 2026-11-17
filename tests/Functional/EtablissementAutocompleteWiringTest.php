<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Dossier;
use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Vérifie que les trois écrans d'autocomplétion d'établissement sont branchés sur la
 * route API commune (api_etablissements_search) via le module TomSelect partagé, et
 * qu'aucun ne dépend plus de la route legacy /api/1.0/search/etablissements (supprimée
 * avec le strangler en 3.0).
 */
#[Group('functional')]
final class EtablissementAutocompleteWiringTest extends WebTestCase
{
    private const string LEGACY_ROUTE = '/api/1.0/search/etablissements';

    private const string API_ROUTE = '/api/etablissements/search';

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testNavbarSearchUsesCommonApiRoute(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request(Request::METHOD_GET, '/accueil');

        $this->assertResponseIsSuccessful();

        $content = $this->getContent($client);

        $this->assertStringContainsString('nav-main-search-field', $content);
        $this->assertStringContainsString(self::API_ROUTE, $content);
        $this->assertStringNotContainsString(self::LEGACY_ROUTE, $content);
    }

    public function testEtablissementFormUsesSharedModule(): void
    {
        /** @var Etablissement|null $etablissement */
        $etablissement = $this->entityManager
            ->getRepository(Etablissement::class)
            ->createQueryBuilder('e')
            ->where('e.dateSuppression IS NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $etablissement) {
            $this->markTestSkipped('Aucun établissement disponible en base');
        }

        $client = $this->createAuthenticatedClient();

        $client->request(Request::METHOD_GET, '/etablissement/'.$etablissement->getId().'/modifier');

        $this->assertResponseIsSuccessful();

        $content = $this->getContent($client);

        // Autocomplétions père et enfants
        $this->assertStringContainsString('pere_autocomplete', $content);
        $this->assertStringContainsString('etablissement_autocomplete', $content);
        $this->assertStringContainsString('initTomSelectEtablissement', $content);
        $this->assertStringContainsString(self::API_ROUTE, $content);
        $this->assertStringNotContainsString(self::LEGACY_ROUTE, $content);
    }

    public function testDossierLiaisonsUsesSharedModule(): void
    {
        /** @var Dossier|null $dossier */
        $dossier = $this->entityManager
            ->getRepository(Dossier::class)
            ->createQueryBuilder('d')
            ->where('d.dateSuppression IS NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier disponible en base');
        }

        $client = $this->createAuthenticatedClient();

        $client->request(Request::METHOD_GET, '/dossier/'.$dossier->getId().'/liees');

        $this->assertResponseIsSuccessful();

        $content = $this->getContent($client);

        $this->assertStringContainsString('initTomSelectEtablissement', $content);
        $this->assertStringContainsString(self::API_ROUTE, $content);
        $this->assertStringNotContainsString(self::LEGACY_ROUTE, $content);
    }

    private function getContent(KernelBrowser $client): string
    {
        $content = $client->getResponse()->getContent();

        $this->assertIsString($content);

        return $content;
    }

    private function createAuthenticatedClient(): KernelBrowser
    {
        $utilisateur = $this->entityManager
            ->getRepository(Utilisateur::class)
            ->findOneBy(['username' => 'root'])
        ;

        if (null === $utilisateur) {
            $this->markTestSkipped('Utilisateur root non trouvé');
        }

        self::ensureKernelShutdown();

        $client = static::createClient();
        $client->loginUser($utilisateur);

        return $client;
    }
}
