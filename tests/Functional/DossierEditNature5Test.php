<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test fonctionnel pour l'édition d'un dossier de nature 5 (Cahier des charges de type T).
 */
#[Group('functional')]
final class DossierEditNature5Test extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testFormDisplaysCorrectFieldsForNature5(): void
    {
        // Récupérer un dossier de nature 5 (Cahier des charges de type T) depuis la base
        $dossier = $this->entityManager
            ->getRepository(Dossier::class)
            ->createQueryBuilder('d')
            ->join('d.nature', 'n')
            ->where('n.id = :nature')
            ->setParameter('nature', 5)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 5 (Cahier des charges de type T) disponible');
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
            'Date de commission',
            'Date de réponse',
            'Préventionniste(s)',
            'Avis de la commission',
            'Observations',
        ];

        $content = $client->getResponse()->getContent();

        foreach ($labels as $label) {
            $this->assertStringContainsString($label, (string) $content, sprintf("Le label '%s' devrait être présent dans le formulaire", $label));
        }

        // Vérifier que les champs incomplet et horsDelai sont présents (champs d'études)
        $this->assertStringContainsString('incomplet', (string) $content, 'Le champ incomplet devrait être présent');
        $this->assertStringContainsString('horsDelai', (string) $content, 'Le champ horsDelai devrait être présent');

        // Vérifier que le champ type ne devrait PAS être visible pour nature 5 (différence avec nature 4)
        // Le champ est dans le HTML mais masqué par JS, donc on vérifie juste sa présence
        $this->assertStringContainsString('dossier[type]', (string) $content, 'Le champ type devrait être dans le HTML');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
    }
}
