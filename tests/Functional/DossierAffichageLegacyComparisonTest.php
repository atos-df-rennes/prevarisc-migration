<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Dossier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test de comparaison entre l'affichage Legacy et Symfony.
 *
 * Compare directement le HTML rendu par les deux applications
 * pour s'assurer que les mêmes informations sont affichées.
 */
class DossierAffichageLegacyComparisonTest extends WebTestCase
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
     * Test de comparaison Legacy vs Symfony pour visite périodique (nature 21).
     */
    public function testCompareVisitePeriodique(): void
    {
        $this->compareLegacyVsSymfony(21);
    }

    /**
     * Test de comparaison Legacy vs Symfony pour visite de contrôle (nature 28).
     */
    public function testCompareVisiteControle(): void
    {
        $this->compareLegacyVsSymfony(28);
    }

    /**
     * Test de comparaison Legacy vs Symfony pour visite inopinée (nature 29).
     */
    public function testCompareVisiteInopinee(): void
    {
        $this->compareLegacyVsSymfony(29);
    }

    /**
     * Compare l'affichage Legacy vs Symfony pour une nature donnée.
     */
    private function compareLegacyVsSymfony(int $natureId): void
    {
        // Récupérer un dossier de cette nature
        $dossier = $this->getDossierByNature($natureId);

        if (null === $dossier) {
            $this->markTestSkipped(sprintf('Aucun dossier de nature %d en base de données', $natureId));
        }

        $dossierId = $dossier->getId();

        // Récupérer le HTML de la page Legacy
        $legacyHtml = $this->fetchLegacyHtml($dossierId);
        $legacyData = $this->extractDisplayedData($legacyHtml);

        // Récupérer le HTML de la page Symfony
        $symfonyHtml = $this->fetchSymfonyHtml($dossierId);
        $symfonyData = $this->extractDisplayedData($symfonyHtml);

        // Comparer les données
        $this->compareData($legacyData, $symfonyData, $dossierId, $natureId);
    }

    /**
     * Récupère le HTML de la page Legacy.
     */
    private function fetchLegacyHtml(int $dossierId): string
    {
        $url = sprintf('http://localhost/dossier/index/id/%d', $dossierId);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=test'); // Session simulée
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (200 !== $httpCode) {
            $this->fail(sprintf('Legacy page returned HTTP %d for dossier %d', $httpCode, $dossierId));
        }

        return $html;
    }

    /**
     * Récupère le HTML de la page Symfony.
     */
    private function fetchSymfonyHtml(int $dossierId): string
    {
        if (null !== self::$kernel) {
            self::ensureKernelShutdown();
        }

        $client = static::createClient();

        // Simuler l'authentification
        $_SESSION['Zend_Auth'] = [
            'storage' => [
                'USERNAME_UTILISATEUR' => 'root',
            ],
        ];

        $client->request(Request::METHOD_GET, sprintf('/dossier/%d', $dossierId));

        if ($client->getResponse()->isRedirect()) {
            $this->fail(sprintf(
                'Symfony redirected to %s for dossier %d',
                $client->getResponse()->headers->get('Location'),
                $dossierId
            ));
        }

        $this->assertResponseIsSuccessful(sprintf(
            'Symfony page for dossier %d not accessible',
            $dossierId
        ));

        return $client->getResponse()->getContent();
    }

    /**
     * Extrait les données affichées depuis le HTML.
     *
     * Cherche tous les couples (libellé, valeur) dans la structure Bootstrap.
     */
    private function extractDisplayedData(string $html): array
    {
        $crawler = new Crawler($html);
        $data = [];

        // Structure Legacy et Symfony : <li class='row'> avec .libelle et .value
        $rows = $crawler->filter('.list-unstyled li.row, ul.list-unstyled li');

        foreach ($rows as $row) {
            $rowCrawler = new Crawler($row);

            // Extraire le libellé
            $labelNode = $rowCrawler->filter('.libelle, .left.libelle');
            if (0 === $labelNode->count()) {
                continue;
            }

            $label = trim($labelNode->text());

            // Extraire la valeur
            $valueNode = $rowCrawler->filter('.value, .right.value, .form-control-static');
            if (0 === $valueNode->count()) {
                continue;
            }

            $value = trim($valueNode->text());

            // Nettoyer les espaces multiples et sauts de ligne
            $value = preg_replace('/\s+/', ' ', $value);
            $value = trim($value);

            $data[$label] = $value;
        }

        return $data;
    }

    /**
     * Compare les données Legacy vs Symfony.
     */
    private function compareData(array $legacyData, array $symfonyData, int $dossierId, int $natureId): void
    {
        $errors = [];
        $warnings = [];

        // Vérifier que tous les champs Legacy sont présents dans Symfony
        foreach ($legacyData as $label => $legacyValue) {
            if (!isset($symfonyData[$label])) {
                $errors[] = sprintf(
                    '❌ Champ "%s" : présent dans Legacy ("%s") mais ABSENT dans Symfony',
                    $label,
                    $legacyValue
                );
                continue;
            }

            $symfonyValue = $symfonyData[$label];

            // Comparer les valeurs (en ignorant les espaces)
            if ($legacyValue !== $symfonyValue) {
                $warnings[] = sprintf(
                    '⚠️  Champ "%s" : valeurs différentes'."\n".
                    '    Legacy:  "%s"'."\n".
                    '    Symfony: "%s"',
                    $label,
                    $legacyValue,
                    $symfonyValue
                );
            }
        }

        // Vérifier qu'il n'y a pas de champs en trop dans Symfony
        foreach ($symfonyData as $label => $symfonyValue) {
            if (!isset($legacyData[$label])) {
                $warnings[] = sprintf(
                    '⚠️  Champ "%s" : présent dans Symfony ("%s") mais ABSENT dans Legacy',
                    $label,
                    $symfonyValue
                );
            }
        }

        // Afficher le rapport
        $this->displayReport($dossierId, $natureId, count($legacyData), count($symfonyData), $errors, $warnings);

        // Le test échoue uniquement si des erreurs critiques sont détectées
        $this->assertEmpty($errors, sprintf(
            "❌ ÉCHEC: %d erreur(s) critique(s) pour le dossier #%d (nature %d)\n%s",
            count($errors),
            $dossierId,
            $natureId,
            implode("\n", $errors)
        ));
    }

    /**
     * Affiche un rapport de comparaison.
     */
    private function displayReport(
        int $dossierId,
        int $natureId,
        int $legacyCount,
        int $symfonyCount,
        array $errors,
        array $warnings
    ): void {
        echo "\n";
        echo str_repeat('=', 64)."\n";
        echo sprintf('📊 COMPARAISON Legacy vs Symfony - Dossier #%d (Nature %d)', $dossierId, $natureId)."\n";
        echo str_repeat('=', 64)."\n";
        echo "\n";
        echo sprintf('📋 Champs Legacy:  %d', $legacyCount)."\n";
        echo sprintf('📋 Champs Symfony: %d', $symfonyCount)."\n";
        echo sprintf('❌ Erreurs critiques: %d', count($errors))."\n";
        echo sprintf('⚠️  Avertissements: %d', count($warnings))."\n";
        echo "\n";

        if ([] !== $errors) {
            echo "--- ERREURS CRITIQUES ---\n";
            foreach ($errors as $error) {
                echo $error."\n";
            }

            echo "\n";
        }

        if ([] !== $warnings) {
            echo "--- AVERTISSEMENTS ---\n";
            foreach ($warnings as $warning) {
                echo $warning."\n";
            }

            echo "\n";
        }

        if ([] === $errors && [] === $warnings) {
            echo "✅ PARFAIT ! L'affichage Symfony est identique au Legacy.\n";
        }

        echo str_repeat('=', 64)."\n";
        echo "\n";
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
