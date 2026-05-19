<?php

namespace App\Tests\Functional\Etablissement;

use App\Entity\Etablissement;
use App\Entity\Genre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests fonctionnels pour l'édition d'un établissement.
 *
 * @group functional
 */
final class EtablissementEditTest extends WebTestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Vérifie que la page d'édition s'affiche correctement (GET).
     */
    public function testEditPageDisplaysForm(): void
    {
        $etablissement = $this->findEtablissementWithInformations();
        if (null === $etablissement) {
            $this->markTestSkipped('Aucun établissement avec informations disponible');
        }

        $this->authenticateAsRoot();

        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/etablissement/'.$etablissement->getId().'/modifier');

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();

        // Le formulaire est présent avec l'action correcte
        $this->assertStringContainsString(
            '/etablissement/'.$etablissement->getId().'/modifier',
            $content,
            'Le formulaire doit avoir une action pointant vers la route de modification'
        );

        // Le token CSRF est présent
        $this->assertStringContainsString(
            'name="_token"',
            $content,
            'Le token CSRF doit être présent dans le formulaire'
        );

        // Les champs principaux sont présents
        $this->assertStringContainsString('ID_GENRE', $content, 'Le champ genre doit être présent');
        $this->assertStringContainsString('LIBELLE_ETABLISSEMENTINFORMATIONS', $content, 'Le champ libellé doit être présent');
    }

    /**
     * Vérifie que le formulaire pré-remplit les données existantes.
     */
    public function testEditPagePrefillsExistingData(): void
    {
        $etablissement = $this->findEtablissementWithInformations();
        if (null === $etablissement) {
            $this->markTestSkipped('Aucun établissement avec informations disponible');
        }

        $this->authenticateAsRoot();

        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/etablissement/'.$etablissement->getId().'/modifier');

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();

        // Vérifie que le libellé de l'établissement est pré-rempli
        $latestInfo = $etablissement->getLatestInformations();
        $libelle = $latestInfo->getLibelle();
        if (null !== $libelle && '' !== $libelle) {
            $this->assertStringContainsString(
                htmlspecialchars($libelle, ENT_QUOTES),
                $content,
                'Le libellé existant doit être pré-rempli dans le formulaire'
            );
        }
    }

    /**
     * Vérifie qu'un POST sans token CSRF est rejeté.
     */
    public function testEditPostWithInvalidCsrfTokenFails(): void
    {
        $etablissement = $this->findEtablissementWithInformations();
        if (null === $etablissement) {
            $this->markTestSkipped('Aucun établissement avec informations disponible');
        }

        $this->authenticateAsRoot();

        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(Request::METHOD_POST, '/etablissement/'.$etablissement->getId().'/modifier', [
            '_token' => 'invalid_token',
            'ID_GENRE' => '2',
            'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Test CSRF',
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString(
            'Token CSRF invalide',
            $content,
            'Un message d\'erreur CSRF doit être affiché'
        );
    }

    /**
     * Vérifie que l'édition avec un POST valide redirige correctement.
     */
    public function testEditPostWithValidDataRedirects(): void
    {
        $etablissement = $this->findEtablissementWithInformations();
        if (null === $etablissement) {
            $this->markTestSkipped('Aucun établissement avec informations disponible');
        }

        $this->authenticateAsRoot();

        self::ensureKernelShutdown();
        $client = static::createClient();

        // Récupérer un token CSRF valide
        $client->request(Request::METHOD_GET, '/etablissement/'.$etablissement->getId().'/modifier');

        $content = $client->getResponse()->getContent();

        // Extraire le token CSRF du formulaire
        preg_match('/name="_token"\s+value="([^"]+)"/', $content, $matches);
        if (empty($matches[1])) {
            $this->markTestSkipped("Impossible d'extraire le token CSRF");
        }

        $csrfToken = $matches[1];
        $latestInfo = $etablissement->getLatestInformations();
        $genre = $latestInfo ? $latestInfo->getGenre() : null;
        $idGenre = $genre instanceof Genre ? (string) $genre->getId() : '2';

        $client->request(Request::METHOD_POST, '/etablissement/'.$etablissement->getId().'/modifier', [
            '_token' => $csrfToken,
            'ID_GENRE' => $idGenre,
            'LIBELLE_ETABLISSEMENTINFORMATIONS' => 'Test édition fonctionnel',
        ]);

        // La réponse doit être une redirection (302) vers la page informations
        $this->assertResponseRedirects(
            '/etablissement/'.$etablissement->getId().'/informations'
        );
    }

    /**
     * Vérifie que l'accès sans authentification est refusé.
     */
    public function testEditPageDeniesUnauthenticatedAccess(): void
    {
        $etablissement = $this->findEtablissementWithInformations();
        if (null === $etablissement) {
            $this->markTestSkipped('Aucun établissement avec informations disponible');
        }

        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/etablissement/'.$etablissement->getId().'/modifier');

        // Doit être redirigé vers la page de login ou retourner 403
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [302, 401, 403], true),
            'L\'accès non authentifié doit être refusé (attendu 302, 401 ou 403, obtenu '.$statusCode.')'
        );
    }

    /**
     * Trouve un établissement avec des informations pour les tests.
     */
    private function findEtablissementWithInformations(): ?Etablissement
    {
        return $this->entityManager
            ->getRepository(Etablissement::class)
            ->createQueryBuilder('e')
            ->join('e.informations', 'i')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Simule l'authentification de l'utilisateur root via la session Zend.
     */
    private function authenticateAsRoot(): void
    {
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }

        $_SESSION['Zend_Auth'] = [
            'storage' => [
                'USERNAME_UTILISATEUR' => 'root',
            ],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
    }
}
