<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Test de comparaison automatisée entre affichage Legacy vs Symfony.
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
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Crée un client authentifié avec le compte root/root.
     */
    private function createAuthenticatedClient()
    {
        // S'assurer que le kernel est arrêté avant de créer un client
        if (null !== self::$kernel) {
            self::ensureKernelShutdown();
        }

        $client = static::createClient();

        // Récupérer l'utilisateur root depuis la base de données
        $user = $this->entityManager
            ->getRepository(Utilisateur::class)
            ->findOneBy(['username' => 'root']);

        if (null === $user) {
            throw new \RuntimeException('Utilisateur root introuvable en base de données');
        }

        // Simuler la session Zend_Auth directement dans $_SESSION
        // pour que LegacySessionAuthenticator fonctionne
        $_SESSION['Zend_Auth'] = [
            'storage' => [
                'USERNAME_UTILISATEUR' => 'root'
            ]
        ];

        return $client;
    }

    /**
     * Test de comparaison pour une nature de type Réunion (31).
     */
    public function testAffichageReunionLocauxSdis(): void
    {
        // Récupérer un dossier de nature 31 existant
        $dossier = $this->getDossierByNature(31);

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 31 en base de données');
        }

        $this->compareAffichageWithLegacy($dossier, [
            'dateInsert' => 'Date de création du dossier',
            'objet' => 'Objet',
            'dateReunion' => 'Date réunion',
            'dateReponse' => 'Date de réponse',
            'demandeur' => 'Demandeur',
            'observations' => 'Observations',
        ]);
    }

    /**
     * Test de comparaison pour une nature de type Réunion (32).
     */
    public function testAffichageReunionExterieurSdis(): void
    {
        $dossier = $this->getDossierByNature(32);

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 32 en base de données');
        }

        $this->compareAffichageWithLegacy($dossier, [
            'dateInsert' => 'Date de création du dossier',
            'objet' => 'Objet',
            'lieuReunion' => 'Lieu réunion',
            'dateReunion' => 'Date réunion',
            'dateReponse' => 'Date de réponse',
            'demandeur' => 'Demandeur',
            'observations' => 'Observations',
        ]);
    }

    /**
     * Test de comparaison pour courrier (nature 52 - Lettre).
     */
    public function testAffichageCourrierLettre(): void
    {
        $dossier = $this->getDossierByNature(52);

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 52 en base de données');
        }

        $this->compareAffichageWithLegacy($dossier, [
            'dateInsert' => 'Date de création du dossier',
            'objet' => 'Objet',
            'dateMairie' => 'Date de réception mairie',
            'dateEnvoiTransit' => "Date d'envoi/transit",
            'dateReponse' => 'Date de réponse',
            'dateReception' => 'Date de réception SDIS',
            'demandeur' => 'Demandeur',
            'dateTransfertCommission' => 'Date de transfert commission',
            'dateReceptionCommission' => 'Date de réception commission',
            'observations' => 'Observations',
        ]);
    }

    /**
     * Test de comparaison pour visite périodique (nature 21).
     *
     * Champs attendus basés sur le legacy :
     * DATEINSERT, COMMISSION, DESCGEN, DESCEFF, DATEVISITE, DATEREP,
     * PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM, AVIS, AVIS_COMMISSION,
     * OBSERVATION, DELAIPRESC
     */
    public function testAffichageVisitePeriodique(): void
    {
        $dossier = $this->getDossierByNature(21);

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 21 en base de données');
        }

        $this->compareAffichageWithLegacy($dossier, [
            'dateInsert' => 'Date de création du dossier',
            'commission' => 'Commission concernée',
            'descriptifGeneral' => 'Descriptif général',
            'descriptifEffectifs' => 'Descriptif effectif',
            'dateVisite' => 'Date de visite',
            'dateReponse' => 'Date de réponse',
            'preventionnistes' => 'Préventionniste(s) du dossier',
            'avisDiffere' => 'Avis différé',
            'absenceDeQuorum' => 'Absence de quorum',
            'avis' => 'Avis du rapporteur',
            'avisCommission' => 'Avis de la commission',
            'observations' => 'Observations',
            'dateLimitePrescription' => 'Délai levée prescriptions',
        ]);
    }

    /**
     * Test de comparaison pour visite de contrôle (nature 28 - groupe de visite).
     *
     * Champs attendus basés sur le legacy :
     * DATEINSERT, OBJET, COMMISSION, DESCGEN, DESCEFF, DATECOMM, DATEVISITE,
     * AVIS, COORDSSI, DATEREP, PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM,
     * AVIS_COMMISSION, OBSERVATION, DELAIPRESC
     */
    public function testAffichageVisiteControle(): void
    {
        $dossier = $this->getDossierByNature(28);

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 28 en base de données');
        }

        $this->compareAffichageWithLegacy($dossier, [
            'dateInsert' => 'Date de création du dossier',
            'objet' => 'Objet',
            'commission' => 'Commission concernée',
            'descriptifGeneral' => 'Descriptif général',
            'descriptifEffectifs' => 'Descriptif effectif',
            'dateCommission' => 'Date de commission en salle',
            'dateVisite' => 'Date de visite',
            'avis' => 'Avis du rapporteur',
            'coordonneesSsi' => 'Coordonnées SSI',
            'dateReponse' => 'Date de réponse',
            'preventionnistes' => 'Préventionniste(s) du dossier',
            'avisDiffere' => 'Avis différé',
            'absenceDeQuorum' => 'Absence de quorum',
            'avisCommission' => 'Avis de la commission',
            'observations' => 'Observations',
            'dateLimitePrescription' => 'Délai levée prescriptions',
        ]);
    }

    /**
     * Test de comparaison pour visite inopinée (nature 29 - groupe de visite).
     *
     * Champs attendus basés sur le legacy :
     * DATEINSERT, OBJET, COMMISSION, DESCGEN, DESCEFF, DATECOMM, DATEVISITE,
     * AVIS, DATEREP, PREVENTIONNISTE, DIFFEREAVIS, ABSQUORUM, AVIS_COMMISSION,
     * OBSERVATION, DELAIPRESC
     */
    public function testAffichageVisiteInopinee(): void
    {
        $dossier = $this->getDossierByNature(29);

        if (null === $dossier) {
            $this->markTestSkipped('Aucun dossier de nature 29 en base de données');
        }

        $this->compareAffichageWithLegacy($dossier, [
            'dateInsert' => 'Date de création du dossier',
            'objet' => 'Objet',
            'commission' => 'Commission concernée',
            'descriptifGeneral' => 'Descriptif général',
            'descriptifEffectifs' => 'Descriptif effectif',
            'dateCommission' => 'Date de commission en salle',
            'dateVisite' => 'Date de visite',
            'avis' => 'Avis du rapporteur',
            'dateReponse' => 'Date de réponse',
            'preventionnistes' => 'Préventionniste(s) du dossier',
            'avisDiffere' => 'Avis différé',
            'absenceDeQuorum' => 'Absence de quorum',
            'avisCommission' => 'Avis de la commission',
            'observations' => 'Observations',
            'dateLimitePrescription' => 'Délai levée prescriptions',
        ]);
    }

    /**
     * Compare l'affichage Symfony avec les données attendues du legacy.
     *
     * @param array $expectedFields Mapping propriété => label affiché
     */
    private function compareAffichageWithLegacy(Dossier $dossier, array $expectedFields): void
    {
        // Créer un client authentifié
        $client = $this->createAuthenticatedClient();

        // Accéder à la page du dossier
        $crawler = $client->request('GET', sprintf('/dossier/%d', $dossier->getId()));

        // Si redirection, vérifier si c'est vers la page de login
        if ($client->getResponse()->isRedirect()) {
            $location = $client->getResponse()->headers->get('Location');
            $this->fail(sprintf(
                'Redirection inattendue vers %s - L\'authentification root/root a échoué',
                $location
            ));
        }

        $this->assertResponseIsSuccessful(sprintf(
            'La page /dossier/%d ne répond pas correctement',
            $dossier->getId()
        ));

        $errors = [];
        $warnings = [];

        foreach ($expectedFields as $property => $expectedLabel) {
            // Récupérer la valeur de la propriété en essayant différents prefixes
            $getter = 'get'.ucfirst($property);
            $hasser = 'has'.ucfirst($property);
            $isser = 'is'.ucfirst($property);

            $value = null;
            if (method_exists($dossier, $getter)) {
                $value = $dossier->$getter();
            } elseif (method_exists($dossier, $hasser)) {
                $value = $dossier->$hasser();
            } elseif (method_exists($dossier, $isser)) {
                $value = $dossier->$isser();
            } else {
                $errors[] = sprintf(
                    '❌ Propriété "%s" : aucun getter trouvé (%s(), %s() ou %s())',
                    $property,
                    $getter,
                    $hasser,
                    $isser
                );
                continue;
            }

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
     * Vérifie si un champ est affiché dans le DOM.
     */
    private function isFieldDisplayed(Crawler $crawler, string $property): bool
    {
        // Les champs sont dans des <li class='row'> avec une div.libelle et une div.value
        // On cherche le libelle correspondant au champ
        $labelMapping = $this->getLabelForProperty($property);

        if (null === $labelMapping) {
            return false;
        }

        // Chercher un élément qui contient ce libellé dans une div.libelle
        $libelle = $crawler->filter('.list-unstyled li .libelle')->reduce(function (Crawler $node) use ($labelMapping): bool {
            $text = trim($node->text());

            return $text === $labelMapping || false !== stripos($text, $labelMapping);
        });

        return $libelle->count() > 0;
    }

    // @todo: Utiliser la fonction d'au-dessus
    /**
     * Récupère le label affiché pour un champ.
     */
    private function getFieldLabel(Crawler $crawler, string $property): ?string
    {
        $labelMapping = $this->getLabelForProperty($property);

        if (null === $labelMapping) {
            return null;
        }

        // Chercher le libellé dans les div.libelle
        $libelle = $crawler->filter('.list-unstyled li .libelle')->reduce(function (Crawler $node) use ($labelMapping): bool {
            $text = trim($node->text());

            return $text === $labelMapping || false !== stripos($text, $labelMapping);
        });

        return $libelle->count() > 0 ? trim($libelle->first()->text()) : null;
    }

    /**
     * Mapping entre les propriétés de l'entité et les labels affichés dans le template.
     */
    private function getLabelForProperty(string $property): ?string
    {
        $mapping = [
            'dateInsert' => 'Date de création du dossier',
            'objet' => 'Objet',
            'demandeur' => 'Demandeur',
            'lieuReunion' => 'Lieu réunion',
            'dateReunion' => 'Date réunion',
            'dateReponse' => 'Date de réponse',
            'dateMairie' => 'Date de réception mairie',
            'dateSecretariat' => 'Date de réception secrétariat SDIS',
            'dateEnvoiTransit' => "Date d'envoi/transit",
            'dateReception' => 'Date de réception SDIS',
            'dateVisite' => 'Date de visite',
            'dateTransfertCommission' => 'Date de transfert commission',
            'dateReceptionCommission' => 'Date de réception commission',
            'observations' => 'Observations',
        ];

        return $mapping[$property] ?? null;
    }

    /**
     * Récupère la valeur affichée pour un champ.
     */
    private function getFieldValue(Crawler $crawler, string $property): ?string
    {
        $labelMapping = $this->getLabelForProperty($property);

        if (null === $labelMapping) {
            return null;
        }

        // Chercher la ligne correspondante
        $rows = $crawler->filter('.list-unstyled li.row');

        foreach ($rows as $row) {
            $rowCrawler = new Crawler($row);
            $libelle = $rowCrawler->filter('.libelle');

            if ($libelle->count() > 0) {
                $text = trim($libelle->text());
                if ($text === $labelMapping || false !== stripos($text, $labelMapping)) {
                    $value = $rowCrawler->filter('.value');
                    if ($value->count() > 0) {
                        $fullText = trim($value->text());
                        // Pour dateInsert, on extrait seulement la date (avant "par :")
                        if ('dateInsert' === $property && false !== strpos($fullText, 'par :')) {
                            return trim(explode('par :', $fullText)[0]);
                        }

                        return $fullText;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Formate une valeur pour l'affichage (dates, etc.).
     */
    private function formatValueForDisplay($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        return (string) $value;
    }

    /**
     * Affiche un rapport détaillé de la comparaison.
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
                echo $error."\n";
            }

            echo "\n";
        }

        if (!empty($warnings)) {
            echo "--- AVERTISSEMENTS ---\n";
            foreach ($warnings as $warning) {
                echo $warning."\n";
            }

            echo "\n";
        }

        if (empty($errors) && empty($warnings)) {
            echo "✅ PARFAIT ! Tous les champs sont conformes au legacy.\n";
        }

        echo "================================================================\n\n";
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
