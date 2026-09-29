<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\EtablissementInformations;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test fonctionnel : lors de la création d'un dossier depuis un établissement,
 * les préventionnistes affectés à l'établissement doivent être présélectionnés
 * sur le dossier (cf. legacy, bug de régression signalé par un client).
 */
#[Group('functional')]
final class DossierNewFromEtablissementPreventionnisteTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testPreventionnistesDeLetablissementSontPreselectionnesSurLeNouveauDossier(): void
    {
        // Récupérer un établissement dont les dernières informations ont au moins un préventionniste actif
        // (getLatestInformationsOrNull() est utilisé par le contrôleur pour la présélection)
        $candidats = $this->entityManager
            ->createQueryBuilder()
            ->select('ei')
            ->from(EtablissementInformations::class, 'ei')
            ->join('ei.preventionnistes', 'p')
            ->where('p.actif = true')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $informationsEtablissement = null;
        foreach ($candidats as $candidat) {
            $latestInformations = $candidat->getEtablissement()->getLatestInformationsOrNull();
            if (null !== $latestInformations && $latestInformations->getId() === $candidat->getId()) {
                $informationsEtablissement = $candidat;

                break;
            }
        }

        if (null === $informationsEtablissement) {
            $this->markTestSkipped("Aucun établissement dont les dernières informations ont un préventionniste actif n'est disponible");
        }

        $etablissement = $informationsEtablissement->getEtablissement();

        $preventionnistesAttendus = [];
        foreach ($informationsEtablissement->getPreventionnistes() as $preventionniste) {
            $preventionnistesAttendus[] = $preventionniste->getId();
        }

        // Récupérer l'utilisateur root
        $utilisateur = $this->entityManager
            ->getRepository(Utilisateur::class)
            ->findOneBy(['username' => 'root']);

        if (!$utilisateur) {
            $this->markTestSkipped('Utilisateur root non trouvé');
        }

        // Simuler l'authentification Zend dans $_SESSION
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }

        $_SESSION['Zend_Auth'] = [
            'storage' => [
                'USERNAME_UTILISATEUR' => 'root',
            ],
        ];

        self::ensureKernelShutdown();

        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/dossier/ajouter?etablissement='.$etablissement->getId());

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();

        foreach ($preventionnistesAttendus as $idPreventionniste) {
            $this->assertStringContainsString(
                'value="'.$idPreventionniste.'" selected',
                $content,
                sprintf(
                    "Le préventionniste #%d de l'établissement devrait être présélectionné sur le nouveau dossier",
                    $idPreventionniste
                )
            );
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
    }
}
