<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Constant\DossierType;
use App\Entity\AvisDerogations;
use App\Entity\Dossier;
use App\Entity\Privileges;
use App\Entity\Utilisateur;
use Doctrine\DBAL\Connection;
use App\Repository\DossierRepository;
use App\Repository\PrivilegesRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

#[Group('functional')]
final class AvisDerogationsVerificationsTechniquesPrivilegesTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
    }

    #[DataProvider('providePrivilegeCombinations')]
    public function testAvisDerogationsReadAndWritePrivilegesAreIndependent(
        string $readPrivilegeName,
        string $writePrivilegeName,
        int $dossierType,
        string $readPath,
        string $writePath,
        bool $read,
        bool $write,
        int $expectedGetStatus,
        int $expectedWriteStatus
    ): void {
        $dossier = $this->findDossier($dossierType);
        $user = $this->findRootUser();
        $privileges = $this->findPrivileges($readPrivilegeName, $writePrivilegeName);

        if (in_array(null, [$dossier, $user, $privileges], true)) {
            $this->markTestSkipped('Données de test ou privilèges séparés indisponibles.');
        }

        if (str_contains($writePath, 'avis-et-derogations')) {
            $avisDerogation = $dossier->getAvisDerogations()->first();
            if (!$avisDerogation instanceof AvisDerogations || null === $avisDerogation->getId()) {
                $this->markTestSkipped('Aucun avis ou dérogation disponible pour ce dossier.');
            }

            $writePath = str_replace('/1/', '/'.$avisDerogation->getId().'/', $writePath);
        }

        $group = $user->getGroupe();
        if (null === $group) {
            $this->markTestSkipped("Le compte root n'a pas de groupe.");
        }

        $connection = $this->entityManager->getConnection();
        $groupId = $group->getId();
        $privilegeIds = [$privileges[$readPrivilegeName]->getId(), $privileges[$writePrivilegeName]->getId()];
        $originalPrivilegeIds = $connection->executeQuery(
            'SELECT id_privilege FROM `groupe-privileges` WHERE ID_GROUPE = ? AND id_privilege IN (?, ?)',
            [$groupId, ...$privilegeIds],
        )->fetchFirstColumn();
        try {
            $this->setPrivilege($connection, $groupId, $privileges[$readPrivilegeName], $read);
            $this->setPrivilege($connection, $groupId, $privileges[$writePrivilegeName], $write);
            $this->entityManager->clear();
            $user = $this->findRootUser();

            self::ensureKernelShutdown();
            $client = static::createClient();
            $client->loginUser($user);

            $client->request(Request::METHOD_GET, '/dossier/'.$dossier->getId().$readPath);
            self::assertResponseStatusCodeSame($expectedGetStatus);

            $client->request(Request::METHOD_GET, '/dossier/'.$dossier->getId().$writePath);
            self::assertResponseStatusCodeSame($expectedWriteStatus);
        } finally {
            foreach ($privilegeIds as $privilegeId) {
                $connection->executeStatement(
                    'DELETE FROM `groupe-privileges` WHERE ID_GROUPE = ? AND id_privilege = ?',
                    [$groupId, $privilegeId],
                );
            }

            foreach ($originalPrivilegeIds as $privilegeId) {
                $connection->executeStatement(
                    'INSERT INTO `groupe-privileges` (ID_GROUPE, id_privilege) VALUES (?, ?)',
                    [$groupId, $privilegeId],
                );
            }

            $this->entityManager->clear();
        }
    }

    public static function providePrivilegeCombinations(): \Generator
    {
        foreach ([
            'avis et derogations' => [
                'lecture_avis_derogations',
                'ecriture_avis_derogations',
                DossierType::ETUDE,
                '/avis-et-derogations',
                '/avis-et-derogations/1/modifier',
            ],
            'verifications techniques' => [
                'lecture_verifications_techniques',
                'ecriture_verifications_techniques',
                DossierType::TYPES_VISITE[0],
                '/verifications-techniques',
                '/verifications-techniques/modifier',
            ],
        ] as $feature => $parameters) {
            yield $feature.' - neither' => [...$parameters, false, false, 403, 403];
            yield $feature.' - read only' => [...$parameters, true, false, 200, 403];
            yield $feature.' - write only' => [...$parameters, false, true, 403, 200];
            yield $feature.' - read and write' => [...$parameters, true, true, 200, 200];
        }
    }

    /**
     * @return array<string, Privileges>|null
     */
    private function findPrivileges(string ...$names): ?array
    {
        /** @var PrivilegesRepository $repository */
        $repository = $this->entityManager->getRepository(Privileges::class);
        $privileges = [];
        foreach ($names as $name) {
            $privilege = $repository->findOneBy(['name' => $name]);
            if (null === $privilege) {
                return null;
            }

            $privileges[$name] = $privilege;
        }

        return $privileges;
    }

    private function setPrivilege(
        Connection $connection,
        int $groupId,
        Privileges $privilege,
        bool $enabled
    ): void
    {
        $connection->executeStatement(
            'DELETE FROM `groupe-privileges` WHERE ID_GROUPE = ? AND id_privilege = ?',
            [$groupId, $privilege->getId()],
        );
        if ($enabled) {
            $connection->executeStatement(
                'INSERT INTO `groupe-privileges` (ID_GROUPE, id_privilege) VALUES (?, ?)',
                [$groupId, $privilege->getId()],
            );
        }
    }

    private function findDossier(int $typeId): ?Dossier
    {
        /** @var DossierRepository $repository */
        $repository = $this->entityManager->getRepository(Dossier::class);

        return $repository->createQueryBuilder('d')
            ->join('d.type', 'type')
            ->where('type.id = :type')
            ->setParameter('type', $typeId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findRootUser(): ?Utilisateur
    {
        return $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['username' => 'root']);
    }

    protected function tearDown(): void
    {
        $this->entityManager->close();

        parent::tearDown();
    }
}
