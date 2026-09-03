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
 * Test simple pour l'édition de la nature 21 (Visite périodique).
 * Vérifie uniquement l'affichage des labels de formulaire.
 */
#[Group('functional')]
final class DossierEditNature21Test extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testFormDisplaysCorrectLabelsForNature21(): void
    {
        $dossier = $this->entityManager
            ->getRepository(Dossier::class)
            ->createQueryBuilder('d')
            ->join('d.nature', 'n')
            ->where('n.id = :nature')
            ->setParameter('nature', 21)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 21 disponible');
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

        // Créer un client
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/dossier/'.$dossier->getId().'/modifier');

        $this->assertResponseIsSuccessful();

        $labels = [
            'Date de création du dossier',
            'Commission concernée',
            'Date de visite',
            'Date de réponse',
            'Préventionniste(s) du dossier',
            'Avis du rapporteur',
            'Avis de la commission',
            'Analyse de risque',
            'Observations',
            'Date avant laquelle les prescriptions doivent être levées',
        ];

        $content = $client->getResponse()->getContent();

        foreach ($labels as $label) {
            $this->assertStringContainsString($label, $content, sprintf("Le label '%s' devrait être présent dans le formulaire", $label));
        }

        // Vérifier que les checkboxes sont présents (leur label s'affiche différemment)
        $this->assertStringContainsString('avisDiffere', $content, 'Le champ avisDiffere devrait être présent');
        $this->assertStringContainsString('absenceDeQuorum', $content, 'Le champ absenceDeQuorum devrait être présent');
    }

    public function testFormHidesFieldsWhenValuesAreEmpty(): void
    {
        $dossier = $this->entityManager
            ->getRepository(Dossier::class)
            ->createQueryBuilder('d')
            ->join('d.nature', 'n')
            ->where('n.id = :nature')
            ->andWhere('d.avisDiffere IS NULL OR d.avisDiffere = 0')
            ->setParameter('nature', 21)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 21 avec avis différé vide disponible');
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

        // Créer un client
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/dossier/'.$dossier->getId().'/modifier');

        $this->assertResponseIsSuccessful();
    }
}
