<?php

namespace App\Tests\Functional;

use App\Entity\Dossier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test fonctionnel pour l'édition d'un dossier de nature 7 (RVRMD diag sécu => Levée de prescriptions).

 *
 * @group functional
 */
class DossierEditNature7Test extends WebTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testFormDisplaysCorrectFieldsForNature7(): void
    {
        // Récupérer un dossier de nature 7 (RVRMD diag sécu => Levée de prescriptions) depuis la base
        $dossier = $this->entityManager
            ->getRepository(Dossier::class)
            ->createQueryBuilder('d')
            ->join('d.nature', 'n')
            ->where('n.id = :nature')
            ->setParameter('nature', 7)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 7 (RVRMD diag sécu => Levée de prescriptions) disponible');
        }

        // Récupérer l'utilisateur root
        $utilisateur = $this->entityManager
            ->getRepository('App:Utilisateur')
            ->findOneBy(['username' => 'root']);

        if (!$utilisateur) {
            $this->markTestSkipped('Utilisateur root non trouvé');
        }

        // Simuler l'authentification Zend dans $_SESSION
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }

        $_SESSION['Zend_Auth'] = [
            'storage' => [
                'USERNAME_UTILISATEUR' => 'root',
            ],
        ];

        self::ensureKernelShutdown();

        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/dossier/'.$dossier->getId().'/modifier');

        $this->assertResponseIsSuccessful();

        // Vérifier les labels principaux
        $labels = [
            'Date de création du dossier',
            'Objet',
            'Commission concernée',
            'Date de commission',
            'Date de réception SDIS', // Nature 7 utilise dateReception (DATESDIS) et non dateReponse (DATEREP)
            'Préventionniste(s)',
            'Avis de la commission',
            'Observations',
        ];

        $content = $client->getResponse()->getContent();

        foreach ($labels as $label) {
            $this->assertStringContainsString($label, $content, sprintf("Le label '%s' devrait être présent dans le formulaire", $label));
        }

        // Vérifier que les champs incomplet et horsDelai sont présents (champs d'études)
        $this->assertStringContainsString('incomplet', $content, 'Le champ incomplet devrait être présent');
        $this->assertStringContainsString('horsDelai', $content, 'Le champ horsDelai devrait être présent');

        // Vérifier que dateReception et chargeSecurite sont présents dans le HTML (masqués par JS)
        $this->assertStringContainsString('dossier[dateReception]', $content, 'Le champ dateReception devrait être dans le HTML');
        $this->assertStringContainsString('dossier[chargeSecurite]', $content, 'Le champ chargeSecurite devrait être dans le HTML');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
    }
}
