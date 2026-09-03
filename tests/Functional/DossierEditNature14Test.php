<?php

namespace App\Tests\Functional;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test fonctionnel pour l'édition d'un dossier de nature 14 (Permis d'aménager).
 */
#[Group('functional')]
class DossierEditNature14Test extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testFormDisplaysCorrectFieldsForNature14(): void
    {
        // Récupérer un dossier de nature 14 (Modification CTS) depuis la base
        $dossier = $this->entityManager
            ->getRepository(Dossier::class)
            ->createQueryBuilder('d')
            ->join('d.nature', 'n')
            ->where('n.id = :nature')
            ->setParameter('nature', 14)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 14 (Permis d\'aménager) disponible');
        }

        // Récupérer l'utilisateur root
        $utilisateur = $this->entityManager
            ->getRepository(Utilisateur::class)
            ->findOneBy(['username' => 'root']);

        if (!$utilisateur) {
            $this->markTestSkipped('Utilisateur root non trouvé');
        }

        $_SESSION ??= [];

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
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
    }
}
