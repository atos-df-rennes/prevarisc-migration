<?php

namespace App\Tests\Functional;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test fonctionnel pour l'édition d'un dossier de nature 63 (Demande organisation manifestation).
 */
#[Group('functional')]
class DossierEditNature63Test extends WebTestCase
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

    public function testFormDisplaysCorrectFieldsForNature63(): void
    {
        $dossier = $this->entityManager
            ->getRepository(Dossier::class)
            ->createQueryBuilder('d')
            ->join('d.nature', 'n')
            ->where('n.id = :nature')
            ->setParameter('nature', 63)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 63 (Demande organisation manifestation) disponible');
        }

        $utilisateur = $this->entityManager
            ->getRepository(Utilisateur::class)
            ->findOneBy(['username' => 'root']);

        if (!$utilisateur) {
            $this->markTestSkipped('Utilisateur root non trouvé');
        }

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

        $labels = [
            'Date de création du dossier',
            'Objet',
            'Préventionniste(s)',
            'Observations',
        ];

        $content = $client->getResponse()->getContent();

        foreach ($labels as $label) {
            $this->assertStringContainsString($label, $content, sprintf("Le label '%s' devrait être présent", $label));
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}
