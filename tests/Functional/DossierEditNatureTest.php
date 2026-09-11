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
 * un seul jeu de scénarios nommés, en conservant les 45 identifiants de nature
 * historiquement couverts (2-27, 33, 37-42, 44-46, 48, 56, 61-64, 66-68).
 *
 * Portée volontairement réduite à un contrôle de non-régression HTTP (200, pas
 * d'exception serveur) : le formulaire d'édition (`DossierType::buildForm()` et
 * `templates/dossier/edit.html.twig`) ajoute et affiche INCONDITIONNELLEMENT tous
 * les champs pour toutes les natures ; seul `public/js/dossier/modules/type-nature.js`
 * masque/affiche les champs côté client, après le rendu HTTP. Le filtre Twig
 * `should_display_field` (basé sur `DossierFields`) n'est utilisé que sur la page
 * de consultation (`dossier/index.html.twig`), jamais sur le formulaire d'édition.
 *
 * Autrement dit : vérifier la présence d'un label/champ précis dans le HTML de
 * cette page ne prouve rien de spécifique à la nature, puisque le même
 * superset de champs est rendu quelle que soit la nature. Ces assertions ont
 * donc été retirées ici. La couverture de "quel champ est visible pour quelle
 * nature" doit être apportée par Cypress (JS exécuté) pour ce formulaire, et par
 * des tests dédiés à `DossierFields`/`should_display_field` pour la page de
 * consultation.
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

    #[DataProvider('provideNatures')]
    public function testEditFormRendersSuccessfullyForNature(int $natureId): void
    {
        $dossier = $this->findDossierForNature($natureId);

        if (null === $dossier) {
            $this->markTestSkipped(sprintf('Aucun dossier de nature %d disponible', $natureId));
        }

        $this->requestDossierEditPage($dossier->getId());
    }

    /**
     * Nature 21 (Visite périodique) : le formulaire doit rester affichable avec succès
     * même lorsque avisDiffere et absenceDeQuorum sont vides (pas de rechute HTTP).
     */
    public function testEditFormRendersSuccessfullyForNature21WithEmptyAvisDiffere(): void
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
     * @return iterable<string, array{0: int}>
     */
    public static function provideNatures(): iterable
    {
        yield from [
            '2 (Autorisation de travaux - AT)' => [2],
            '3 (Dérogation)' => [3],
            '4 (Cahier des charges fonctionnel du SSI)' => [4],
            '5 (Cahier des charges de type T)' => [5],
            '6 (Salon type T)' => [6],
            '7 (RVRMD diag sécu => Levée de prescriptions)' => [7],
            '8 (Documents divers)' => [8],
            '9 (Changement de DUS)' => [9],
            '10 (Suivi organisme formation SSIAP)' => [10],
            '11 (Demande de registre de sécurité CTS)' => [11],
            '12 (Demande d\'implantation CTS < 6mois)' => [12],
            '13 (Implantation CTS > 6mois)' => [13],
            '14 (Permis d\'aménager)' => [14],
            '15 (Permis de démolir)' => [15],
            '16 (CR visite organismes inspection sécurité)' => [16],
            '17 (Etude suite à avis différé)' => [17],
            '18 (Utilisation exceptionnelle de locaux)' => [18],
            '19 (Levée réserves suite avis défavorable)' => [19],
            '20 (Réception de travaux)' => [20],
            '21 (Visite périodique)' => [21],
            '22 (Chantier)' => [22],
            '23 (Contrôle)' => [23],
            '24 (Inopinée)' => [24],
            '25 (Réception de travaux)' => [25],
            '26 (Périodique)' => [26],
            '27 (Chantier)' => [27],
            '33 (RVRMD (diag sécu))' => [33],
            '37 (Incendie)' => [37],
            '38 (SAP)' => [38],
            '39 (Inter. div.)' => [39],
            '40 (Ouverture)' => [40],
            '41 (Fermeture)' => [41],
            '42 (Mise en demeure)' => [42],
            '44 (Utilisation exceptionnelle de locaux)' => [44],
            '45 (Courrier)' => [45],
            '46 (Echéancier de travaux)' => [46],
            '48 (Avant ouverture)' => [48],
            '56 (Demande de visite de contrôle)' => [56],
            '61 (Autorisation d\'une ICPE)' => [61],
            '62 (Certificats d\'urbanisme)' => [62],
            '63 (Demande organisation manifestation)' => [63],
            '64 (Visite conseil)' => [64],
            '66 (Déclassement / Reclassement)' => [66],
            '67 (Demande de plans)' => [67],
            '68 (Enregistrement déclaration)' => [68],
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
