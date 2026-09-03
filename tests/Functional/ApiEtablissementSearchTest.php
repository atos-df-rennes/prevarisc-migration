<?php

namespace App\Tests\Functional;

use App\Entity\Etablissement;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test fonctionnel de la route API commune de recherche d'établissements
 * (api_etablissements_search), utilisée par la recherche de la barre de navigation,
 * les liaisons de dossier et les établissements père/enfants.
 */
#[Group('functional')]
class ApiEtablissementSearchTest extends WebTestCase
{
    private const string ROUTE = '/api/etablissements/search';

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testSearchIsIgnoredBelowThreeCharacters(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request(Request::METHOD_GET, self::ROUTE, ['q' => 'ab']);

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $this->decodeResponse($client));
    }

    public function testSearchByLibelleReturnsNormalizedPayload(): void
    {
        $etablissement = $this->findEtablissement();
        $libelle = $etablissement->getLatestInformations()->getLibelle();

        if (null === $libelle || mb_strlen($libelle) < 3) {
            $this->markTestSkipped('Aucun établissement avec un libellé exploitable');
        }

        $client = $this->createAuthenticatedClient();

        $client->request(Request::METHOD_GET, self::ROUTE, ['q' => mb_substr($libelle, 0, 5)]);

        $this->assertResponseIsSuccessful();

        $results = $this->decodeResponse($client);

        $this->assertNotEmpty($results, 'La recherche par libellé devrait retourner au moins un résultat');
        $this->assertSame(
            ['id', 'numeroId', 'libelle', 'genre', 'genreId', 'commune'],
            array_keys($results[0])
        );
    }

    /**
     * Le préfixe « # » déclenche la recherche par identifiant (NUMEROID_ETABLISSEMENT),
     * comportement porté de Service_Search::etablissements() et absent de l'ancienne
     * implémentation basée sur searchByLibelle().
     */
    public function testSearchByIdentifiantWithHashPrefix(): void
    {
        $etablissement = $this->findEtablissement();
        $numeroId = $etablissement->getNumero();

        if (null === $numeroId || '' === $numeroId) {
            $this->markTestSkipped('Aucun établissement avec un identifiant exploitable');
        }

        $client = $this->createAuthenticatedClient();

        $client->request(Request::METHOD_GET, self::ROUTE, ['q' => '#'.$numeroId]);

        $this->assertResponseIsSuccessful();

        $results = $this->decodeResponse($client);

        $this->assertNotEmpty($results, 'La recherche par identifiant préfixé devrait retourner un résultat');

        foreach ($results as $result) {
            $this->assertSame($numeroId, $result['numeroId']);
        }
    }

    /**
     * Le paramètre legacy « label » reste accepté en alias de « q ».
     */
    public function testLegacyLabelParameterIsAccepted(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request(Request::METHOD_GET, self::ROUTE, ['label' => 'ab']);

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $this->decodeResponse($client));
    }

    public function testLimitIsForwardedAndCappedAtFifty(): void
    {
        $etablissement = $this->findEtablissement();
        $libelle = $etablissement->getLatestInformations()->getLibelle();

        if (null === $libelle || mb_strlen($libelle) < 3) {
            $this->markTestSkipped('Aucun établissement avec un libellé exploitable');
        }

        $prefix = mb_substr($libelle, 0, 3);

        $client = $this->createAuthenticatedClient();

        // La limite demandée est bien transmise au service de recherche
        $client->request(Request::METHOD_GET, self::ROUTE, ['q' => $prefix, 'limit' => 1]);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $this->decodeResponse($client));

        // Et elle reste bornée à 50 quelle que soit la valeur demandée
        $client->request(Request::METHOD_GET, self::ROUTE, ['q' => $prefix, 'limit' => 5000]);

        $this->assertResponseIsSuccessful();
        $this->assertLessThanOrEqual(50, count($this->decodeResponse($client)));
    }

    private function findEtablissement(): Etablissement
    {
        $etablissement = $this->entityManager
            ->getRepository(Etablissement::class)
            ->createQueryBuilder('e')
            ->where('e.dateSuppression IS NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$etablissement instanceof Etablissement) {
            $this->markTestSkipped('Aucun établissement disponible en base');
        }

        return $etablissement;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();

        $this->assertIsString($content);

        /** @var array<int, array<string, mixed>> $decoded */
        $decoded = json_decode($content, true);

        $this->assertIsArray($decoded);

        return $decoded;
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

        return static::createClient();
    }
}
