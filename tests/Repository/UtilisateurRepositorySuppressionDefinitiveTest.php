<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Fonction;
use App\Entity\Groupe;
use App\Entity\Utilisateur;
use App\Entity\UtilisateurInformations;
use App\Enum\MotifConservationUtilisateurEnum;
use App\Enum\ResultatSuppressionUtilisateurEnum;
use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('functional')]
final class UtilisateurRepositorySuppressionDefinitiveTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private Connection $connection;

    private UtilisateurRepository $repository;

    private Fonction $fonction;

    private Groupe $groupe;

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;

        $this->connection = $this->entityManager->getConnection();
        $this->connection->beginTransaction();

        $this->repository = self::getContainer()->get(UtilisateurRepository::class);

        $this->fonction = new Fonction()->setLibelle('Test suppression '.uniqid('', true));
        $this->groupe = new Groupe()->setLibelle('Test suppression '.uniqid('', true));
        $this->entityManager->persist($this->fonction);
        $this->entityManager->persist($this->groupe);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }

        $this->entityManager->clear();

        parent::tearDown();
    }

    public function testFindReferencesBloquantesRetourneLesMotifsDesUtilisateursReferences(): void
    {
        $auteur = $this->creerUtilisateur();
        $libre = $this->creerUtilisateur();
        $this->connection->insert('news', ['TYPE_NEWS' => 'info', 'TEXTE_NEWS' => 'Test', 'ID_UTILISATEUR' => $auteur->getId()]);

        $this->assertSame([$auteur->getId(), $libre->getId()], $this->repository->findIdsByGroupe($this->groupeId()));
        $this->assertSame([$auteur->getId() => [MotifConservationUtilisateurEnum::AuteurActualite]], $this->repository->findReferencesBloquantes([$auteur->getId(), $libre->getId()]));
    }

    public function testSupprimerDefinitivementSupprimeLeCompteSesPreferencesSesRattachementsEtSaFiche(): void
    {
        $utilisateur = $this->creerUtilisateur();
        $id = $this->idUtilisateur($utilisateur);
        $informationsId = $this->idInformations($utilisateur);
        $this->connection->insert('utilisateurpreferences', ['ID_UTILISATEUR' => $id]);
        $this->connection->insert('utilisateurcommission', [
            'ID_UTILISATEUR' => $id,
            'ID_COMMISSION' => $this->creerCommissionId(),
        ]);
        $this->connection->insert('utilisateurgroupement', [
            'ID_UTILISATEUR' => $id,
            'ID_GROUPEMENT' => $this->creerGroupementId(),
        ]);

        $this->assertSame(ResultatSuppressionUtilisateurEnum::Supprime, $this->repository->supprimerDefinitivement($id, $this->groupeId()));
        $this->assertSame(0, $this->compter('utilisateur', 'ID_UTILISATEUR', $id));
        $this->assertSame(0, $this->compter('utilisateurpreferences', 'ID_UTILISATEUR', $id));
        $this->assertSame(0, $this->compter('utilisateurcommission', 'ID_UTILISATEUR', $id));
        $this->assertSame(0, $this->compter('utilisateurgroupement', 'ID_UTILISATEUR', $id));
        $this->assertSame(0, $this->compter('utilisateurinformations', 'ID_UTILISATEURINFORMATIONS', $informationsId));
    }

    public function testSupprimerDefinitivementConserveUneFichePartagee(): void
    {
        $utilisateur = $this->creerUtilisateur();
        $id = $this->idUtilisateur($utilisateur);
        $informationsId = $this->idInformations($utilisateur);
        $this->connection->insert('groupementcontact', [
            'ID_GROUPEMENT' => $this->creerGroupementId(),
            'ID_UTILISATEURINFORMATIONS' => $informationsId,
        ]);

        $this->assertSame(ResultatSuppressionUtilisateurEnum::SupprimeFicheConservee, $this->repository->supprimerDefinitivement($id, $this->groupeId()));
        $this->assertSame(0, $this->compter('utilisateur', 'ID_UTILISATEUR', $id));
        $this->assertSame(1, $this->compter('utilisateurinformations', 'ID_UTILISATEURINFORMATIONS', $informationsId));
    }

    public function testSupprimerDefinitivementConserveUneFicheReferenceeUniquementParUnMembreDeCommission(): void
    {
        $utilisateur = $this->creerUtilisateur();
        $id = $this->idUtilisateur($utilisateur);
        $informationsId = $this->idInformations($utilisateur);
        $informations = $this->connection->fetchAssociative(
            'SELECT * FROM utilisateurinformations WHERE ID_UTILISATEURINFORMATIONS = ?',
            [$informationsId],
        );
        $this->connection->insert('commissionmembre', [
            'LIBELLE_COMMISSIONMEMBRE' => 'Test suppression',
            'PRESENCE_COMMISSIONMEMBRE' => 1,
            'ID_COMMISSION' => $this->creerCommissionId(),
            'ID_UTILISATEURINFORMATIONS' => $informationsId,
        ]);

        $this->assertSame(ResultatSuppressionUtilisateurEnum::SupprimeFicheConservee, $this->repository->supprimerDefinitivement($id, $this->groupeId()));
        $this->assertSame(0, $this->compter('utilisateur', 'ID_UTILISATEUR', $id));
        $this->assertSame(1, $this->compter('commissionmembre', 'ID_UTILISATEURINFORMATIONS', $informationsId));
        $this->assertSame($informations, $this->connection->fetchAssociative(
            'SELECT * FROM utilisateurinformations WHERE ID_UTILISATEURINFORMATIONS = ?',
            [$informationsId],
        ));
    }

    public function testSupprimerDefinitivementIgnoreUnUtilisateurQuiNAppartientPlusAuGroupe(): void
    {
        $utilisateur = $this->creerUtilisateur();
        $id = $this->idUtilisateur($utilisateur);

        $this->assertSame(ResultatSuppressionUtilisateurEnum::Absent, $this->repository->supprimerDefinitivement($id, $this->groupeId() + 1_000_000));
        $this->assertSame(1, $this->compter('utilisateur', 'ID_UTILISATEUR', $id));
    }

    private function creerUtilisateur(): Utilisateur
    {
        $informations = new UtilisateurInformations()
            ->setNom('Suppression')
            ->setPrenom('Test')
            ->setFonction($this->fonction);
        $utilisateur = new Utilisateur()
            ->setUsername('suppression-test-'.uniqid('', true))
            ->setActif(true)
            ->setGroupe($this->groupe)
            ->setInformations($informations);
        $this->entityManager->persist($informations);
        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        return $utilisateur;
    }

    private function creerCommissionId(): int
    {
        $this->connection->insert('commissiontype', ['LIBELLE_COMMISSIONTYPE' => 'Test suppression']);
        $this->connection->insert('commission', [
            'LIBELLE_COMMISSION' => 'Test suppression',
            'ID_COMMISSIONTYPE' => (int) $this->connection->lastInsertId(),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function creerGroupementId(): int
    {
        $this->connection->insert('groupementtype', ['LIBELLE_GROUPEMENTTYPE' => 'Test suppression']);
        $this->connection->insert('groupement', [
            'LIBELLE_GROUPEMENT' => 'Test suppression',
            'ID_GROUPEMENTTYPE' => (int) $this->connection->lastInsertId(),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    private function compter(string $table, string $colonne, int $id): int
    {
        $nombre = $this->connection->fetchOne(\sprintf('SELECT COUNT(*) FROM %s WHERE %s = ?', $table, $colonne), [$id]);

        return is_numeric($nombre) ? (int) $nombre : -1;
    }

    private function groupeId(): int
    {
        return $this->groupe->getId() ?? throw new \UnexpectedValueException('Groupe de test non persisté.');
    }

    private function idUtilisateur(Utilisateur $utilisateur): int
    {
        return $utilisateur->getId() ?? throw new \UnexpectedValueException('Utilisateur de test non persisté.');
    }

    private function idInformations(Utilisateur $utilisateur): int
    {
        return $utilisateur->getInformations()?->getId() ?? throw new \UnexpectedValueException('Informations de test non persistées.');
    }
}
