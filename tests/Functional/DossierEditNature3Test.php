<?php

namespace App\Tests\Functional;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test fonctionnel pour l'édition d'un dossier de nature 3 (Dérogation).
 */
#[Group('functional')]
class DossierEditNature3Test extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testFormDisplaysCorrectFieldsForNature3(): void
    {
        // Récupérer un dossier de nature 3 (Dérogation) depuis la base
        $dossier = $this->entityManager
            ->getRepository(Dossier::class)
            ->createQueryBuilder('d')
            ->join('d.nature', 'n')
            ->where('n.id = :nature')
            ->setParameter('nature', 3)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 3 (Dérogation) disponible');
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
            $this->assertStringContainsString($label, $content, sprintf("Le label '%s' devrait être présent dans le formulaire", $label));
        }

        // Vérifier les champs spécifiques aux dérogations
        $this->assertStringContainsString('Justification de la dérogation', $content, 'Le champ justificationDerogation devrait être présent');
        $this->assertStringContainsString('Mesures compensatoires', $content, 'Le champ mesuresCompensatoires devrait être présent');
        $this->assertStringContainsString('Mesures complémentaires', $content, 'Le champ mesuresComplementaires devrait être présent');
        $this->assertStringContainsString('Règles de dérogation', $content, 'Le champ reglesDerogation devrait être présent');

        // Vérifier les champs d'études
        $this->assertStringContainsString('documentsUrbanisme', $content, 'Le champ documentsUrbanisme devrait être présent');
        $this->assertStringContainsString('incomplet', $content, 'Le champ incomplet devrait être présent');
        $this->assertStringContainsString('horsDelai', $content, 'Le champ horsDelai devrait être présent');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
    }
}
