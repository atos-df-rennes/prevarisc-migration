<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Dossier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour l'édition des dossiers de type Visites.
 *
 * Approche TDD : Tests écrits AVANT l'implémentation.
 */
class DossierEditVisitesTest extends WebTestCase
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
     * Test d'édition pour nature 21 - Visite périodique.
     *
     * Champs attendus (legacy) :
     * DATEINSERT, COMMISSION, DESCGEN, DESCEFF, DATEVISITE, DATEREP,
     * PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM, AVIS, AVIS_COMMISSION,
     * OBSERVATION, DELAIPRESC
     */
    public function testEditVisitePeriodique(): void
    {
        // Récupérer un dossier de nature 21
        $dossier = $this->getDossierByNature(21);

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 21 en base de données');
        }

        $dossierId = $dossier->getId();

        // Créer client authentifié
        $client = $this->createAuthenticatedClient();

        // GET : Afficher le formulaire d'édition
        $crawler = $client->request('GET', sprintf('/dossier/%d/modifier', $dossierId));

        $this->assertResponseIsSuccessful(sprintf(
            'La page d\'édition du dossier %d n\'est pas accessible',
            $dossierId
        ));

        // Vérifier que c'est bien un formulaire
        $form = $crawler->filter('form[name="dossier"]');
        $this->assertCount(1, $form, 'Le formulaire "dossier" doit être présent');

        // Vérifier la présence des champs spécifiques à la nature 21
        $expectedFields = [
            'dossier[commission]' => 'Commission',
            'dossier[descriptifGeneral]' => 'Descriptif général',
            'dossier[descriptifEffectifs]' => 'Descriptif effectif',
            'dossier[dateVisite]' => 'Date de visite',
            'dossier[dateReponse]' => 'Date de réponse',
            'dossier[avisDiffere]' => 'Avis différé',
            'dossier[absenceDeQuorum]' => 'Absence de quorum',
            'dossier[avis]' => 'Avis du rapporteur',
            'dossier[avisCommission]' => 'Avis de la commission',
            'dossier[observations]' => 'Observations',
            'dossier[dateLimitePrescription]' => 'Délai levée prescriptions',
        ];

        foreach ($expectedFields as $fieldName => $label) {
            $field = $crawler->filter(sprintf('[name="%s"]', $fieldName));
            $this->assertGreaterThan(
                0,
                $field->count(),
                sprintf('Le champ "%s" (%s) doit être présent dans le formulaire', $fieldName, $label)
            );
        }

        // Vérifier que les champs sont pré-remplis avec les valeurs actuelles
        if ($dossier->getCommission()) {
            $commissionField = $crawler->filter('select[name="dossier[commission]"] option[selected]');
            $this->assertGreaterThan(
                0,
                $commissionField->count(),
                'Le champ commission doit être pré-rempli'
            );
        }

        if ($dossier->getDescriptifGeneral()) {
            $descriptifField = $crawler->filter('textarea[name="dossier[descriptifGeneral]"]');
            $this->assertStringContainsString(
                $dossier->getDescriptifGeneral(),
                $descriptifField->text(),
                'Le champ descriptifGeneral doit être pré-rempli'
            );
        }

        // Tester la soumission du formulaire
        $form = $crawler->selectButton('Enregistrer')->form();

        // Modifier une valeur
        $newObservation = 'Test automatisé - '.time();
        $form['dossier[observations]'] = $newObservation;

        // Soumettre le formulaire
        $client->submit($form);

        // Vérifier la redirection
        $this->assertResponseRedirects(
            sprintf('/dossier/%d', $dossierId),
            302,
            'Après sauvegarde, doit rediriger vers la page d\'affichage du dossier'
        );

        // Suivre la redirection
        $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Vérifier que les données sont bien enregistrées en base
        $this->entityManager->clear(); // Clear pour forcer un reload
        $dossierUpdated = $this->entityManager->getRepository(Dossier::class)->find($dossierId);

        $this->assertNotNull($dossierUpdated);
        $this->assertSame(
            $newObservation,
            $dossierUpdated->getObservations(),
            'Les observations doivent être sauvegardées en base'
        );

        // Afficher un rapport de succès
        echo "\n";
        echo str_repeat('=', 64)."\n";
        echo sprintf('✅ Test ÉDITION Nature 21 - Dossier #%d', $dossierId)."\n";
        echo str_repeat('=', 64)."\n";
        echo sprintf("📋 Champs testés : %d\n", count($expectedFields));
        echo "✅ Formulaire affiché\n";
        echo "✅ Tous les champs présents\n";
        echo "✅ Soumission réussie\n";
        echo "✅ Données persistées\n";
        echo str_repeat('=', 64)."\n";
        echo "\n";
    }

    /**
     * Crée un client authentifié.
     */
    private function createAuthenticatedClient()
    {
        if (null !== self::$kernel) {
            self::ensureKernelShutdown();
        }

        $client = static::createClient();

        // Simuler l'authentification Zend
        $_SESSION['Zend_Auth'] = [
            'storage' => [
                'USERNAME_UTILISATEUR' => 'root',
            ],
        ];

        return $client;
    }

    /**
     * Récupère un dossier par sa nature.
     */
    private function getDossierByNature(int $natureId): ?Dossier
    {
        return $this->entityManager
            ->getRepository(Dossier::class)
            ->createQueryBuilder('d')
            ->join('d.nature', 'n')
            ->where('n.id = :natureId')
            ->setParameter('natureId', $natureId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
