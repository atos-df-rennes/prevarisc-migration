<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Dossier;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test fonctionnel pour l'édition d'un dossier selon sa nature.
 *
 * Consolide les anciens fichiers DossierEditNature<id>Test.php (un par nature) en
 * un seul jeu de scénarios nommés, en conservant les 46 identifiants de nature
 * historiquement couverts (2-27, 33, 37-42, 44-46, 48, 56, 61-64, 66-68), y
 * compris le cas d'état vide dédié à la nature 21.
 *
 * Ce sont des tests de rendu HTTP (présence de labels/champs dans le HTML), pas
 * des tests de visibilité navigateur : la visibilité réelle est décidée côté
 * JavaScript par public/js/dossier/modules/type-nature.js. Un champ peut donc
 * être présent dans le HTML tout en étant masqué visuellement.
 */
#[Group('functional')]
final class DossierEditNatureTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param list<string> $labels      labels attendus dans le formulaire de modification
     * @param list<string> $extraChecks fragments HTML supplémentaires (champs spécifiques à la nature)
     */
    #[DataProvider('provideNatures')]
    public function testFormDisplaysCorrectFieldsForNature(int $natureId, array $labels, array $extraChecks): void
    {
        $dossier = $this->findDossierForNature($natureId);

        if (null === $dossier) {
            $this->markTestSkipped(sprintf('Aucun dossier de nature %d disponible', $natureId));
        }

        $content = $this->requestDossierEditPage($dossier->getId());

        foreach ($labels as $label) {
            $this->assertStringContainsString($label, $content, sprintf("Le label '%s' devrait être présent dans le formulaire", $label));
        }

        foreach ($extraChecks as $fragment) {
            $this->assertStringContainsString($fragment, $content, sprintf("Le champ/fragment '%s' devrait être présent dans le HTML", $fragment));
        }
    }

    /**
     * Nature 21 (Visite périodique) : le formulaire doit rester affichable avec succès
     * même lorsque avisDiffere et absenceDeQuorum sont vides (pas de rechute HTTP).
     */
    public function testFormHidesFieldsWhenValuesAreEmptyForNature21(): void
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

        $this->requestDossierEditPage($dossier->getId());
    }

    /**
     * @return iterable<string, array{0: int, 1: list<string>, 2: list<string>}>
     */
    public static function provideNatures(): iterable
    {
        yield from [
            '2 (Autorisation de travaux - AT)' => [
                2,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Date de commission',
                    'Date de réponse',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'documentsUrbanisme',
                    'incomplet',
                    'horsDelai',
                ],
            ],
            '3 (Dérogation)' => [
                3,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Date de commission',
                    'Date de réponse',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'Justification de la dérogation',
                    'Mesures compensatoires',
                    'Mesures complémentaires',
                    'Règles de dérogation',
                    'documentsUrbanisme',
                    'incomplet',
                    'horsDelai',
                ],
            ],
            '4 (Cahier des charges fonctionnel du SSI)' => [
                4,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Date de commission',
                    'Date de réponse',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                    'documentsUrbanisme',
                    'serviceInstructeur',
                ],
            ],
            '5 (Cahier des charges de type T)' => [
                5,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Date de commission',
                    'Date de réponse',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                    'dossier[type]',
                ],
            ],
            '6 (Salon type T)' => [
                6,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Date de commission',
                    'Date de réponse',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                    'Chargé de sécurité (T)',
                    'dossier[chargeSecurite]',
                ],
            ],
            '7 (RVRMD diag sécu => Levée de prescriptions)' => [
                7,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Date de commission',
                    'Date de réception SDIS',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                    'dossier[dateReception]',
                    'dossier[chargeSecurite]',
                ],
            ],
            '8 (Documents divers)' => [
                8,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Date de commission',
                    'Date de réponse',
                    'Date de réception SDIS',
                    'Date réception préfecture',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                    'dossier[datePrefecture]',
                ],
            ],
            '9 (Changement de DUS)' => [
                9,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Date de commission',
                    'Date de réponse',
                    'Date de réception SDIS',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                    'dossier[datePrefecture]',
                ],
            ],
            '10 (Suivi organisme formation SSIAP)' => [
                10,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Date de réponse',
                    'Date de réception SDIS',
                    'Date réception préfecture',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                    'dossier[commission]',
                    'dossier[dateCommission]',
                ],
            ],
            '11 (Demande de registre de sécurité CTS)' => [
                11,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Date de commission',
                    'Date de réponse',
                    'Date de réception SDIS',
                    'Date réception préfecture',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                ],
            ],
            '12 (Demande d\'implantation CTS < 6mois)' => [
                12,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Date de réception mairie',
                    'Commission concernée',
                    'Date de commission',
                    'Date de réponse',
                    'Date de réception SDIS',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                    'dossier[datePrefecture]',
                ],
            ],
            '13 (Implantation CTS > 6mois)' => [
                13,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                ],
            ],
            '14 (Permis d\'aménager)' => [
                14,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                ],
            ],
            '15 (Permis de démolir)' => [
                15,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                ],
            ],
            '16 (CR visite organismes inspection sécurité)' => [
                16,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                ],
            ],
            '17 (Etude suite à avis différé)' => [
                17,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                ],
            ],
            '18 (Utilisation exceptionnelle de locaux)' => [
                18,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                ],
            ],
            '19 (Levée réserves suite avis défavorable)' => [
                19,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Commission concernée',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [
                    'incomplet',
                    'horsDelai',
                ],
            ],
            '20 (Réception de travaux)' => [
                20,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [],
            ],
            '21 (Visite périodique)' => [
                21,
                [
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
                ],
                [
                    'avisDiffere',
                    'absenceDeQuorum',
                ],
            ],
            '22 (Chantier)' => [
                22,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [],
            ],
            '23 (Contrôle)' => [
                23,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [],
            ],
            '24 (Inopinée)' => [
                24,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [],
            ],
            '25 (Réception de travaux)' => [
                25,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [],
            ],
            '26 (Périodique)' => [
                26,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [],
            ],
            '27 (Chantier)' => [
                27,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Avis de la commission',
                    'Observations',
                ],
                [],
            ],
            '33 (RVRMD (diag sécu))' => [
                33,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '37 (Incendie)' => [
                37,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '38 (SAP)' => [
                38,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '39 (Inter. div.)' => [
                39,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '40 (Ouverture)' => [
                40,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '41 (Fermeture)' => [
                41,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '42 (Mise en demeure)' => [
                42,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '44 (Utilisation exceptionnelle de locaux)' => [
                44,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '45 (Courrier)' => [
                45,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '46 (Echéancier de travaux)' => [
                46,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '48 (Avant ouverture)' => [
                48,
                [
                    'Date de création du dossier',
                    'Commission concernée',
                    'Date de visite',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '56 (Demande de visite de contrôle)' => [
                56,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '61 (Autorisation d\\\'une ICPE)' => [
                61,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '62 (Certificats d\\\'urbanisme)' => [
                62,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '63 (Demande organisation manifestation)' => [
                63,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '64 (Visite conseil)' => [
                64,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '66 (Déclassement / Reclassement)' => [
                66,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '67 (Demande de plans)' => [
                67,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
            '68 (Enregistrement déclaration)' => [
                68,
                [
                    'Date de création du dossier',
                    'Objet',
                    'Préventionniste(s)',
                    'Observations',
                ],
                [],
            ],
        ];
    }

    private function findDossierForNature(int $natureId): ?Dossier
    {
        return $this->entityManager
            ->getRepository(Dossier::class)
            ->createQueryBuilder('d')
            ->join('d.nature', 'n')
            ->where('n.id = :nature')
            ->setParameter('nature', $natureId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function requestDossierEditPage(int $dossierId): string
    {
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

        $client->request(Request::METHOD_GET, '/dossier/'.$dossierId.'/modifier');

        $this->assertResponseIsSuccessful();

        return (string) $client->getResponse()->getContent();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
    }
}
