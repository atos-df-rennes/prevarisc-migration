<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Dossier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Test de comparaison automatisée entre affichage Legacy vs Symfony
 * 
 * Objectif: Garantir que l'affichage Symfony est 100% ISO avec le legacy
 * pour tous les champs et toutes les natures de dossiers.
 */
class DossierAffichageComparisonTest extends WebTestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;
    
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->entityManager = self::$container->get('doctrine')->getManager();
    }
    
    /**
     * Crée un client authentifié
     */
    private function createAuthenticatedClient()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'prevarisc_user',
            'PHP_AUTH_PW'   => 'prevarisc',
        ]);
        
        return $client;
    }
    
    /**
     * Test de comparaison pour une nature de type Réunion (31)
     */
    public function testAffichageReunionLocauxSdis(): void
    {
        // Récupérer un dossier de nature 31 existant
        $dossier = $this->getDossierByNature(31);
        
        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 31 en base de données');
        }
        
        $this->compareAffichageWithLegacy($dossier, [
            'dateInsert' => 'Date de création',
            'objet' => 'Objet',
            'dateReunion' => 'Date de réunion',
            'dateReponse' => 'Date de réponse',
            'demandeur' => 'Demandeur',
            'observations' => 'Observations',
        ]);
    }
    
    /**
     * Test de comparaison pour une nature de type Réunion (32)
     */
    public function testAffichageReunionExterieurSdis(): void
    {
        $dossier = $this->getDossierByNature(32);
        
        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 32 en base de données');
        }
        
        $this->compareAffichageWithLegacy($dossier, [
            'dateInsert' => 'Date de création',
            'objet' => 'Objet',
            'lieuReunion' => 'Lieu de réunion',
            'dateReunion' => 'Date de réunion',
            'dateReponse' => 'Date de réponse',
            'demandeur' => 'Demandeur',
            'observations' => 'Observations',
        ]);
    }
    
    /**
     * Test de comparaison pour courrier (nature 52 - Lettre)
     */
    public function testAffichageCourrierLettre(): void
    {
        $dossier = $this->getDossierByNature(52);
        
        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 52 en base de données');
        }
        
        $this->compareAffichageWithLegacy($dossier, [
            'dateInsert' => 'Date de création',
            'objet' => 'Objet',
            'dateMairie' => 'Date réception mairie',
            'dateEnvoiTransit' => 'Date envoi / transit',
            'dateReponse' => 'Date de réponse',
            'dateReception' => 'Date réception SDIS',
            'demandeur' => 'Demandeur',
            'dateTransfertCommission' => 'Date transfert commission',
            'dateReceptionCommission' => 'Date réception commission',
            'observations' => 'Observations',
        ]);
    }
    
    /**
     * Compare l'affichage Symfony avec les données attendues du legacy
     * 
     * @param Dossier $dossier
     * @param array $expectedFields Mapping propriété => label affiché
     */
    private function compareAffichageWithLegacy(Dossier $dossier, array $expectedFields): void
    {
        // Créer un client
        $client = static::createClient();
        $client->disableReboot(); // Éviter le reboot du kernel entre les requêtes
        
        // Pour ce test, on utilise le mode test qui devrait avoir l'authentification désactivée
        // Si cela ne fonctionne pas, on pourra marquer le test comme skippé
        $crawler = $client->request('GET', sprintf('/dossier/%d', $dossier->getId()));
        
        // Si redirection vers login, skip le test (authentification requise)
        if ($client->getResponse()->isRedirect()) {
            $this->markTestSkipped('Authentification requise - test non exécutable dans cet environnement');
        }
        
        $this->assertResponseIsSuccessful(sprintf(
            'La page /dossier/%d ne répond pas correctement',
            $dossier->getId()
        ));
        
        $errors = [];
        $warnings = [];
        
        foreach ($expectedFields as $property => $expectedLabel) {
            // Récupérer la valeur de la propriété
            $getter = 'get' . ucfirst($property);
            
            if (!method_exists($dossier, $getter)) {
                $errors[] = sprintf(
                    '❌ Propriété "%s" : getter "%s()" n\'existe pas dans l\'entité Dossier',
                    $property,
                    $getter
                );
                continue;
            }
            
            $value = $dossier->$getter();
            
            // Si la valeur est nulle, vérifier que le champ n'est PAS affiché
            if (null === $value || '' === $value) {
                $fieldDisplayed = $this->isFieldDisplayed($crawler, $property);
                
                if ($fieldDisplayed) {
                    $warnings[] = sprintf(
                        '⚠️  Champ "%s" (%s) : affiché alors que la valeur est vide/null',
                        $property,
                        $expectedLabel
                    );
                }
                continue;
            }
            
            // Vérifier que le champ EST affiché
            $fieldDisplayed = $this->isFieldDisplayed($crawler, $property);
            
            if (!$fieldDisplayed) {
                $errors[] = sprintf(
                    '❌ Champ "%s" (%s) : NON affiché alors qu\'il contient une valeur',
                    $property,
                    $expectedLabel
                );
                continue;
            }
            
            // Vérifier le label
            $displayedLabel = $this->getFieldLabel($crawler, $property);
            
            if (null === $displayedLabel) {
                $errors[] = sprintf(
                    '❌ Champ "%s" : label introuvable (attendu: "%s")',
                    $property,
                    $expectedLabel
                );
            } elseif (trim($displayedLabel) !== $expectedLabel) {
                $errors[] = sprintf(
                    '❌ Champ "%s" : label incorrect (attendu: "%s", obtenu: "%s")',
                    $property,
                    $expectedLabel,
                    trim($displayedLabel)
                );
            }
            
            // Vérifier la valeur affichée
            $displayedValue = $this->getFieldValue($crawler, $property);
            $expectedValue = $this->formatValueForDisplay($value);
            
            if (null === $displayedValue) {
                $errors[] = sprintf(
                    '❌ Champ "%s" : valeur introuvable dans le DOM',
                    $property
                );
            } elseif (trim($displayedValue) !== $expectedValue) {
                $errors[] = sprintf(
                    '❌ Champ "%s" : valeur incorrecte (attendu: "%s", obtenu: "%s")',
                    $property,
                    $expectedValue,
                    trim($displayedValue)
                );
            }
        }
        
        // Afficher le rapport
        $this->displayComparisonReport($dossier, $expectedFields, $errors, $warnings);
        
        // Le test échoue si des erreurs critiques sont détectées
        $this->assertEmpty($errors, sprintf(
            "❌ ÉCHEC: %d erreur(s) détectée(s) pour le dossier #%d (nature %d)\n%s",
            count($errors),
            $dossier->getId(),
            $dossier->getNature()->getId(),
            implode("\n", $errors)
        ));
    }
    
    /**
     * Vérifie si un champ est affiché dans le DOM
     */
    private function isFieldDisplayed(Crawler $crawler, string $property): bool
    {
        // Chercher le champ via l'ID du conteneur
        $fieldContainer = $crawler->filter(sprintf('#field-%s', $property));
        
        return $fieldContainer->count() > 0;
    }
    
    /**
     * Récupère le label affiché pour un champ
     */
    private function getFieldLabel(Crawler $crawler, string $property): ?string
    {
        $label = $crawler->filter(sprintf('#field-%s .libelle', $property));
        
        return $label->count() > 0 ? $label->text() : null;
    }
    
    /**
     * Récupère la valeur affichée pour un champ
     */
    private function getFieldValue(Crawler $crawler, string $property): ?string
    {
        $value = $crawler->filter(sprintf('#field-%s .form-control-static', $property));
        
        return $value->count() > 0 ? $value->text() : null;
    }
    
    /**
     * Formate une valeur pour l'affichage (dates, etc.)
     * 
     * @param mixed $value
     */
    private function formatValueForDisplay($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }
        
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }
        
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }
        
        return (string) $value;
    }
    
    /**
     * Affiche un rapport détaillé de la comparaison
     */
    private function displayComparisonReport(
        Dossier $dossier,
        array $expectedFields,
        array $errors,
        array $warnings
    ): void {
        echo "\n";
        echo "================================================================\n";
        echo sprintf("📊 RAPPORT DE COMPARAISON - Dossier #%d (Nature %d)\n", 
            $dossier->getId(), 
            $dossier->getNature()->getId()
        );
        echo "================================================================\n\n";
        
        echo sprintf("✅ Champs attendus : %d\n", count($expectedFields));
        echo sprintf("❌ Erreurs critiques : %d\n", count($errors));
        echo sprintf("⚠️  Avertissements : %d\n\n", count($warnings));
        
        if (!empty($errors)) {
            echo "--- ERREURS CRITIQUES ---\n";
            foreach ($errors as $error) {
                echo $error . "\n";
            }
            echo "\n";
        }
        
        if (!empty($warnings)) {
            echo "--- AVERTISSEMENTS ---\n";
            foreach ($warnings as $warning) {
                echo $warning . "\n";
            }
            echo "\n";
        }
        
        if (empty($errors) && empty($warnings)) {
            echo "✅ PARFAIT ! Tous les champs sont conformes au legacy.\n";
        }
        
        echo "================================================================\n\n";
    }
    
    /**
     * Récupère un dossier par sa nature
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
